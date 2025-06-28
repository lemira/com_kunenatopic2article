<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   Copyright (C) 2023 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Model;

\defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Kunena\Bbcode\KunenaBbcode; 
use Joomla\CMS\HTML\HTMLHelper;

/**
 * Article Model
 * @since  0.0.1
 */
class ArticleModel extends BaseDatabaseModel
{
    protected $db; // @var \Joomla\Database\DatabaseInterface 
    protected $app; /** @var \Joomla\CMS\Application\CMSApplication */
    private $currentArticle = null;  
    private int $articleSize = 0;    // Текущий размер статьи , @var    int 
    private $articleLinks = [];  // Массив ссылок на созданные статьи  @var array 
    private int $postId = 0;   // Текущий ID поста @var    int
    private string $postText = ''; // Текст текущего поста 
    private int $postSize = 0; // Размер текущего поста var    int
    private $postIdList = []; // Список ID постов для обработки @var    array
    private $currentPost = null;  // Текущий пост @var    object
    private string $subject = ''; // Переменная модели для хранения subject
    private $params = null; // Хранение параметров для доступа в других методах
    private int $currentIndex = 0; // первый переход с первого элемента $topicId = $firstPostId (0) на 2-й (1)
    private string $infoString = '';  // строка сборки информационной строки поста в createPostInfoString()
    private string $postInfoString = '';  // Информационная строка поста
    private string $reminderLines = '';  // строки напоминания поста
    private string $title = '';   // Заголовок статьи
    
      public function __construct($config = [])
{
    parent::__construct($config);
    
    $this->app = Factory::getApplication();
    $this->db = $this->getDatabase();
    
}

    /**
     * Создание статей из темы форума Kunena
     * @param   array  $params  Настройки для создания статей
     * @return  array  Массив ссылок на созданные статьи
     */
    public function createArticlesFromTopic($params)
    {   // Параметры $params получены в контроллере из таблицы kunenatopic2article_params; копию функции можно взять из контроллера
        $this->params = $params; 
        $this->articleLinks = []; // Инициализация массива ссылок
        $this->currentArticle = null;     // статья не открыта 
     
        try {
              
            // Получаем ID первого поста
            $firstPostId = $params->topic_selection; // 3232
            Factory::getApplication()->enqueueMessage('ArticleModel $firstPostId: ' . $firstPostId, 'info'); // ОТЛАДКА          
           
              $this->postId = $firstPostId; // текущий id 
              $this->openPost($this->postId); // Открываем первый пост темы для доступа к его параметрам
              $this->reminderLines = ""; // у первого поста нет строк напоминания

            // Формируем список ID постов в зависимости от схемы обхода; должно быть после открытия первого поста!
            if ($this->params->post_transfer_scheme != 1) {
                $this->postIdList = $this->buildFlatPostIdList($firstPostId);
                } else {
                $this->postIdList = $this->buildTreePostIdList($firstPostId);
                }

              $this->subject = $this->currentPost->subject;
           Factory::getApplication()->enqueueMessage('createArticlesFromTopic $subject: ' . $this->subject, 'info'); // ОТЛАДКА 
              $this->topicAuthorId = $this->currentPost->userid;

              $this->openArticle();     // Открываем первую статью
                    
               // Основной цикл обработки постов
                while ($this->postId != 0) {
                
                   // Статья открыта
                    Factory::getApplication()->enqueueMessage('Основной цикл Размер статьи: ' . $this->articleSize, 'info'); // ОТЛАДКА  
                    Factory::getApplication()->enqueueMessage('Основной цикл Размер статьи: ' . $this->postSize, 'info'); // ОТЛАДКА 
                if ($this->articleSize + $this->postSize > $this->params->max_article_size) {
                    $this->closeArticle();     // закрываем её перед открытием новой
                    $this->openArticle();   // Открываем новую статью
                    }
            
                $this->transferPost(); // Переносим содержимое поста в статью
                $this->nextPost(); // Переходим к следующему посту
                $this->openPost($this->postId); // Открываем пост для доступа к его параметрам, не открываем пост после последнего
            }      // Конец основного цикла обработки постов

       
            // Закрываем последнюю статью
            if ($this->currentArticle !== null) {
                $this->closeArticle();
            }
            Factory::getApplication()->enqueueMessage('createArticlesFromTopic: последняя статья' . $this->subject, 'info'); // ОТЛАДКА 

            return $this->articleLinks;
         } catch (\Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return $this->articleLinks;
        }
    }

    /**
     * Открытие статьи для её заполнения
     * @return  boolean  True в случае успеха
     */
    private function openArticle()
    {
          try {

           $this->currentArticle = new \stdClass(); // Инициализируем $this->currentArticle как stdClass
           // Сбрасываем текущий размер статьи
           $this->articleSize = 0;
           $this->currentArticle->fulltext = ''; // для возможного изменения строк предупреждения
           $this->currentArticle->fulltext .=  Text::_('COM_KUNENATOPIC2ARTICLE_INFORMATION_SIGN') . '<br />'    // ?? не учтена длина!
                 . Text::_('COM_KUNENATOPIC2ARTICLE_WARNING_SIGN') 
                 . '<hr style="width: 50%; height: 1px; background: linear-gradient(to right, transparent, #ccc, transparent); margin: 0 auto; border: none;">'; //  Линия с тенью (эффект углубления)
           
            // Формируем базовый заголовок статьи
            $this->title = $this->subject;
            // Если это не первая статья, добавляем номер части
            if (!empty($this->articleLinks)) {
                $partNum = count($this->articleLinks) + 1;
                $this->title .= ' - ' . Text::sprintf('COM_KUNENATOPIC2ARTICLE_PART_NUMBER', $partNum);
            }
            $this->currentArticle->title = $this->title;
           
            // Формируем уникальный алиас
            $baseAlias = OutputFilter::stringURLSafe($this->title);
            $uniqueAlias = $this->getUniqueAlias($baseAlias);
            $this->currentArticle->alias = $uniqueAlias;
              
            // Отладка
            $this->app->enqueueMessage('openArticle Статья открыта: ' . $this->title . ', категория: ' . $this->params->article_category . ', alias: ' . $uniqueAlias, 'notice');

            return true;
         } catch (\Exception $e) {
            $this->app->enqueueMessage('Ошибка при открытии статьи: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Генерация уникального алиаса для статьи
     * @param   string  $baseAlias  Базовый алиас
     * @return  string  Уникальный алиас
     */
   private function getUniqueAlias($baseAlias)
{
    $db = $this->db;
    $counter = '';
    $alias = $baseAlias;

    // Проверяем уникальность алиаса и автоматически добавляем номер, если нужно
    while ($this->aliasExists($alias)) {
        $counter = ($counter === '') ? 2 : $counter + 1;
        $alias = $baseAlias . '-' . $counter;
    }

    return $alias;
}
    
    /**
     * Проверка существования алиаса
     * @param   string  $alias  Алиас для проверки
     * @return  boolean  True если алиас существует
     */
    private function aliasExists($alias)
    {
    try {
        $query = $this->db->getQuery(true)
            ->select('1')
            ->from($this->db->quoteName('#__content'))
            ->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($alias))
            ->setLimit(1);

        return (bool) $this->db->setQuery($query)->loadResult();
    } catch (\Exception $e) {
        return false;
    }
}

    /**
     * Закрытие и сохранение статьи
     * @return  boolean  True в случае успеха
     */
    private function closeArticle()
    {
        if ($this->currentArticle === null) {
            return false;
        }

        try {
Factory::getApplication()->enqueueMessage('closeArticle Сохранение статьи: ' . $this->currentArticle->title, 'info'); // ОТЛАДКА          

            // Обработка introtext, если он пустой
           // if (empty($this->currentArticle->introtext) && !empty($this->currentArticle->fulltext)) {
             //   $maxIntroLength = 500; // Максимальная длина введения
               // if (strlen($this->currentArticle->fulltext) > $maxIntroLength) {
                 //   $this->currentArticle->introtext = substr($this->currentArticle->fulltext, 0, $maxIntroLength) . '...';
                //} else {
                  //  $this->currentArticle->introtext = $this->currentArticle->fulltext;
               // }
       
            // Создаем статью через Table
            $articleId = $this->createArticleViaTable();
                         
            if (!$articleId) {
                throw new \Exception('Ошибка сохранения статьи.');
            }

            // Формируем URL для статьи
            $link = Route::_('index.php?option=com_content&view=article&id=' . $articleId);
            
            // Добавляем ссылку и заголовок в массив для последующего вывода
            $this->articleLinks[] = [
                'title' => $this->currentArticle->title,
                'url' => Uri::root() . ltrim($link, '/'),
                'id' => $articleId
            ];

            // Отладка
            $this->app->enqueueMessage('Статья успешно сохранена с ID: ' . $articleId, 'notice');

            // Сбрасываем текущую статью
            $this->currentArticle = null;

            return true;
        } catch (\Exception $e) {
            $this->app->enqueueMessage('Ошибка сохранения статьи: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /** 
     * Создание статьи через Table API
     * @return  boolean|int  False в случае неудачи, ID статьи в случае успеха
     */
    protected function createArticleViaTable()
    {
        try {
            // Получаем table для контента
            $tableArticle = Table::getInstance('Content');
            
            if (!$tableArticle) {
                throw new \Exception('Не удалось получить таблицу контента');
            }

            // Подготавливаем данные 
                $data = [
                'title' => $this->currentArticle->title,
                'alias' => $this->currentArticle->alias,
                'introtext' => HTMLHelper::_('string.truncate', $this->currentArticle->fulltext, 200),
                'fulltext' => $this->currentArticle->fulltext,
                'catid' => (int) $this->params->article_category,
                'created_by' => (int)$this->topicAuthorId, 
                'state' => 1, // Published
                'language' => '*',
                'access' => 1,
                'created' => (new \Joomla\CMS\Date\Date())->toSql(),
                'publish_up' => (new \Joomla\CMS\Date\Date())->toSql(),
                'attribs' => '{"show_publishing_options":"","show_article_options":"","show_urls_images_backend":"","show_urls_images_frontend":""}',
                'metakey' => '',
                'metadesc' => '',
                'metadata' => '{"robots":"","author":"","rights":""}', // Стандартные метаданные
            ];

            // Привязываем данные к таблице
            if (!$tableArticle->bind($data)) {
                throw new \Exception('Ошибка привязки данных: ' . $tableArticle->getError());
            }

            // Проверяем данные
            if (!$tableArticle->check()) {
                throw new \Exception('Ошибка проверки данных: ' . $tableArticle->getError());
            }

            // Сохраняем
            if (!$tableArticle->store()) {
                throw new \Exception('Ошибка сохранения: ' . $tableArticle->getError());
            }
        // Получаем ID созданной статьи
        $articleId = $tableArticle->id;
            
        // --- Запись в #__workflow_associations
         try {
            // Проверяем, есть ли уже запись
           $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__workflow_associations'))
                ->where($this->db->quoteName('item_id') . ' = ' . $this->db->quote($articleId))
                ->where($this->db->quoteName('extension') . ' = ' . $this->db->quote('com_content.article'));
            $exists = (bool) $this->db->setQuery($query)->loadResult();

            if (!$exists) {
                $query = $this->db->getQuery(true)
                    ->insert($this->db->quoteName('#__workflow_associations'))
                    ->columns([
                        $this->db->quoteName('item_id'),
                        $this->db->quoteName('stage_id'),
                        $this->db->quoteName('extension')
                    ])
                    ->values(implode(',', [
                        $this->db->quote($articleId),
                        $this->db->quote(1), // stage_id=1 (опубликовано)
                        $this->db->quote('com_content.article')
                    ]));
                $this->db->setQuery($query)->execute();
            }
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем работу
            $this->app->enqueueMessage('Ошибка добавления записи в workflow_associations: ' . $e->getMessage(), 'warning');
        }
       // --- Конец записи в #__workflow_associations
            
            return $articleId;
            
        } catch (\Exception $e) {
            $this->app->enqueueMessage('Ошибка создания статьи через Table: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Открытие поста для доступа к его параметрам
     * @param   int  $postId  ID поста
     * @return  boolean  True в случае успеха
     */
    private function openPost($postId)
    {
         $postInfoString = ''; // Инициализация
        try {
            if ($this->postId == 0) {      // не открываем пост после последнего
                 return false;
                    }
            // Получаем данные поста из базы данных Kunena, фильтрация промодерированных постов сделана раньше
            $query = $this->db->getQuery(true)
                ->select('*')        // Нужно сделать получение только используемых полей
                ->from($this->db->quoteName('#__kunena_messages'))
                ->where($this->db->quoteName('id') . ' = ' . (int)$postId);

            $this->currentPost = $this->db->setQuery($query)->loadObject();
            // Проверка if (!$this->currentPost) не нужна, все посты проверены; сбой БД ловится в catch 
        
            // Создаём запрос текста поста
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('message'))
                ->from($this->db->quoteName('#__kunena_messages_text'))
                ->where($this->db->quoteName('mesid') . ' = ' . (int)$postId);

            // Получаем текст поста
            $this->postText = $this->db->setQuery($query)->loadResult();

            // Проверяем, найден ли текст
            if ($this->postText === null) {
                throw new \Exception(Text::sprintf('COM_YOURCOMPONENT_POST_TEXT_NOT_FOUND', $postId));
            }

            // Приведение типов с проверкой (PHP 8.2+ style)
           $this->postText = $this->postText ?? '';
           $this->reminderLines = $this->reminderLines ?? '';
            
           $this->postInfoString = $this->createPostInfoString(); // Вычиcляем информационную строку (всегда есть хотя бы разделители) поста
           
            // Вычисляем размер поста (в символах)  ?? Может быть, надо вычислять размер после перекодировки?
           // Расчёт длины с обработкой ошибок
           try {
              $this->postSize = mb_strlen($this->postText, 'UTF-8')
              + mb_strlen($this->postInfoString, 'UTF-8')
              + mb_strlen($this->reminderLines, 'UTF-8');
          } catch (\Throwable $e) {
               throw new \RuntimeException('Ошибка расчёта размера поста: ' . $e->getMessage());
          }
            //    Factory::getApplication()->enqueueMessage('openPost Размер поста с и.с.: ' . $this->postSize, 'info'); // ОТЛАДКА          
          return true;
        } catch (\Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Перенос поста в статью
     * @return  boolean  True в случае успеха
     */
    private function transferPost()
    {
       try {
            // Преобразуем BBCode в HTML
            $htmlContent = $this->convertBBCodeToHtml($this->postText);
            
            $this->printHeadOfPost();    // Добавляем в статью инф строку(не пуста) и, если нужно, строки напоминнания ; обязательно ПОСЛЕ Преобразования BBCode
                      
            // Добавляем преобразованный текст в статью
            $this->currentArticle->fulltext .= $htmlContent;

           // Вычисляем строки напоминания текущего поста, используются в следующем посте
                $this->reminderLines = '<br />'  . Text::_('COM_KUNENATOPIC2ARTICLE_START_OF_REMINDER_LINES') 
                 . '#' . $this->currentPost->parent . ': '
                       . HTMLHelper::_('string.truncate', $this->htmlContent, (int)$this->params->reminder_lines) . '<br />';

           $this->currentArticle->fulltext .= '<hr style="width: 75%; height: 1px; background: black; margin: 0 auto; border: none;">'; // добавляем линию разделения пстов, ?? не учтена в длине статьи!
                        
            // Обновляем размер статьи DOLLARthis - postSize включает длину инф строки и строки напоминания, вычислен в openPost
            $this->articleSize += $this->postSize;
// Factory::getApplication()->enqueueMessage('transferPost Размер статьи: ' . $this->articleSize, 'info'); // ОТЛАДКА   
            return true;
        } catch (\Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Переход к следующему посту
     * @return  int  ID следующего поста или 0, если больше нет постов
     */
    private function nextPost()
    {
         // Переход к следующему посту
           if (isset($this->postIdList[$this->currentIndex + 1])) {      // Если есть следующий элемент
            $this->currentIndex += 1;
            $this->postId = $this->postIdList[$this->currentIndex]; // Возвращаем следующий ID
   } else {
            // Если больше нет постов
            $this->postId = 0;
        }
  Factory::getApplication()->enqueueMessage('nextPost Id: ' . $this->postId, 'info'); // ОТЛАДКА          
        return $this->postId;
    }

    /**
     * Построение списка ID постов для плоской схемы обхода (по времени создания)
     * @param   int  $firstPostId  ID первого поста темы
     * @return  array  Список ID постов
     */
    private function buildFlatPostIdList($firstPostId)
    {
        try {
      $threadId = (int) $this->currentPost->thread; // Получаем Id темы

            // Получаем все посты темы
    $query = $this->db->getQuery(true)
    ->select($this->db->quoteName('id'))
    ->from($this->db->quoteName('#__kunena_messages'))
    ->where($this->db->quoteName('thread') . ' = ' . $threadId) // Используем полученный ID
    ->where($this->db->quoteName('hold') . ' = 0');

// --- НАЧАЛО БЛОКА ДЛЯ ИСКЛЮЧЕНИЯ АВТОРОВ --- дж
$ignoredAuthors = trim($this->params->ignored_authors); // Получаем и обрабатываем список игнорируемых авторов
if (!empty($ignoredAuthors)) { // Проверяем, что список не пустой
    $ignoredAuthorsArray = array_filter(array_map('trim', explode(',', $ignoredAuthors)));  // Разбиваем строку на массив, очищаем от пробелов и удаляем пустые значения
   if (!empty($ignoredAuthorsArray)) {     // Если после очистки в массиве остались имена, добавляем условие в запрос
       $quotedAuthors = array_map(array($this->db, 'quote'), $ignoredAuthorsArray);  // Безопасно квотируем каждое имя для использования в SQL-запросе
        // Добавляем условие NOT IN к запросу
        $query->where($this->db->quoteName('name') . ' NOT IN (' . implode(',', $quotedAuthors) . ')');
    }
}
// --- КОНЕЦ БЛОКА ИСКЛЮЧЕНИЯ АВТОРОВ ---

// Добавляем сортировку в конце
$query->order($this->db->quoteName('time') . ' ASC');
         
            $postIds = $this->db->setQuery($query)->loadColumn();
            $this->currentIndex = 0; // в nextPost() начинаем переход сразу к элементу (1), т.к. (0) = $topicId = $firstPostId
                
    Factory::getApplication()->enqueueMessage('Массив ID постов: ' . print_r($postIds, true), 'info'); // ОТЛАДКА
         
            return $postIds;
            
        } catch (\Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return []; // м. использовать: return null; // Вернуть null при ошибке
        }
    }

    /**
     * Построение списка ID постов для древовидной схемы обхода
     * @param   int  $topicId  ID темы
     * @return  array  Список ID постов
     */
    private function buildTreePostIdList($firstPostId)
    {
        // Заглушка: в реальной реализации здесь должен быть алгоритм обхода дерева
        // На данный момент возвращаем плоский список как временное решение
                 
            return $this->buildFlatPostIdList($firstPostId);
     }

    /**
     * Формирование информационной строки о посте
     * @return  string  Информационная строка
     */
 private function createPostInfoString()
{
    if ($this->currentPost === null) {
        return '';
    }

    $infoString = HTMLHelper::_('content.prepare', '<div class="kun_p2a_infoPostString text-center">'); // Используем современный синтаксис Joomla 5
    
    // IDs постов (с ссылкой или без)
    if ($this->params->post_ids) {      // НАЧАЛО БЛОКА IDs
    // Формируем часть строки с ID постов
    $idsString = '';
    
    // Текущий пост
    if ($this->params->kunena_post_link) {
    $postUrl = $this->getKunenaPostUrl($this->currentPost->id);
    $idsString .= ' / <a href="' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') 
               . '" target="_blank" rel="noopener noreferrer">#' 
               . $this->currentPost->id . '</a>';
} else {
    $idsString .= ' / #' . $this->currentPost->id;
}
 // Родительский пост
if (!empty($this->currentPost->parent)) {
    if ($this->params->kunena_post_link) {
        $parentUrl = $this->getKunenaPostUrl($this->currentPost->parent);
        $idsString .= ' << <a href="' . htmlspecialchars($parentUrl, ENT_QUOTES, 'UTF-8') 
                   . '" target="_blank" rel="noopener noreferrer">#' 
                   . $this->currentPost->parent . '</a>';
    } else {
        $idsString .= ' << #' . $this->currentPost->parent;
    }
}
$infoString .= $idsString;
    }  // КОНЕЦ БЛОКА IDs
  $infoString .= '<br />';  
    
  // Автор (никнейм)
    if ($this->params->post_author) {
        $infoString .= htmlspecialchars($this->currentPost->name, ENT_QUOTES, 'UTF-8');
    }
    
    // Заголовок поста
    if ($this->params->post_title) {
        $infoString .= ' / ' . htmlspecialchars($this->currentPost->subject, ENT_QUOTES, 'UTF-8');
    }
    
    // Дата и время
    if ($this->params->post_creation_date) {
        $date = date('d.m.Y', $this->currentPost->time);
        $infoString .= ' / ' . $date;
        
        if ($this->params->post_creation_time) {
            $time = date('H:i', $this->currentPost->time);
            $infoString .= ' ' . $time;
        }
    }

    // Закрываем блок
   $infoString .= '<br /></div>';   
    
    return $infoString;
}
    
   /**
     * Преобразование BBCode в HTML
     * @param   string  $text  Текст с BBCode
     * @return  string  HTML-текст
     */
    private function convertBBCodeToHtml($text)
{
    try {
        if (!class_exists('Kunena\Forum\Libraries\Bbcode\KunenaBbcode')) {
            $this->app->enqueueMessage(
                Text::_('COM_KUNENATOPIC2ARTICLE_BBCODE_PARSER_NOT_AVAILABLE'),
                'warning'
            );
            return $text;
        }

        $bbcode = \Kunena\Forum\Libraries\Bbcode\KunenaBbcode::getInstance();
        return $bbcode->parse($text);
    } catch (\Exception $e) {
        $this->app->enqueueMessage(
            Text::_('COM_KUNENATOPIC2ARTICLE_BBCODE_PARSE_ERROR') . ': ' . $e->getMessage(),
            'warning'
        );
        return $text;
    }
}

    /**
     * Генерирует URL открытого поста в Kunena
     */
private function getKunenaPostUrl(int $postId): string
{
    $catid = $this->currentPost->catid ?? 0;
    $thread = $this->currentPost->thread ?? 0;
    return Uri::root() . "forum/{$catid}/{$thread}#{$postId}";
}

private function printHeadOfPost()
{
        // Добавляем в статью инф строку   (не пуста)
           $this->currentArticle->fulltext .= $this->postInfoString;
  //      Factory::getApplication()->enqueueMessage('transferPost инф стр: ' . $this->postInfoString, 'info'); // ОТЛАДКА   
            
          if ($this->params->reminder_lines) {      // Если нужно выводить строки напоминнания
                $this->currentArticle->fulltext .=  $this->reminderLines;    // Добавляем в статью строки напоминания предыдущего поста
             } 
        $this->currentArticle->fulltext .=  '<hr style="width: 50%; height: 1px; background-color: #e0e0e0; margin: 0 auto; border: none;">';        //    Светло-серый
                     
        // return;   в конце void-метода не нужен
    }

} // КОНЕЦ КЛАССА
