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
use Joomla\CMS\Filter\InputFilter;
use Joomla\Component\Content\Site\Helper\RouteHelper;

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
    private string $htmlContent = '';   // Текс поста после BBCode
    public bool $emailsSent = false;
    public array $emailsSentTo = [];

    
      public function __construct($config = [])
{
    parent::__construct($config);
    
    $this->app = Factory::getApplication();
    $this->db = $this->getDatabase();
    
}

    // -------------------------- РАБОТА СО СТАТЬЯМИ -------------------------
    
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
        //    Factory::getApplication()->enqueueMessage('ArticleModel $firstPostId: ' . $firstPostId, 'info'); // ОТЛАДКА          
           
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
        //   Factory::getApplication()->enqueueMessage('createArticlesFromTopic $subject: ' . $this->subject, 'info'); // ОТЛАДКА 
              $this->topicAuthorId = $this->currentPost->userid;

              $this->openArticle();     // Открываем первую статью
                    
               // Основной цикл обработки постов
                while ($this->postId != 0) {
                
                   // Статья открыта
                    Factory::getApplication()->enqueueMessage('Основной цикл Размер статьи: ' . $this->articleSize, 'info'); // ОТЛАДКА  
                    Factory::getApplication()->enqueueMessage('Основной цикл Размер поста: ' . $this->postSize, 'info'); // ОТЛАДКА 
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
       //     Factory::getApplication()->enqueueMessage('createArticlesFromTopic: последняя статья' . $this->subject, 'info'); // ОТЛАДКА 

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
           $this->currentArticle->fulltext .= '<div class="kunenatopic2article_marker" style="display:none;"></div>'; // для плагина подклюсения CSS
           
           $this->currentArticle->fulltext .=  Text::_('COM_KUNENATOPIC2ARTICLE_INFORMATION_SIGN') . '<br />'    // ?? не учтена длина!
                 . Text::_('COM_KUNENATOPIC2ARTICLE_WARNING_SIGN') 
                 . '<div class="kun_p2a_divider-shadow"></div>'; //  Линия с тенью (эффект углубления)
           
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
   
            // 1. Фильтрация контента
            $filter = InputFilter::getInstance([], [], 1, 1);
            $filteredContent = $filter->clean($this->currentArticle->fulltext, 'html');
    
            // 2. Формирование ссылки на CSS
            $cssUrl = Uri::root(true) . '/media/com_kunenatopic2article/css/kun_p2a.css';
            $cssLink = '<link href="' . $cssUrl . '" rel="stylesheet">';
  
     //       Factory::getApplication()->enqueueMessage('closeArticle Добавление CSS:' . $cssLink, 'info'); // ОТЛАДКА 
            // 3. Сборка финального контента
            $this->currentArticle->fulltext = $cssLink . $filteredContent;
    // Factory::getApplication()->enqueueMessage('closeArticle fulltext до createArt' . HTMLHelper::_('string.truncate', $this->currentArticle->fulltext, 100, true, false), 'info'); //ОТЛАДКА true-сохр целые слова, false-не доб многоточие          
            // 4. Создаем статью через Table
            $articleId = $this->createArticleViaTable();

  // Factory::getApplication()->enqueueMessage('closeArticle fulltext после createArt' . HTMLHelper::_('string.truncate', $this->currentArticle->fulltext, 100, true, false),'info'); 
                         
            if (!$articleId) {
                throw new \Exception('Ошибка сохранения статьи.');
            }

            // Формируем URL для статьи
            $link = 'index.php?option=com_content&view=article&id=' . $articleId . '&catid=' . $this->params->article_category;   // Формируем базовый маршрут
            $url = Route::link('site', $link, true, -1);  // Преобразуем в SEF-URL (если SEF включен) : 'site' — гарантирует, что URL будет сформирован для фронтенда
            // Если в глобальных настройках Joomla включены ЧПУ (SEF) и rewrite-правила (например, .htaccess), метод автоматически сгенерирует "красивый" URL, 
            // а если SEF выключен, получится стандартный URL: http://localhost/gchru/index.php?option=com_content&view=article&id=265&catid=57

            // Добавляем ссылку и заголовок в массив для последующего вывода
            $this->articleLinks[] = [
                'title' => $this->currentArticle->title,
                'url' => $url,
                'id' => $articleId
            ];

            // ОТЛАДКА
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
     * Создание статьи через Table API
     * @return  boolean|int  False в случае неудачи, ID статьи в случае успеха
         */
    protected function createArticleViaTable()
    {
        try {
            // Получаем table для контента
            $tableArticle = Table::getInstance('Content');
            
            // Подготавливаем данные 
                $data = [
                'title' => $this->currentArticle->title,
                'alias' => $this->currentArticle->alias,
                'introtext' => '',
                'fulltext' => $this->currentArticle->fulltext, // Используем отфильтрованный контент с добавленным впереди css
                'catid' => (int) $this->params->article_category,
                'created' => (new Date())->toSql(),
                'publish_up' => (new Date())->toSql(),
                'state' => 1, // Published
                'language' => '*',
                'access' => 1,
                'attribs' => '{"show_title":"","link_titles":"","show_tags":""}',
                'metakey' => '',
                'metadesc' => '',
                'metadata' => '{"robots":"","author":"","rights":""}' // Стандартные метаданные
            ];

           if (!$tableArticle->save($data)) {    // Получаем ID созданной статьи в $tableArticle->id
            throw new \Exception($tableArticle->getError());
        }
             
        // --- Запись в #__workflow_associations
         try {
            // Проверяем, есть ли уже запись
           $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__workflow_associations'))
                ->where($this->db->quoteName('item_id') . ' = ' . $this->db->quote($tableArticle->id))
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
                        $this->db->quote($tableArticle->id),
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
            
            return $tableArticle->id;
            
        } catch (\Exception $e) {
            $this->app->enqueueMessage('Ошибка создания статьи через Table: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    // --------------------------- РАБОТА С ПОСТАМИ -------------------
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
 
            $this->postInfoString = $this->createPostInfoString(); // Вычиcляем информационную строку (всегда есть хотя бы разделители) поста
           
            // Вычисляем размер поста (в символах)  ?? Может быть, надо вычислять размер после перекодировки?
           // Расчёт длины с обработкой ошибок
           try {
              $this->postSize = mb_strlen($this->postText, 'UTF-8')
              + mb_strlen($this->postInfoString, 'UTF-8')
              + mb_strlen($this->reminderLines, 'UTF-8');
       //         Factory::getApplication()->enqueueMessage('openPost Размер reminderLines: ' . mb_strlen($this->reminderLines, 'UTF-8'), 'info'); // ОТЛАДКА 
               Factory::getApplication()->enqueueMessage('openPost Размер поста: ' . $this->postSize, 'info'); // ОТЛАДКА 
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
            $this->htmlContent = $this->convertBBCodeToHtml($this->postText);
            
            $this->printHeadOfPost();    // Добавляем в статью инф строку(не пуста) и, если нужно, строки напоминнания ; обязательно ПОСЛЕ Преобразования BBCode
                      
            // Добавляем преобразованный текст в статью
            $this->currentArticle->fulltext .= $this->htmlContent;

           // Вычисляем строки напоминания текущего поста, используются в следующем посте
           if ($this->params->reminder_lines) {   
           $this->reminderLines = HTMLHelper::_('string.truncate', $this->htmlContent, (int)$this->params->reminder_lines);
           Factory::getApplication()->enqueueMessage('transferPost reminderLines: ' . $this->reminderLines, 'info'); // ОТЛАДКА   
           } 
           $this->currentArticle->fulltext .= '<div class="kun_p2a_divider-gray"></div>'; // добавляем линию разделения постов, ?? не учтена в длине статьи!
                        
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
    $this->currentIndex += 1;
    $this->postId = $this->postIdList[$this->currentIndex];
    Factory::getApplication()->enqueueMessage('nextPost Id: ' . $this->postId, 'info'); // ОТЛАДКА       
    return $this->postId; // Автоматически получим 0 в конце
}

 // -------------------------- РАБОТА СО СТРУКТУРОЙ СТАТЕЙ ---------------------
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
            array_push($postIds, 0);    // добавляем элемент 0 в конец массива
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

  // ----------------------- ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ----------------------------------   
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
    $idsString .= ' <a href="' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') 
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

   // Закрываем блок инф строки
   $infoString .= '<br /></div>';   
    
    return $infoString;
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
            
    if ($this->params->reminder_lines && $this->currentPost->parent) {        // Если нужно выводить строки напоминнания
    $this->currentArticle->fulltext .= Text::_('COM_KUNENATOPIC2ARTICLE_START_OF_REMINDER_LINES')
        . '#' . $this->currentPost->parent . ': '
        . '<div class="kun_p2a_reminderLines">' . $this->reminderLines . '</div>';    // Добавляем в статью строки напоминания предыдущего поста  
 }    
        
    $this->currentArticle->fulltext .= '<div class="kun_p2a_divider-gray"></div>';   //    Светло-серый 
                     
        // return;   в конце void-метода не нужен
 }

       /**
     * Преобразование BBCode в HTML
     * @param   string  $text  Текст с BBCode
     * @return  string  HTML-текст
     */
// Простой самописный парсер кл
<?php
// Простой BBCode парсер для замены метода convertBBCodeToHtml

private function convertBBCodeToHtml($text)
{
    try {
        // Сначала обрабатываем attachments (до основных паттернов)
        $text = $this->processAttachments($text);
        
        // Обрабатываем списки (до основных паттернов)
        $text = $this->processLists($text);
        
        // Обрабатываем блоки кода (до основных паттернов, чтобы избежать обработки BBCode внутри кода)
        $text = $this->processCodeBlocks($text);
        
        // Основные теги BBCode (убрали [img] - он обрабатывается отдельно)
        $bbcode_patterns = [
            '/\[b\](.*?)\[\/b\]/is' => '<strong>$1</strong>',
            '/\[i\](.*?)\[\/i\]/is' => '<em>$1</em>',
            '/\[u\](.*?)\[\/u\]/is' => '<u>$1</u>',
            '/\[s\](.*?)\[\/s\]/is' => '<del>$1</del>',
            '/~~(.*?)~~/is' => '<del>$1</del>', // Добавили поддержку ~~текст~~
            '/\[url=(.*?)\](.*?)\[\/url\]/is' => '<a href="$1" target="_blank">$2</a>',
            '/\[url\](.*?)\[\/url\]/is' => '<a href="$1" target="_blank">$1</a>',
            '/\[quote\](.*?)\[\/quote\]/is' => '<blockquote>$1</blockquote>',
            '/\[quote=(.*?)\](.*?)\[\/quote\]/is' => '<blockquote><cite>$1:</cite><br>$2</blockquote>',
            '/\[color=(.*?)\](.*?)\[\/color\]/is' => '<span style="color: $1;">$2</span>',
            '/\[size=(.*?)\](.*?)\[\/size\]/is' => '<span style="font-size: $1%;>$2</span>', // Изменили px на %
            '/\[center\](.*?)\[\/center\]/is' => '<div style="text-align: center;">$1</div>',
            '/\[left\](.*?)\[\/left\]/is' => '<div style="text-align: left;">$1</div>',
            '/\[right\](.*?)\[\/right\]/is' => '<div style="text-align: right;">$1</div>',
            // Если остались обычные [img] теги - обрабатываем их тоже
            '/\[img\](.*?)\[\/img\]/is' => '<img src="$1" alt="" />',
        ];
        
        // Применяем замены
        $html = preg_replace(array_keys($bbcode_patterns), array_values($bbcode_patterns), $text);
        
        // Заменяем переносы строк
        $html = nl2br($html);
        
        return $html;
        
    } catch (\Exception $e) {
        $this->app->enqueueMessage(
            Text::_('COM_KUNENATOPIC2ARTICLE_BBCODE_PARSE_ERROR') . ': ' . $e->getMessage(),
            'warning'
        );
        return $text;
    }
}

// Обработка attachments Kunena
private function processAttachments($text)
{
    // Паттерн для [attachment=945]Fomenko1.jpg[/attachment]
    $pattern = '/\[attachment=(\d+)\](.*?)\[\/attachment\]/i';
    
    return preg_replace_callback($pattern, function($matches) {
        $attachmentId = $matches[1];
        $filename = $matches[2];
        
        // Строим путь к основному изображению
        // Путь формируется как: media/kunena/attachments/{topic_id}/{filename}
        // Но нам нужен attachment_id, поэтому используем более простой подход
        $imagePath = "media/kunena/attachments/{$attachmentId}/{$filename}";
        
        // Проверяем, существует ли файл (опционально)
        $fullPath = JPATH_ROOT . '/' . $imagePath;
        if (!file_exists($fullPath)) {
            // Если файл не найден, возвращаем просто название
            return $filename;
        }
        
        // Возвращаем HTML для изображения (убираем center, он добавляется где-то еще)
        return '<img src="' . $imagePath . '" alt="' . htmlspecialchars($filename) . '" />';
        
    }, $text);
}

// Обработка списков BBCode
private function processLists($text)
{
    // Нумерованные списки [list=1][*]item[*]item[/list]
    $text = preg_replace_callback('/\[list=1\](.*?)\[\/list\]/is', function($matches) {
        $content = $matches[1];
        // Заменяем [*] на <li>, учитывая что после может быть [*] или конец списка
        $items = preg_split('/\[\*\]/', $content);
        $html = '<ol>';
        foreach($items as $item) {
            $item = trim($item);
            if (!empty($item)) {
                $html .= '<li>' . $item . '</li>';
            }
        }
        $html .= '</ol>';
        return $html;
    }, $text);
    
    // Маркированные списки [list][*]item[*]item[/list]
    $text = preg_replace_callback('/\[list\](.*?)\[\/list\]/is', function($matches) {
        $content = $matches[1];
        $items = preg_split('/\[\*\]/', $content);
        $html = '<ul>';
        foreach($items as $item) {
            $item = trim($item);
            if (!empty($item)) {
                $html .= '<li>' . $item . '</li>';
            }
        }
        $html .= '</ul>';
        return $html;
    }, $text);
    
    return $text;
}

// Обработка блоков кода с подсветкой
private function processCodeBlocks($text)
{
    // Обрабатываем "Code:" заголовки (они часто идут перед [code])
    $text = preg_replace('/Code:\s*\n/i', '<div class="code-header">Code:</div>', $text);
    
    // Блоки кода [code][/code] с подсветкой
    $text = preg_replace_callback('/\[code\](.*?)\[\/code\]/is', function($matches) {
        $code = htmlspecialchars(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        return '<pre style="background: #f4f4f4; border: 1px solid #ddd; padding: 10px; overflow-x: auto;"><code style="color: #d14; font-family: monospace;">' . $code . '</code></pre>';
    }, $text);
    
    return $text;
}
  
} // КОНЕЦ КЛАССА
