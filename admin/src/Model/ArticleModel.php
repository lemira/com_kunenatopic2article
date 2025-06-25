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
use Kunena\Forum\Libraries\Bbcode\KunenaBbcode;

/**
 * Article Model
 * @since  0.0.1
 */
class ArticleModel extends BaseDatabaseModel
{
    protected $db; // @var \Joomla\Database\DatabaseInterface 
    protected $app; /** @var \Joomla\CMS\Application\CMSApplication */
    protected $kunenaApiAvailable = false;
    private $currentArticle = null;  
    private $articleSize = 0;    // Текущий размер статьи , @var    int 
    private $articleLinks = [];  // Массив ссылок на созданные статьи  @var array 
    private $postId = 0;   // Текущий ID поста @var    int
    private $postText = ''; // Текст текущего поста 
    private $postSize = 0; // Размер текущего поста var    int
    private $postIdList = []; // Список ID постов для обработки @var    array
    private $currentPost = null;  // Текущий пост @var    object
    private $subject = ''; // Переменная модели для хранения subject
    private $topicAuthorId = ''; // Переменная модели для хранения Id автора
    private $params = null; // Хранение параметров для доступа в других методах
    private $currentIndex = 0; // первый переход с первого элемента $topicId = $firstPostId (0) на 2-й (1)
    private $postInfoString = '';  // Информационная строка поста
   
        public function __construct($config = [])
    {
        parent::__construct($config);
        $this->app = Factory::getApplication();
         $this->db = $this->getDatabase(); // Оптимизировано для J5
    }

    /**
     * Создание статей из темы форума Kunena
     * @param   array  $params  Настройки для создания статей
     * @return  array  Массив ссылок на созданные статьи
     */
    public function createArticlesFromTopic($params)
    {   // Параметры $params получены в контроллере из таблицы kunenatopic2article_params; копию функции можно взять из контроллера
        $this->params = $params; 
        // Инициализация массива ссылок
        $this->articleLinks = [];
        try {
              
            // Получаем ID первого поста
            $firstPostId = $params->topic_selection; // 3232
            Factory::getApplication()->enqueueMessage('ArticleModel $firstPostId: ' . $firstPostId, 'info'); // ОТЛАДКА          
           
            $data = $this->getTopicSubject($firstPostId);    // Возвращаем массив
            $this->subject = $data['subject'];
           Factory::getApplication()->enqueueMessage('ArticleModel $subject: ' . $this->subject, 'info'); // ОТЛАДКА 
            $this->topicAuthorId = $data['topicAuthorId'];
            
            // Формируем список ID постов в зависимости от схемы обхода
            if ($this->params->post_transfer_scheme != 1) {
                $this->postIdList = $this->buildFlatPostIdList($firstPostId);
                } else {
                $this->postIdList = $this->buildTreePostIdList($firstPostId);
                }

              $this->postId = $firstPostId; // текущий id 
            
               // Основной цикл обработки постов
                while ($this->postId != 0) {
                $this->openPost($this->postId); // Открываем пост для доступа к его параметрам

                if ($this->currentArticle === null){     // Если статья не открыта 
                    $this->openArticle();     // Открываем новую статью
                    }
                   
                    // Если статья уже открыта
                if ($this->articleSize + $this->postSize > $this->params->max_article_size) {
                    $this->closeArticle();     // закрываем её перед открытием новой
                    $this->openArticle();   // Открываем новую статью
                    }
            
                $this->transferPost(); // Переносим содержимое поста в статью
                $this->nextPost(); // Переходим к следующему посту
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
           $this->currentArticle->fulltext = '';
              
            // Формируем базовый заголовок статьи
            $title = $this->subject;
            // Если это не первая статья, добавляем номер части
            if (!empty($this->articleLinks)) {
                $partNum = count($this->articleLinks) + 1;
                $title .= ' - ' . Text::sprintf('COM_KUNENATOPIC2ARTICLE_PART_NUMBER', $partNum);
            }
            $this->currentArticle->title = $title;
           
            // Формируем уникальный алиас
            $baseAlias = OutputFilter::stringURLSafe($title);
            $uniqueAlias = $this->getUniqueAlias($baseAlias);
            $this->currentArticle->alias = $uniqueAlias;
              
            // Сбрасываем текущий размер статьи
            $this->articleSize = 0;

            // Отладка
            $this->app->enqueueMessage('openArticle Статья открыта: ' . $title . ', категория: ' . $this->params->article_category . ', alias: ' . $uniqueAlias, 'notice');

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
                'introtext' => $this->currentArticle->introtext ?? '',
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
        try {
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

            // Вычисляем размер поста (в символах)
            $this->postSize = mb_strlen($this->postText, 'UTF-8');
            
            Factory::getApplication()->enqueueMessage('openPost Размер поста: ' . $this->postSize, 'info'); // ОТЛАДКА          

             // Вычиcляем информационную строку (всегда есть хотя бы разделители) в статью)
           $this->$postInfoString = $this->createPostInfoString();       
            // Добавляем размер информационной строки (в символах)
            $this->postSize .= mb_strlen($this->$postInfoString, 'UTF-8');
            
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
        if ($this->currentArticle === null || $this->currentPost === null) {
            return false;
        }

        try {
           // Добавляем в статью инф строку
           $this->currentArticle->fulltext .= $this->$postInfoString;
            
           // Преобразуем BBCode в HTML
            $htmlContent = $this->convertBBCodeToHtml($this->postText);
            
            // Добавляем преобразованный текст в статью
            $this->currentArticle->fulltext .= $htmlContent;
            
            // Обновляем размер статьи ; $this->postSize включает длину инф строки
            $this->articleSize += $this->postSize;
Factory::getApplication()->enqueueMessage('transferPost Размер статьи: ' . $this->articleSize, 'info'); // ОТЛАДКА   
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

    private function getTopicSubject($firstPostId)
{
    // Получаем subject и userid одним запросом
    $query = $this->db->getQuery(true)
        ->select([$this->db->quoteName('subject'), $this->db->quoteName('userid')])
        ->from($this->db->quoteName('#__kunena_messages'))
        ->where($this->db->quoteName('id') . ' = ' . $firstPostId);

    $result = $this->db->setQuery($query)->loadObject();

    // Возвращаем массив
    return [
        'subject' => $result->subject,
        'topicAuthorId' => $result->userid
    ];
}

    /**
     * Построение списка ID постов для плоской схемы обхода (по времени создания)
     * @param   int  $firstPostId  ID первого поста темы
     * @return  array  Список ID постов
     */
    private function buildFlatPostIdList($firstPostId)
    {
        try {
          // Получаем Id темы
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('thread'))
                ->from($this->db->quoteName('#__kunena_messages'))
                ->where($this->db->quoteName('id') . ' = ' . $this->db->quote($firstPostId));

            $threadId = $this->db->setQuery($query)->loadResult();
        
            // Получаем все посты темы
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__kunena_messages'))
                ->where($this->db->quoteName('thread') . ' = ' . (int)$threadId)
                ->where($this->db->quoteName('hold') . ' = 0')
                ->order($this->db->quoteName('time') . ' ASC');
            /* оптимизация для уменьшения нагрузки на базу данных г: $query = $this->db->getQuery(true) \\  ->select($this->db->quoteName('id')) \\
    ->from($this->db->quoteName('#__kunena_messages')) \\ ->where($this->db->quoteName('thread') . ' IN (' . \\ $this->db->getQuery(true) \\
 ->select($this->db->quoteName('thread')) \\ ->from($this->db->quoteName('#__kunena_messages')) \\ 
 ->where($this->db->quoteName('id') . ' = ' . $this->db->quote($firstPostId)) . ')') \\  ->where($this->db->quoteName('hold') . ' = 0') \\
    ->order($this->db->quoteName('time') . ' ASC');
    */
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

    $infoPostString = '<div class="infoPostString">';
    $infoPostString .= '<br /> v v v v v<br />';
    
    // Автор (никнейм)
    if ($this->params->post_author) {
        $infoPostString .= htmlspecialchars($this->currentPost->name, ENT_QUOTES, 'UTF-8');
    }
    
    // Заголовок поста
    if ($this->params->post_title) {
        $infoPostString .= ' / ' . htmlspecialchars($this->currentPost->subject, ENT_QUOTES, 'UTF-8');
    }
    
    // Дата и время
    if ($this->params->post_creation_date) {
        $date = date('d.m.Y', $this->currentPost->time);
        $infoPostString .= ' / ' . $date;
        
        if ($this->params->post_creation_time) {
            $time = date('H:i', $this->currentPost->time);
            $infoPostString .= ' ' . $time;
        }
    }
  
    // IDs постов (с ссылкой или без)
    if ($this->params->post_ids) {
        // Текущий пост
        if ($this->params->kunena_post_link) {
            $postUrl = $this->getKunenaPostUrl($this->currentPost->id);
            $infoPostString .= ' / #<a href="' . htmlspecialchars($postUrl, ENT_QUOTES, 'UTF-8') . '">' 
                            . $this->currentPost->id . '</a>';
        } else {
            $infoPostString .= ' / #' . $this->currentPost->id;
        }
        
        // Родительский пост (если есть)
        if (!empty($this->currentPost->parent)) {
            if ($this->params->kunena_post_link) {
                $parentUrl = $this->getKunenaPostUrl($this->currentPost->parent);
                $infoPostString .= ' << #<a href="' . htmlspecialchars($parentUrl, ENT_QUOTES, 'UTF-8') . '>' 
                                . $this->currentPost->parent . '</a>';
            } else {
                $infoPostString .= ' << #' . $this->currentPost->parent;
            }
        }
    }  
    
    // Закрываем блок
    $infoPostString .= '<br /> * * * * *</div><br />';
    return $infoPostString;
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
     * Проверяет, нужно ли добавлять ссылки на посты
     */
    private function shouldAddPostLink(): bool
    {
        return $this->params->get('kunena_post_link', 0) == 1;
    }
    
    /**
     * Генерирует URL поста в Kunena
     */
    private function getKunenaPostUrl(int $postId): string
    {
        try {
            $post = KunenaForumMessageHelper::get($postId);
            return $post->exists() 
                ? KunenaRoute::getMessageUrl($postId, false)
                : "#{$postId}";
        } catch (Exception $e) {
            // Просто возвращаем якорь, если что-то пошло не так
            return "#{$postId}";
        }
    }
}
