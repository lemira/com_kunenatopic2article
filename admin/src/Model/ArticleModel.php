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
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Router\Route;
// use Kunena\Bbcode\KunenaBbcode; 
use Joomla\CMS\Filter\OutputFilter as FilterOutput;
use Joomla\Component\KunenaTopic2Article\Administrator\Parser\PostContentParser;
use Joomla\Component\KunenaTopic2Article\Administrator\Helper\VideoProcessor;
use Joomla\Component\KunenaTopic2Article\Administrator\Repository\ContentRepository;
use Joomla\Component\KunenaTopic2Article\Administrator\Repository\KunenaRepository;
use Joomla\Component\KunenaTopic2Article\Administrator\Repository\ParamsRepository;
use Joomla\Component\KunenaTopic2Article\Administrator\Renderer\ArticleContentRenderer;
use Joomla\Component\KunenaTopic2Article\Administrator\Renderer\PostRenderer;
use Joomla\Component\KunenaTopic2Article\Administrator\Renderer\ReminderLineRenderer;
use Joomla\Component\KunenaTopic2Article\Administrator\Service\ArticleNotificationService;
use Joomla\Component\KunenaTopic2Article\Administrator\Service\TreeBuilder;

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
    private int $topicAuthorId = 0; // ID автора исходной темы
    private int $currentIndex = 0; // первый переход с первого элемента $threadId = $firstPostId (0) на 2-й (1)
    private string $postInfoString = '';  // Информационная строка поста
    private string $reminderLines = '';  // строки напоминания поста
    private string $title = '';   // Заголовок статьи
    private string $htmlContent = '';   // Текст поста после BBCode
    private array $postIds_time = []; // Хронологический список постов для проверки URL
    public bool $isPreview = false;
    private $videoProcessor = null; // Video processor instance
    private ContentRepository $contentRepository;
    private KunenaRepository $kunenaRepository;
    private ParamsRepository $paramsRepository;
    private ArticleContentRenderer $articleContentRenderer;
    private PostRenderer $postRenderer;
    private ReminderLineRenderer $reminderLineRenderer;
    private ArticleNotificationService $articleNotificationService;
    private TreeBuilder $treeBuilder;
    private PostContentParser $postContentParser;
    
    public function __construct($config = [])
    {
        parent::__construct($config);
        
        $this->app = Factory::getApplication();
        $this->db = $this->getDatabase();
        $this->contentRepository = new ContentRepository($this->db);
        $this->kunenaRepository = new KunenaRepository($this->db);
        $this->paramsRepository = new ParamsRepository($this->db);
        $this->articleContentRenderer = new ArticleContentRenderer();
        $this->postRenderer = new PostRenderer();
        $this->articleNotificationService = new ArticleNotificationService();
        $this->treeBuilder = new TreeBuilder();
        
        // Инициализируем видео-процессор
        $this->videoProcessor = new VideoProcessor();
        $this->reminderLineRenderer = new ReminderLineRenderer($this->videoProcessor);
        $this->postContentParser = new PostContentParser($this->kunenaRepository, $this->videoProcessor);
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
            
            $majorParams = $this->getMajorParams($firstPostId);
            $this->threadId = $majorParams['thread'];
            $this->subject = $majorParams['subject'];
            $this->topicAuthorId = $majorParams['userid'];

            // Формируем список ID постов в зависимости от схемы обхода; должно быть получены главные Major параметры первого поста!
            $this->postIdList = $this->buildFlatPostIdList($firstPostId); // Создаем всегда хронологический список (flat нужен для URL постов)
            $this->postIds_time = $this->treeBuilder->buildFlatPostIdList(
                $this->kunenaRepository->getVisibleThreadPostIds((int) $this->threadId)
            ); // Полный хронологический список Kunena для вычисления start в URL
            if ($this->params->post_transfer_scheme === 1) { // если flat
                $baum = $this->buildTreePostIdList($firstPostId);
                $this->postIdList = $baum['postIds']; // для Tree меняем $this->postIdList
                $this->postLevelList = $baum['levels'];
                     }
            
              $this->postId = $firstPostId; // текущий id
              $this->openPost($this->postId); // Открываем первый пост темы для доступа к его параметрам
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
           $this->currentArticle->fulltext .= $this->articleContentRenderer->renderOpeningContent(
                $this->getContentLanguageString('COM_KUNENATOPIC2ARTICLE_INFORMATION_SIGN'),
                $this->getContentLanguageString('COM_KUNENATOPIC2ARTICLE_WARNING_SIGN')
           );
           
            // Формируем базовый заголовок статьи
            $this->title = $this->subject;
            // Если это не первая статья, добавляем номер части
            if (!empty($this->articleLinks)) {
                $partNum = count($this->articleLinks) + 1;
                $this->title .= ' - ' . $this->getPartNumberText($partNum);
            }
            $this->currentArticle->title = $this->title;
           
            // Формируем уникальный алиас
            $baseAlias = FilterOutput::stringURLSafe($this->title);
            $uniqueAlias = $this->getUniqueAlias($baseAlias);
            $this->currentArticle->alias = $uniqueAlias;

            return true;
         } catch (\Exception $e) {
            $this->app->enqueueMessage('Ошибка при открытии статьи: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    private function getPartNumberText(int $partNum): string
    {
        $template = $this->getContentLanguageString('COM_KUNENATOPIC2ARTICLE_PART_NUMBER');

        return sprintf($template, $partNum);
    }

    private function getContentLanguageString(string $key): string
    {
        $strings = $this->loadContentLanguageStrings($this->getContentLanguageTag());

        if (isset($strings[$key])) {
            return $strings[$key];
        }

        return Text::_($key);
    }

    private function getContentLanguageTag(): string
    {
        $language = $this->app->getLanguage();
        $siteLanguage = (string) ComponentHelper::getParams('com_languages')->get('site', '');

        if ($siteLanguage !== '') {
            return $siteLanguage;
        }

        if (preg_match('/\p{Cyrillic}/u', $this->subject)) {
            return 'ru-RU';
        }

        return $language->getTag();
    }

    private function loadContentLanguageStrings(string $languageTag): array
    {
        $paths = [
            JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/language/' . $languageTag . '/com_kunenatopic2article.ini',
            JPATH_ADMINISTRATOR . '/language/' . $languageTag . '/com_kunenatopic2article.ini',
            JPATH_SITE . '/language/' . $languageTag . '/com_kunenatopic2article.ini',
        ];

        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }

            $strings = parse_ini_file($path);

            if (is_array($strings)) {
                return $strings;
            }
        }

        return [];
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
            $this->currentArticle->fulltext = $this->articleContentRenderer->embedCss($this->currentArticle->fulltext);
            
            // 2. Создаем статью через Table
            $this->articleId = $this->createArticleViaTable();
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
            $url = Route::link('site', $link);

            // Добавляем ссылку и заголовок в массив для последующего вывода
            $this->articleLinks[] = [
                'title' => $this->currentArticle->title,
                'url' => $url,
                'id' => $this->articleId  // Сохраняем ID в массиве ссылок
                ];
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
        return $this->contentRepository->aliasExists((string) $alias);
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
        
        // Подготавливаем базовые данные
        $data = [
            'title' => $this->currentArticle->title,
            'alias' => $this->currentArticle->alias,
            'introtext' => '',
            'fulltext' => $this->currentArticle->fulltext,
            'catid' => (int) $this->params->article_category,
            'state' => 1, // Published 
            'created' => (new Date())->toSql(),
            'created_by' => $this->topicAuthorId,
            'publish_up' => (new Date())->toSql(),
            'language' => '*',
            'access' => 1,
            'attribs' => '{"show_title":"","link_titles":"","show_tags":""}',
            'metakey' => '',
            'metadesc' => '',
            'metadata' => '{"robots":"","author":"","rights":""}'
        ];

        
        if ($this->isPreview) {      // Если это режим превью - модифицируем данные
          $data['state'] = 0; // превью не опубликовано
        }

        // Сохраняем статью
        if (!$tableArticle->save($data)) {
            throw new \Exception($tableArticle->getError());
        }
        
        // Получаем ID созданной статьи
        $savedId = $tableArticle->id;
        
        // --- Запись в #__workflow_associations 
         if (!$this->isPreview) {   // только для обычных статей, для превью не нужно
            try {
                $exists = $this->contentRepository->workflowAssociationExists((int) $savedId);

                if (!$exists) {
                    $this->contentRepository->addWorkflowAssociation((int) $savedId, 1);
                }
            } catch (\Exception $e) {
                // Логируем ошибку, но не прерываем работу
                $this->app->enqueueMessage('Ошибка добавления записи в workflow_associations: ' . $e->getMessage(), 'warning');
       }
    }
        // --- Конец записи в #__workflow_associations
        
        return $savedId;
        
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
            $this->currentPost = $this->kunenaRepository->getMessage((int) $postId);
            // Проверка if (!$this->currentPost) не нужна, все посты проверены; сбой БД ловится в catch 
        
            // Получаем текст поста
            $postText = $this->kunenaRepository->getMessageText((int) $postId);

            // Проверяем, найден ли текст
            if ($postText === null) {
                throw new \Exception(Text::sprintf('COM_KUNENATOPIC2ARTICLE_POST_TEXT_NOT_FOUND', $postId));
            }

            $this->postText = $postText;
 
            $this->postInfoString = $this->createPostInfoString(); // Вычиcляем информационную строку (всегда есть хотя бы разделители) поста
           
            // Вычисляем размер поста в символах до HTML-преобразования.
           // Расчёт длины с обработкой ошибок
           try {
              $this->postSize = mb_strlen($this->postText, 'UTF-8')
              + mb_strlen($this->postInfoString, 'UTF-8')
              + mb_strlen($this->reminderLines, 'UTF-8');
          } catch (\Throwable $e) {
               throw new \RuntimeException('Ошибка расчёта размера поста: ' . $e->getMessage());
          }
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
          // Вместо простого truncate, используем функцию очистки
          $reminderLinesLength = (int)$this->params->reminder_lines;
         
        $this->reminderLines = $this->processReminderLines($this->htmlContent, $reminderLinesLength); // обработка ссылок и рис-в и обрезание 
           } 
           $this->currentArticle->fulltext .= '<div class="kun_p2a_divider-gray"></div>';
                        
            // Обновляем размер статьи. $this-postSize включает длину инф строки и строки напоминания, вычислен в openPost
            $this->articleSize += $this->postSize;
            return true;
        } catch (\Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

/**
 * Processes the raw HTML content, replacing links and images with short
 * descriptive text, and truncating the result to the defined limit.
 *
 * @param string $htmlContent The raw HTML content of the post.
 * @param int $reminderLinesLength The maximum number of characters for the reminder.
 * @return string The processed and truncated reminder line text.
 */
private function processReminderLines(string $htmlContent, int $reminderLinesLength): string
{
    return $this->reminderLineRenderer->render($htmlContent, $reminderLinesLength);
}
    
    /**
     * Переход к следующему посту
     * @return  int  ID следующего поста или 0, если больше нет постов
     */
   private function nextPost()
{
    $this->currentIndex += 1;
    $this->postId = $this->postIdList[$this->currentIndex];
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
      $postIds = $this->getAllThreadPosts($this->threadId); // Получаем массив постов темы
      return $this->treeBuilder->buildFlatPostIdList($postIds);
    }

   private function getAllThreadPosts($threadId)           
     {
   // --- НАЧАЛО БЛОКА ДЛЯ ИСКЛЮЧЕНИЯ АВТОРОВ --- 
        $ignoredAuthors = trim($this->params->ignored_authors); // Получаем и обрабатываем список игнорируемых авторов
        $ignoredAuthorsArray = [];
     if (!empty($ignoredAuthors)) { // Проверяем, что список не пустой
         $ignoredAuthorsArray = array_filter(array_map('trim', explode(',', $ignoredAuthors)));  // Разбиваем строку на массив, очищаем от пробелов и удаляем пустые значения
    }
    // --- КОНЕЦ БЛОКА ИСКЛЮЧЕНИЯ АВТОРОВ ---
         $postIds = $this->kunenaRepository->getVisibleThreadPostIds((int) $threadId, $ignoredAuthorsArray);
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
        $allPosts = $this->kunenaRepository->getThreadTreePosts((int) $this->threadId);
        
        // 2. ОТДЕЛЬНО получаем посты для финального списка (только hold=0)
        $finalPostIds = $this->getAllThreadPosts($this->threadId); 

        return $this->treeBuilder->buildTreePostIdList((int) $firstPostId, $finalPostIds, $allPosts);
        
    } catch (\Exception $e) {
        $this->app->enqueueMessage('Ошибка построения древовидного обхода: ' . $e->getMessage(), 'error');
        return [
            'postIds' => [$firstPostId, 0],
            'levels' => [0, 0]
        ];
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

    return $this->postRenderer->renderInfoString(
        $this->currentPost,
        $this->params,
        fn (int $postId): string => $this->getKunenaPostUrl($postId),
        $this->postId,
        $this->firstPostId,
        $this->postLevelList[$this->currentIndex] ?? null
    );
}

private function printHeadOfPost()
{
    $this->currentArticle->fulltext .= $this->postRenderer->renderHeadOfPost(
        $this->currentPost,
        $this->params,
        $this->postInfoString,
        $this->reminderLines
    );
}
    
/**
 * Генерируем URL для конкретного поста через штатный Kunena mesid-маршрут.
 *
 * @param int $postId ID поста в Kunena
 * @return string URL поста или пустая строка, если пост не входит в обработанный список
 */
public function getKunenaPostUrl(int $postId): string
{
    // ПРОВЕРКА: если пост не существует в обработанном списке, возвращаем пустую строку
    $postIndex = array_search($postId, $this->postIds_time, true);

    if ($postIndex === false) {
        return '';
    }

    $post = $this->kunenaRepository->getMessage((int) $postId);

    if ($post !== null) {
        $topicSubject = $this->kunenaRepository->getTopicSubject((int) $post->thread) ?: (string) $post->subject;
        $messagesPerPage = $this->kunenaRepository->getKunenaMessagesPerPage();
        $postStart = intdiv((int) $postIndex, $messagesPerPage) * $messagesPerPage;

        return $this->kunenaRepository->buildTopicPostUrl(
            (int) $post->catid,
            (int) $post->thread,
            $topicSubject,
            $postStart,
            (int) $post->id
        );
    }

    $kunenaUrl = $this->kunenaRepository->getMessageUrl((int) $postId);

    if (!empty($kunenaUrl)) {
        return $kunenaUrl;
    }

    return '';
}
     
    /**
 * Отправка email-уведомлений о созданных статьях
 * @param   array  $articleLinks  Массив ссылок на статьи
 * @return  array  Результат отправки (success, recipients)
* Пример ошибки: ['success' => false, 'recipients' => ['admin@site.com'], 'error' => 'SMTP Error...']
 */
public function sendLinksToAdministrator(array $articleLinks): array
{
    $result = $this->articleNotificationService->sendArticleLinks(
        $articleLinks,
        $this->subject,
        (int) $this->params->topic_selection,
        $this->topicAuthorId
    );

    return $result;
}

private function convertBBCodeToHtml($text)
{
    try {
        return $this->postContentParser->render((string) $text);
    } catch (\Throwable $e) {
        $this->app->enqueueMessage(
            'BBCode Parse Error: ' . $e->getMessage(),
            'warning'
        );
        return $this->simpleBBCodeToHtml($text);
    }
}

private function simpleBBCodeToHtml($text)
{
    return 'NO PARSER';
}

/**
 * Удаляет статью предпросмотра по ID
 * 
 * @param int $id ID статьи для удаления
 * @return bool True при успешном удалении, false при ошибке
 */
public function deletePreviewArticleById($id)
{
    try {
        // ПРОСТО УДАЛЯЕМ СТАТЬЮ БЕЗ ПРОВЕРКИ АЛИАСА
        // (в preview мы всегда передаем правильный ID)
        $result = $this->contentRepository->deleteArticleById((int) $id);
        
        if ($result) {
            // Также удаляем запись из workflow_associations, если она есть
            try {
                $this->contentRepository->deleteWorkflowAssociation((int) $id);
            } catch (\Exception $e) {
                // Игнорируем ошибки при удалении из workflow_associations
                // (возможно, таблицы нет или запись уже удалена)
            }
            
            return true;
        } else {
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
        $params = $this->paramsRepository->getParams();
        
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

public function getMajorParams($postId)
{
    $result = $this->kunenaRepository->getMajorParams((int) $postId);
    
    return [
        'thread' => $result->thread,
        'subject' => $result->subject,
        'userid' => $result->userid
    ];
}    
    
} // КОНЕЦ КЛАССА
