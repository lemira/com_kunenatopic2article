<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   Copyright (C) 2023 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Filter\InputFilter;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Filter\OutputFilter as FilterOutput;
use Joomla\Component\KunenaTopic2Article\Administrator\Helper\ContentParser;

/**
 * Article Model
 * @since  0.0.1
 */
class ArticleModel extends BaseDatabaseModel
{
    protected $db; // @var \Joomla\Database\DatabaseInterface 
    protected $app; /** @var \Joomla\CMS\Application\CMSApplication */
    protected $currentArticle = null;  
    protected $articleId = 0; // Свойство модели
    private int $articleSize = 0;    // Текущий размер статьи , @var    int 
    private $articleLinks = [];  // Массив ссылок на созданные статьи  @var array 
    private int $postId = 0;   // Текущий ID поста @var    int
    private int $threadId = 0;  // Id темы
    private string $postText = ''; // Текст текущего поста 
    private int $postSize = 0; // Размер текущего поста var    int
    private $postIdList = []; // Список ID постов для обработки @var    array
    private $postLevelList = []; // СоответствующиеID постов уровни вложенности
    private $currentPost = null;  // Текущий пост @var    object
    private string $subject = ''; // Переменная модели для хранения subject
    private $params = null; // Хранение параметров для доступа в других методах
    private int $firstPostId; //  ID первого поста темы
    private int $currentIndex = 0; // первый переход с первого элемента $threadId = $firstPostId (0) на 2-й (1)
    private string $infoString = '';  // строка сборки информационной строки поста в createPostInfoString()
    private string $postInfoString = '';  // Информационная строка поста
    private string $reminderLines = '';  // строки напоминания поста
    private string $title = '';   // Заголовок статьи
    private string $htmlContent = '';   // Текст поста после BBCode
    public bool $emailsSent = false;
    public array $emailsSentTo = [];
    private $allPosts = []; // Добавляем свойство для хранения всех постов
    public bool $isPreview = false;
    private $contentParser = null; // Content parser instance
    
    public function __construct($config = [])
    {
        parent::__construct($config);
        
        $this->app = Factory::getApplication();
        $this->db = $this->getDatabase();
        
        // Инициализируем парсер контента
        $this->initContentParser();
    }
    
    /**
     * Initialize content parser
     */
    private function initContentParser(): void
    {
        $this->contentParser = new ContentParser();
        
        // Настраиваем callback для получения путей к вложениям
        $this->contentParser->setAttachmentCallback(function($attachmentId) {
            return $this->getAttachmentPath($attachmentId);
        });
    }
    
    // -------------------------- РАБОТА СО СТАТЬЯМИ -------------------------
    
    /**
     * Создание статей из темы форума Kunena
     * @param   array  $params  Настройки для создания статей
     * @return  array  Массив ссылок на созданные статьи
     */
   public function createArticlesFromTopic($isPreview = false)
        {  
        $this->isPreview = $isPreview;   // для closeArticle()

        // Триггер загрузки языкового файла компонента
        // Первое обращение к Text::_() для любой константы компонента загружает язык
        Text::_('COM_KUNENATOPIC2ARTICLE_NO_TOPIC_SELECTED');
            
         // Параметры $params получаем из таблицы kunenatopic2article_params
         $this->params = $this->getComponentParams(); 
         if (empty($this->params) || empty($this->params->topic_selection)) {
            throw new \RuntimeException(Text::_('COM_KUNENATOPIC2ARTICLE_NO_TOPIC_SELECTED'));
          }
        $this->articleLinks = []; // Инициализация массива ссылок
         $this->currentArticle = null;     // статья не открыта 
       
        try {
            // Получаем ID первого поста
            $firstPostId = $this->params->topic_selection; 
            $this->firstPostId = $firstPostId;
                //    Factory::getApplication()->enqueueMessage('ArticleModel $firstPostId: ' . $firstPostId, 'info'); // ОТЛАДКА          
            
            $majorParams = $this->getMajorParams($firstPostId);
            $this->threadId = $majorParams['thread'];
            $this->subject = $majorParams['subject'];
            $this->topicAuthorId = $majorParams['userid'];

             // Формируем список ID постов в зависимости от схемы обхода; должно быть получены главные Major параметры первого поста!
            $this->postIdList = $this->buildFlatPostIdList($firstPostId); // Создаем всегда хронологический список (flat нужен для URL постов)
            $this->postIds_time = $this->postIdList; // Исп-ся для URL постов в обоих схемах
            if ($this->params->post_transfer_scheme === 1) { // если flat
               // $this->currentIndex = 0; // для infoString 
                $baum = $this->buildTreePostIdList($firstPostId);
                $this->postIdList = $baum['postIds']; // для Tree меняем $this->postIdList
                $this->postLevelList = $baum['levels'];
                     }
            
              $this->postId = $firstPostId; // текущий id
              $this->openPost($this->postId); // Открываем первый пост темы для доступа к его параметрам
//   Factory::getApplication()->enqueueMessage('createArticlesFromTopic $subject: ' . $this->subject, 'info'); // ОТЛАДКА 
              $this->reminderLines = ""; // у первого поста нет строк напоминания
      
               // для preview - ограничиваем 2 постами 
            if ($isPreview) {
                $this->postIdList = array_slice($this->postIdList, 0, 2);
                $this->postIdList[] = 0; // Гарантируем завершение цикла
            }
            
              $this->currentIndex = 0; // в nextPost() начинаем переход сразу к элементу (1), т.к. (0) = $threadId = $firstPostId
                    
              $this->openArticle();     // Открываем первую статью
                    
               // Основной цикл обработки постов
                while ($this->postId != 0) {
                
                // Статья открыта
               if (!$isPreview &&    // в preview пропускаем проверку размера
                            $this->articleSize + $this->postSize > $this->params->max_article_size &&  // С новым постом превышен максимальный размер статьи
                            $this->articleSize != 0) {                                           // И статья не пустая = размер этого поста больше размера статьи
                            $this->closeArticle();  // Закрываем текущую статью перед открытием новой
                            $this->openArticle();   // Открываем новую статью
                }    

                $this->transferPost(); // Переносим содержимое поста в статью
                $this->nextPost(); // Переходим к следующему посту
                $this->openPost($this->postId); // Открываем пост для доступа к его параметрам, не открываем пост после последнего
            }      // Конец основного цикла обработки постов

            // Закрываем последнюю статью
           $previewData = null;
            if ($this->currentArticle !== null) {
                $result = $this->closeArticle();
                    if ($this->isPreview && is_array($result)) {
                        $previewData = $result;
                    }
            }
            // ОТЛАДКА   Factory::getApplication()->enqueueMessage('createArticlesFromTopic: последняя статья' . $this->subject, 'info');  
           
            if ($this->isPreview) {
                return $previewData ?: []; // возвращаем данные или пустой массив
            }
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
           $this->articleId = 0; // Сбрасываем при открытии новой статьи    
           $this->articleSize = 0;   // Сбрасываем текущий размер статьи
           $this->currentArticle->fulltext = ''; // для возможного изменения строк предупреждения
           
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
            $baseAlias = FilterOutput::stringURLSafe($this->title); // дж
            $uniqueAlias = $this->getUniqueAlias($baseAlias);
            $this->currentArticle->alias = $uniqueAlias;
          // Отладка  $this->app->enqueueMessage('openArticle Статья открыта: ' . $this->title . ', категория: ' . $this->params->article_category . ', alias: ' . $uniqueAlias, 'notice');

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
           // 1. Контент уже очищен BBCode парсером, дополнительная фильтрация не нужна
           $filteredContent = $this->currentArticle->fulltext;

            // 2. Формирование ссылки на CSS
           HTMLHelper::_('stylesheet', 'com_kunenatopic2article/css/kun_p2a.css', ['relative' => true]);
           $cssLink = '<link href="' . Uri::root(true) . '/media/com_kunenatopic2article/css/kun_p2a.css" rel="stylesheet">'; // для Сборки финального контента
     //       Factory::getApplication()->enqueueMessage('closeArticle Добавление CSS:' . $cssLink, 'info'); // ОТЛАДКА 
            // 3. Сборка финального контента
            $this->currentArticle->fulltext = $cssLink . $filteredContent;
    // Factory::getApplication()->enqueueMessage('closeArticle fulltext до createArt' . HTMLHelper::_('string.truncate', $this->currentArticle->fulltext, 100, true, false), 'info'); //ОТЛАДКА true-сохр целые слова, false-не доб многоточие          
          
            // 4. Создаем статью через Table
            $this->articleId = $this->createArticleViaTable();
  // Factory::getApplication()->enqueueMessage('closeArticle fulltext после createArt' . HTMLHelper::_('string.truncate', $this->currentArticle->fulltext, 100, true, false),'info'); 
                         
            if (!$this->articleId) {
                throw new \Exception('Ошибка сохранения статьи.');
            }

            if ($this->isPreview) {
            // Для preview возвращаем в createArticlesFromTopic() данные из URL статьи ниже
                return [
                'id' => $this->articleId,
                'alias' => $this->currentArticle->alias,
                'catid' => $this->params->article_category,
                 ];
            }
            
            // Формируем URL для статьи
            $link = 'index.php?option=com_content&view=article&id=' . $this->articleId . '&catid=' . $this->params->article_category;   // Формируем базовый маршрут
            $url = Route::link('site', $link); //фронтэнд, SEF включён: /article-slug, Для фронтенда, & остаётся, В Kunena SEF-правила, Фавикон

            // Добавляем ссылку и заголовок в массив для последующего вывода
            $this->articleLinks[] = [
                'title' => $this->currentArticle->title,
                'url' => $url,
                'id' => $this->articleId  // Сохраняем ID в массиве ссылок
                ];
           // ОТЛАДКА           $this->app->enqueueMessage('Статья успешно сохранена с ID: ' . $this->articleId, 'notice');

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
                'state' => 1, // Published 
                'created' => (new Date())->toSql(),
                'publish_up' => (new Date())->toSql(),
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
               ->select($this->db->quoteName([ 'id', 'subject', 'thread', 'userid', 'parent', 'name', 'time', 'catid' ])) // только используемые поля
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

            // Проверяем, найден ли текст   // НЕ НУЖНО?
            if ($this->postText === null) {
                throw new \Exception(Text::sprintf('COM_KUNENATOPIC2ARTICLE_POST_TEXT_NOT_FOUND', $postId));
            }
 
            $this->postInfoString = $this->createPostInfoString(); // Вычиcляем информационную строку (всегда есть хотя бы разделители) поста
           
            // Вычисляем размер поста (в символах)  ?? Может быть, надо вычислять размер после перекодировки?
           // Расчёт длины с обработкой ошибок
           try {
              $this->postSize = mb_strlen($this->postText, 'UTF-8')
              + mb_strlen($this->postInfoString, 'UTF-8')
              + mb_strlen($this->reminderLines, 'UTF-8');
        // ОТЛАДКА        Factory::getApplication()->enqueueMessage('openPost Размер поста: ' . $this->postSize, 'info'); // ОТЛАДКА 
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
            // Преобразуем BBCode в HTML через парсер
            $this->htmlContent = $this->convertBBCodeToHtml($this->postText);
            
            $this->printHeadOfPost();    // Добавляем в статью инф строку(не пуста) и, если нужно, строки напоминнания ; обязательно ПОСЛЕ Преобразования BBCode
                      
            // Добавляем преобразованный текст в статью
            $this->currentArticle->fulltext .= $this->htmlContent;

           // Вычисляем строки напоминания текущего поста, используются в следующем посте
           if ($this->params->reminder_lines) {   
          // Вместо простого truncate, используем функцию очистки
          $reminderLinesLength = (int)$this->params->reminder_lines;
         
        $this->reminderLines = $this->processReminderLines($this->htmlContent, $reminderLinesLength); // обработка ссылок и рис-в и обрезание 
 // Factory::getApplication()->enqueueMessage('transferPost reminderLines: ' . $this->reminderLines, 'info'); // ОТЛАДКА   
           } 
           $this->currentArticle->fulltext .= '<div class="kun_p2a_divider-gray"></div>'; // добавляем линию разделения постов, ?? не учтена в длине статьи!
                        
            // Обновляем размер статьи. $this-postSize включает длину инф строки и строки напоминания, вычислен в openPost
            $this->articleSize += $this->postSize;
// Factory::getApplication()->enqueueMessage('transferPost Размер статьи: ' . $this->articleSize, 'info'); // ОТЛАДКА   
            return true;
        } catch (\Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * Process reminder lines using ContentParser
     */
    private function processReminderLines(string $htmlContent, int $reminderLinesLength): string
    {
        if (!$this->contentParser) {
            return '';
        }
        
        return $this->contentParser->processReminderLines($htmlContent, $reminderLinesLength);
    }
    
    /**
     * Переход к следующему посту
     * @return  int  ID следующего поста или 0, если больше нет постов
     */
   private function nextPost()
{
    $this->currentIndex += 1;
    $this->postId = $this->postIdList[$this->currentIndex];
  // ОТЛАДКА    Factory::getApplication()->enqueueMessage('nextPost Id: ' . $this->postId, 'info'); // ОТЛАДКА       
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
      $this->postIds = $this->getAllThreadPosts($this->threadId); // Получаем массив постов темы   
      sort($this->postIds); // Сортируем массив постов по возрастанию id (= по времени создания)
      array_push($this->postIds, 0);    // добавляем элемент 0 в конец массива
      return $this->postIds; 
    }

   private function getAllThreadPosts($threadId)           
     {
     // Получаем все посты темы
    $query = $this->db->getQuery(true)
    ->select($this->db->quoteName('id'))
    ->from($this->db->quoteName('#__kunena_messages'))
    ->where($this->db->quoteName('thread') . ' = ' . $this->threadId) 
    ->where($this->db->quoteName('hold') . ' = 0');

   // --- НАЧАЛО БЛОКА ДЛЯ ИСКЛЮЧЕНИЯ АВТОРОВ дж --- 
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
         $query->order($this->db->quoteName('id') . ' ASC');
         $postIds = $this->db->setQuery($query)->loadColumn();
   // ОТЛАДКА  Factory::getApplication()->enqueueMessage('Массив ID постов: ' . print_r($postIds, true), 'info'); 
   
            return $postIds;
  }
    
/**
 * Построение списков ID постов и их уровней для древовидного обхода
 * @param   int  $firstPostId  ID первого поста темы
 * @return  array  Массив с двумя списками: ['postIds' => [...], 'levels' => [...]]
 */
private function buildTreePostIdList($firstPostId)
{
    try {
        // 1. Получаем ВСЕ посты темы (включая hold>0) ТОЛЬКО ДЛЯ ПОСТРОЕНИЯ СВЯЗЕЙ
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName(['id', 'parent', 'hold']))
            ->from($this->db->quoteName('#__kunena_messages'))
            ->where($this->db->quoteName('thread') . ' = ' . $this->threadId);
        
        $allPosts = $this->db->setQuery($query)->loadObjectList();
        
        // 2. ОТДЕЛЬНО получаем посты для финального списка (только hold=0)
        $finalPostIds = $this->getAllThreadPosts($this->threadId); 
        
        // 3. Строим полную карту связей из ВСЕХ постов
        $fullChildrenMap = [];
        foreach ($allPosts as $post) {
            if ($post->parent > 0) {
                $fullChildrenMap[$post->parent][] = $post->id;
            }
        }
        
        // 4. Восстанавливаем связи для потомков удаленных постов
        $recoveredChildren = [];
        
        foreach ($allPosts as $post) {
            // Если пост в финальном списке И его родитель удален
            if (in_array($post->id, $finalPostIds) && 
                $post->parent > 0 && 
                !in_array($post->parent, $finalPostIds)) {
                
                // Находим нового родителя: поднимаемся по цепочке пока не найдем существующего
                $newParent = $this->findClosestExistingParent($post->parent, $finalPostIds, $allPosts);
                
                if ($newParent > 0) {
                    $recoveredChildren[$newParent][] = $post->id;
                } else {
                    // Если не нашли существующего родителя в цепочке - прикрепляем к корню
                    $recoveredChildren[$firstPostId][] = $post->id;
                }
            }
        }
        
        // 5. Объединяем восстановленные связи с обычными
        $children = [];
        foreach ($finalPostIds as $postId) {
            if ($postId == 0) continue;
            
            $children[$postId] = [];
            
            // Обычные дети
            if (isset($fullChildrenMap[$postId])) {
                foreach ($fullChildrenMap[$postId] as $childId) {
                    if (in_array($childId, $finalPostIds)) {
                        $children[$postId][] = $childId;
                    }
                }
            }
            
            // Восстановленные дети
            if (isset($recoveredChildren[$postId])) {
                $children[$postId] = array_merge($children[$postId], $recoveredChildren[$postId]);
            }
            
            // Сортируем и убираем дубликаты
            if (!empty($children[$postId])) {
                $children[$postId] = array_unique($children[$postId]);
                sort($children[$postId]);
            } else {
                $children[$postId] = [0];
            }
        }
        
        // 6. Выполняем обход дерева
        $postIdList = [];
        $postLevelList = [];
        
        $this->traverseTree($firstPostId, 0, $children, $postIdList, $postLevelList);
        
        return [
           'postIds' => array_merge($postIdList, [0]),
            'levels' => $postLevelList
        ];
        
    } catch (\Exception $e) {
        $this->app->enqueueMessage('Ошибка построения древовидного обхода: ' . $e->getMessage(), 'error');
        return [
            'postIds' => [$firstPostId, 0],
            'levels' => [0, 0]
        ];
    }
}

/**
 * Находим ближайшего существующего родителя в цепочке
 */
private function findClosestExistingParent($deletedParentId, $finalPostIds, $allPosts)
{
    $postMap = [];
    foreach ($allPosts as $post) {
        $postMap[$post->id] = $post;
    }
    
    $currentId = $deletedParentId;
    
    // Поднимаемся по цепочке родителей
    while (isset($postMap[$currentId])) {
        $currentPost = $postMap[$currentId];
        
        // Если нашли существующего родителя - возвращаем его
        if (in_array($currentPost->id, $finalPostIds)) {
            return $currentPost->id;
        }
        
        // Переходим к следующему родителю
        if ($currentPost->parent > 0) {
            $currentId = $currentPost->parent;
        } else {
            break; // Достигли корня
        }
    }
    
    return 0; // Не нашли существующего родителя
}
    
/**
 * Рекурсивный обход дерева в глубину
 * @param   int    $postId         Текущий пост
 * @param   int    $level          Текущий уровень
 * @param   array  $children       Массив связей родитель-дети
 * @param   array  &$postIdList    Результирующий список ID (по ссылке)
 * @param   array  &$postLevelList Результирующий список уровней (по ссылке)
 */
private function traverseTree($postId, $level, $children, &$postIdList, &$postLevelList)
{
    // Добавляем текущий пост
    $postIdList[] = $postId;
    $postLevelList[] = $level;
    
    // Если у поста есть дети
    if (isset($children[$postId]) && $children[$postId][0] !== 0) {
        foreach ($children[$postId] as $childId) {
            // Рекурсивно обходим каждого ребенка
            $this->traverseTree($childId, $level + 1, $children, $postIdList, $postLevelList);
        }
    }
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

    $infoString = HTMLHelper::_('content.prepare', '<div class="kun_p2a_ids kun_p2a_index_line text-center">');
    
    // IDs постов
    if ($this->params->post_ids) {
        $idsString = '';
        
        if ($this->params->kunena_post_link) {
            $postUrl = $this->getKunenaPostUrl($this->currentPost->id);
            // ЕСЛИ URL ПУСТОЙ, НЕ ДЕЛАЕМ ССЫЛКУ
            if (!empty($postUrl)) {
                $idsString .= ' <a href="' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') 
                           . '" target="_blank" rel="noopener noreferrer">#' 
                           . $this->currentPost->id . '</a>';
            } else {
                $idsString .= '#' . $this->currentPost->id;
            }
        } else {
            $idsString .= '#' . $this->currentPost->id;
        }
        
        if (!empty($this->currentPost->parent)) {
            if ($this->params->kunena_post_link) {
                $parentUrl = $this->getKunenaPostUrl($this->currentPost->parent);
                // ЕСЛИ URL ПУСТОЙ, НЕ ДЕЛАЕМ ССЫЛКУ
                if (!empty($parentUrl)) {
                    $idsString .= ' ⟸ <a href="' . htmlspecialchars($parentUrl, ENT_QUOTES, 'UTF-8') 
                               . '" target="_blank" rel="noopener noreferrer">#' 
                               . $this->currentPost->parent . '</a>';
                } else {
                    $idsString .= ' ⟸ #' . $this->currentPost->parent;
                }
            } else {
                $idsString .= ' ⟸ #' . $this->currentPost->parent;
            }
        }
        $infoString .= $idsString;
    }
    
    $infoString .= '</div>'; // Закрываем блок IDs
    
    // Основная информационная строка
    $infoString .= '<div class="kun_p2a_info_main text-center">';
    
    if ($this->params->post_author) {
        $infoString .= htmlspecialchars($this->currentPost->name, ENT_QUOTES, 'UTF-8');
    }
    
    if ($this->params->post_title) {
        $infoString .= ' / <span class="kun_p2a_post_subject">' . htmlspecialchars($this->currentPost->subject, ENT_QUOTES, 'UTF-8') . '</span>';
        
        if ($this->params->post_transfer_scheme == 1) {
            if ($this->postId != $this->firstPostId) {
                $infoString .= ' / ' . htmlspecialchars("\u{1F332}", ENT_QUOTES, 'UTF-8') . $this->postLevelList[$this->currentIndex];
            }                                          
        }    
    }
    
    if ($this->params->post_creation_date) {
        $date = date('d.m.Y', $this->currentPost->time);
        $infoString .= ' / ' . $date;
        
        if ($this->params->post_creation_time) {
            $time = date('H:i', $this->currentPost->time);
            $infoString .= ' ' . $time;
        }
    }

    $infoString .= '</div>'; // Закрываем основную инфо строку
    
    return $infoString;
}

private function printHeadOfPost()
{
    // Добавляем в статью инф строку
    $this->currentArticle->fulltext .= $this->postInfoString;

    if ($this->params->reminder_lines && $this->currentPost->parent) {
        $reminderText = '<div class="kun_p2a_reminder_content" data-tooltip="' 
            . Text::_('COM_KUNENATOPIC2ARTICLE_START_OF_REMINDER_LINES') . '">'
            . '<span class="tooltip-icon">ⓘ</span> '
            . $this->reminderLines 
            . '</div>';
            
        $this->currentArticle->fulltext .= $reminderText;
    }
    
    $this->currentArticle->fulltext .= '<div class="kun_p2a_divider-gray"></div>';
}
    
/**
 * Генерируем полный URL для конкретного поста в Kunena, используя SEF-совместимые slug-и.
 *
 * @param int $postId ID поста в Kunena
 * @return string Полный URL поста
 */
public function getKunenaPostUrl(int $postId): string
{
    // ПРОВЕРКА: если пост не существует в обработанном списке, возвращаем пустую строку
    if (!in_array($postId, $this->postIds_time)) {
        return '';
    }
    
    $postsPerPage = $this->getKunenaPostsPerPage();
    
    // --- Данные поста ---
    $query = $this->db->getQuery(true)
        ->select('m.catid, m.thread')
        ->from('#__kunena_messages AS m')
        ->where('m.id = ' . (int) $postId);

    $this->db->setQuery($query);
    $post = $this->db->loadObject();
    
    // ДОПОЛНИТЕЛЬНАЯ ПРОВЕРКА: если пост не найден, возвращаем пустую строку
    if (!$post) {
        return '';
    }
    
    $catid  = (int) $post->catid;
    $thread = (int) $post->thread;
    
    // --- Slug'и ---
    $catAlias = $this->db->setQuery(
        $this->db->getQuery(true)->select('alias')->from('#__kunena_categories')->where('id = ' . $catid)
    )->loadResult() ?: 'category';
    
    $topicSubject = $this->db->setQuery(
        $this->db->getQuery(true)->select('subject')->from('#__kunena_topics')->where('id = ' . $thread)
    )->loadResult();
    $topicAlias = FilterOutput::stringURLSafe($topicSubject) ?: 'topic';
    $topicSlug = "{$thread}-{$topicAlias}";
    
    // --- Находим позицию в хронологическом списке ---
    $position = array_search($postId, $this->postIds_time);
    
    // Вычисляем start
    $start = floor($position / $postsPerPage) * $postsPerPage;
    
    // --- Формируем URL ---
    $fullUrl = Uri::root() . "forum/{$catAlias}/{$topicSlug}" . "?start={$start}" . "#{$postId}";
    return $fullUrl;
}
    
    /**
 * Получаем количество сообщений, отображаемых на одной странице темы Kunena,
 * с обработкой ошибок и выводом сообщения в админке.
 *
 * @return int Количество сообщений на странице.
 */
protected function getKunenaPostsPerPage(): int
{
    // Безопасное значение по умолчанию (Fallback)
    $defaultPostsPerPage = 20; 
    
    try {
        // Получаем необходимые объекты через Factory
        $db = Factory::getDbo();
        $app = Factory::getApplication();
        $tableName = '#__kunena_configuration'; 
        
        $query = $db->getQuery(true)
            ->select($db->qn('params'))
            ->from($db->qn($tableName));
            
        $db->setQuery($query, 0, 1);
        $jsonParams = $db->loadResult();

        if (empty($jsonParams)) {
            // Сообщение, если строка конфигурации не найдена
            $app->enqueueMessage(
                'Ошибка: Не удалось найти параметры Kunena в таблице ' . $tableName, 
                'warning'
            );
            return $defaultPostsPerPage;
        }

        $params = new Registry($jsonParams);
        $postsPerPage = $params->get('messagesPerPage');
        
        // Проверка корректности полученного значения
        if (is_numeric($postsPerPage) && (int) $postsPerPage > 0) {
            return (int) $postsPerPage;
        } else {
             // Сообщение, если значение некорректно
            $app->enqueueMessage(
                'Ошибка: Некорректное значение "messagesPerPage" ("' . $postsPerPage . '") в конфигурации Kunena.', 
                'warning'
            );
            return $defaultPostsPerPage;
        }

    } catch (\Exception $e) {
        // Ловим любые исключения (ошибка БД, парсинга и т.д.) и выводим фидбэк
        Factory::getApplication()->enqueueMessage(
            'Критическая ошибка при получении настройки Kunena: ' . $e->getMessage(), 
            'error'
        );
        
        // Возвращаем безопасное значение для предотвращения ошибки деления на ноль
        return $defaultPostsPerPage; 
    }
}
     
    /**
 * Отправка email-уведомлений о созданных статьях
 * @param   array  $articleLinks  Массив ссылок на статьи
 * @return  array  Результат отправки (success, recipients)
* Пример ошибки: ['success' => false, 'recipients' => ['admin@site.com'], 'error' => 'SMTP Error...']
 */
public function sendLinksToAdministrator(array $articleLinks): array
{
    $app = Factory::getApplication();
    $result = [
        'success'    => false,
        'recipients' => [],
        'error'      => null,
    ];

    try {
        $config = Factory::getConfig();
        $mailer = Factory::getMailer();

        // 1. Получаем email-адреса
        $adminEmail = $config->get('mailfrom'); // $adminEmail здесь - адрес сайта (отправитель)
        $author = Factory::getUser($this->topicAuthorId);
        $authorEmail = $author->email;

        // 2. Фильтруем адреса, оставляя только валидные и непустые
        $rawRecipients = [$adminEmail, $authorEmail];
        $recipients = array_unique(array_filter($rawRecipients, function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        }));

        // Если после фильтрации не осталось ни одного получателя, прекращаем работу.
        if (empty($recipients)) {
            $result['error'] = 'Не найдены корректные email-адреса для отправки.';
            // success остается false, так как отправка не производилась.
            return $result;
        }

        // 3. Формируем тело и тему письма
        $subject = Text::sprintf('COM_KUNENATOPIC2ARTICLE_MAIL_SUBJECT', $config->get('sitename'));
        $body = Text::sprintf(
            'COM_KUNENATOPIC2ARTICLE_MAIL_BODY',
            $config->get('sitename'),
            $this->subject,
            Uri::root() . 'index.php?option=com_kunena&view=topic&postid=' . (int)$this->params->topic_selection,
            $author->name,
            implode("\n", array_map(
                fn($link) => "- {$link['title']}: {$link['url']}",
                $articleLinks
            ))
        );

        // 4. Настраиваем объект Mailer
        $mailer->setSender([$adminEmail, $config->get('sitename')]);
        $mailer->setSubject($subject);
        $mailer->setBody($body);
        $mailer->isHtml(false);

        foreach ($recipients as $email) {
            $mailer->addRecipient($email);
        }

        // 5. ПРОВЕРКА ОКРУЖЕНИЯ: Локальный сервер или реальный
        $isLocalServer = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);

        if ($isLocalServer) {
            // Мы на WAMP (или другом локальном сервере)
            // Имитируем успешную отправку для отладки, но не отправляем письмо.
            $app->enqueueMessage('Режим отладки: отправка почты пропущена (локальный сервер).', 'notice');
            $result['success'] = true;
        } else {
            // Мы на реальном сервере. Пытаемся отправить письмо.
            // Если здесь произойдет ошибка, выполнение перейдет в блок catch.
            $mailer->Send();
            $result['success'] = true; // Успех, если Send() не выбросил исключение
        }

        // Код ниже выполнится в случае успеха (реального или имитированного)
        $result['recipients'] = $recipients;
        $this->emailsSent = true;
        $this->emailsSentTo = $recipients;

    } catch (\Exception $e) {
        // Этот блок кода выполнится ТОЛЬКО в случае ошибки на РЕАЛЬНОМ сервере

        // Формируем сообщение об ошибке для администратора
        $errorMessage = Text::sprintf('COM_KUNENATOPIC2ARTICLE_MAIL_SEND_ERROR', $e->getMessage());
        $app->enqueueMessage($errorMessage, 'error');

        // Заполняем результат информацией о провале
        $result['success'] = false;
        $result['error'] = $e->getMessage(); // Сохраняем техническую информацию об ошибке
        $result['recipients'] = $recipients; // Сохраняем, кому мы пытались отправить письмо

        // Логируем ошибку для будущего анализа (рекомендуется)
        // Factory::log($e->getTraceAsString(), 'error', 'com_kunenatopic2article');

        // Обновляем состояние модели
        $this->emailsSent = false;
        $this->emailsSentTo = []; // или $recipients
    }

    // Возвращаем итоговый массив с результатом операции
    return $result;
}

    // ПАРСЕР - УПРОЩЕННЫЙ ВАРИАНТ
    /**
     * Get attachment path from database
     */
    private function getAttachmentPath($attachmentId)
    {
        try {
            $db = $this->getDatabase();
            $query = $db->getQuery(true)
                ->select(['folder', 'filename', 'filename_real'])
                ->from('#__kunena_attachments')
                ->where('id = ' . (int)$attachmentId);
            
            $db->setQuery($query);
            $attachment = $db->loadObject();
            
            if ($attachment) {
                $imagePath = $attachment->folder . '/' . $attachment->filename;
                
                if (file_exists(JPATH_ROOT . '/' . $imagePath)) {
                    return $imagePath;
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
           return null;
        }
    }
   
   /**
     * Convert BBCode to HTML using ContentParser
     */
   private function convertBBCodeToHtml($text)
   {
        if (!$this->contentParser) {
            $this->initContentParser();
        }
        
        return $this->contentParser->convertBBCodeToHtml($text, $this->params);
   }
    
   // ------- КОНЕЦ ПАРСЕРА ---------
    
/**
 * Удаляет статью предпросмотра по ID
 * 
 * @param int $id ID статьи для удаления
 * @return bool True при успешном удалении, false при ошибке
 */
public function deletePreviewArticleById($id)
{
    try {
        $db = $this->getDatabase();
        
        // ПРОСТО УДАЛЯЕМ СТАТЬЮ БЕЗ ПРОВЕРКИ АЛИАСА
        // (в preview мы всегда передаем правильный ID)
        $query = $db->getQuery(true)
            ->delete('#__content')
            ->where('id = ' . (int) $id);
        
        $db->setQuery($query);
        $result = $db->execute();
        
        if ($result) {
            // Также удаляем запись из workflow_associations, если она есть
            try {
                $query = $db->getQuery(true)
                    ->delete('#__workflow_associations')
                    ->where('item_id = ' . (int) $id)
                    ->where('extension = ' . $db->quote('com_content.article'));
                
                $db->setQuery($query);
                $db->execute();
            } catch (\Exception $e) {
                // Игнорируем ошибки при удалении из workflow_associations
                // (возможно, таблицы нет или запись уже удалена)
            }
            
      //      error_log('Successfully deleted preview article with ID: ' . $id);
            return true;
        } else {
      //      error_log('Failed to delete article with ID: ' . $id);
            return false;
        }
        
    } catch (\Exception $e) {
          return false;
    }
}

    /**
     * Получение параметров компонента из таблиц
     * @return  object|null  Объект с параметрами компонента
     */
    private function getComponentParams()
{
    try {
        $db = Factory::getContainer()->get('DatabaseDriver');
        
        // Сначала проверяем, существует ли таблица
        $tables = $db->getTableList();
        $tableName = $db->getPrefix() . 'kunenatopic2article_params';
        
        if (!in_array($tableName, $tables)) {
            // Таблица не существует - создаем её
            $this->createParamsTable();
        }
        
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__kunenatopic2article_params'))
            ->where($db->quoteName('id') . ' = 1');
        
        $params = $db->setQuery($query)->loadObject();
        
        if (!$params) {
            Factory::getApplication()->enqueueMessage(
                Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_NOT_FOUND'), 
                'error'
            );
            return null;
        }
        
        return $params;
    } catch (\Exception $e) {
        Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        return null;
    }
}

/**
 * Создание таблицы параметров
 */
private function createParamsTable()
{
    try {
        $db = Factory::getContainer()->get('DatabaseDriver');
        
       $createQuery = "CREATE TABLE IF NOT EXISTS `#__kunenatopic2article_params` (
            `id` int NOT NULL AUTO_INCREMENT,
            `topic_selection` int NOT NULL DEFAULT 0,
            `article_category` int NOT NULL DEFAULT 0,
            `post_transfer_scheme` int NOT NULL DEFAULT 1,
            `max_article_size` int NOT NULL DEFAULT 40000,
            `post_author` int NOT NULL DEFAULT 1,
            `post_creation_date` int NOT NULL DEFAULT 0,
            `post_creation_time` int NOT NULL DEFAULT 0,
            `post_ids` int NOT NULL DEFAULT 0,
            `post_title` int NOT NULL DEFAULT 0,
            `kunena_post_link` int NOT NULL DEFAULT 0,
            `reminder_lines` int NOT NULL DEFAULT 0,
            `ignored_authors` text,
            PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $db->setQuery($createQuery);
        $db->execute();
        
        // Добавляем начальные данные
        $insertQuery = "INSERT IGNORE INTO `#__kunenatopic2article_params` 
                        (`id`, `topic_selection`, `article_category`, `post_transfer_scheme`, `max_article_size`, `post_author`, `post_creation_date`, `post_creation_time`, `post_ids`, `post_title`, `kunena_post_link`, `reminder_lines`, `ignored_authors`)
                        VALUES (1, 0, 0, 1, 40000, 1, 0, 0, 0, 0, 0, 0, '')";
        
        $db->setQuery($insertQuery);
        $db->execute();
        
        Factory::getApplication()->enqueueMessage('Таблица параметров создана успешно', 'success');
        
    } catch (\Exception $e) {
        throw new \Exception('Ошибка создания таблицы параметров: ' . $e->getMessage());
    }
}

public function getMajorParams($postId)
{
    $query = $this->db->getQuery(true)
        ->select([
            $this->db->quoteName('thread'),
            $this->db->quoteName('subject'), 
            $this->db->quoteName('userid')
        ])
        ->from($this->db->quoteName('#__kunena_messages'))
        ->where($this->db->quoteName('id') . ' = ' . (int)$postId);
    
    $this->db->setQuery($query);
    $result = $this->db->loadObject();
    
    return [
        'thread' => $result->thread,
        'subject' => $result->subject,
        'userid' => $result->userid
    ];
}    
    
} // КОНЕЦ КЛАССА
