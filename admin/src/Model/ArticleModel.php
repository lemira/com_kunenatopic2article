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

/**
 * Article Model
 * @since  0.0.1
 */
class ArticleModel extends BaseDatabaseModel
{
    protected $db; // @var \Joomla\Database\DatabaseInterface 
    protected $app; /** @var \Joomla\CMS\Application\CMSApplication */
    private $currentArticle = null;  
    private $articleSize = 0;    // Текущий размер статьи , @var    int 
    private $articleLinks = [];  // Массив ссылок на созданные статьи  @var array 
    private $postId = 0;   // Текущий ID поста @var    int
    private $postSize = 0; // Размер текущего поста var    int
    private $postIdList = []; // Список ID постов для обработки @var    array
    private $currentPost = null;  // Текущий пост @var    object
    private $subject = ''; // Переменная модели для хранения subject
    private $topicAuthorId = ''; // Переменная модели для хранения Id автора
    private $params = null; // Хранение параметров для доступа в других методах

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
           
            $topicId = $firstPostId; // текущий id 
                      
            $data = $this->getTopicSubject($firstPostId);    // Возвращаем массив
            $this->$subject = $data['subject'];
           Factory::getApplication()->enqueueMessage('ArticleModel $subject: ' . $this->$subject, 'info'); // ОТЛАДКА 
            $this->$topicAuthorId = $data['topicAuthorId'];
            
            // Формируем список ID постов в зависимости от схемы обхода
            if ($params['post_transfer_scheme'] == 'tree') {
                $this->postIdList = $this->buildTreePostIdList($topicId);
            } else {
                $this->postIdList = $this->buildFlatPostIdList($firstPostId);
                Factory::getApplication()->enqueueMessage('Массив ID постов: ' . print_r($this->postIdList, true), 'info'); // ОТЛАДКА
            }

            // Основной цикл обработки постов
            while ($this->postId != 0) {
                // Открываем пост для доступа к его параметрам
                $this->openPost($this->postId);

                // Если статья не открыта или текущий пост не помещается в статью
                if ($this->currentArticle === null || 
                    ($this->articleSize + $this->postSize > $params['max_article_size'] && $this->articleSize > 0)) {
                   
                    // Если статья уже открыта, закрываем её перед открытием новой
                    if ($this->currentArticle !== null) {
                        $this->closeArticle();
                    }
                   
                    // Открываем новую статью
                    $this->openArticle();
                }

                // Переносим содержимое поста в статью
                $this->transferPost();

                // Переходим к следующему посту
                $this->nextPost();
            }

            // Закрываем последнюю статью
            if ($this->currentArticle !== null) {
                $this->closeArticle();
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
            // Формируем базовый заголовок статьи
            $title = $this->subject;
           
            // Если это не первая статья, добавляем номер части
            if (!empty($this->articleLinks)) {
                $partNum = count($this->articleLinks) + 1;
                $title .= ' - ' . Text::sprintf('COM_KUNENATOPIC2ARTICLE_PART_NUMBER', $partNum);
            }

            // Формируем уникальный алиас
            $baseAlias = OutputFilter::stringURLSafe($title);
            $uniqueAlias = $this->getUniqueAlias($baseAlias);

            // Сбрасываем текущий размер статьи
            $this->articleSize = 0;

            // Отладка
            $this->app->enqueueMessage('Статья подготовлена: ' . $title . ', категория: ' . $this->params['article_category'] . ', alias: ' . $uniqueAlias, 'notice');

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
            $this->app->enqueueMessage('Сохранение статьи: ' . $this->currentArticle->title, 'notice');

            // Обработка introtext, если он пустой
           // if (empty($this->currentArticle->introtext) && !empty($this->currentArticle->fulltext)) {
             //   $maxIntroLength = 500; // Максимальная длина введения
               // if (strlen($this->currentArticle->fulltext) > $maxIntroLength) {
                 //   $this->currentArticle->introtext = substr($this->currentArticle->fulltext, 0, $maxIntroLength) . '...';
                //} else {
                  //  $this->currentArticle->introtext = $this->currentArticle->fulltext;
               // }
            }

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
            $title = $this->currentArticle->title;
            $uniqueAlias = $this->currentArticle->alias;
            
            $data = [
                'title' => $title,
                'alias' => $uniqueAlias,
                'introtext' => '',
                'fulltext' => $this->currentArticle->fulltext,
                'catid' => (int) $this->params['article_category'],
                'created_by' => (int)$this->topicAuthorId, 
                'state' => 1, // Published
                'language' => '*',
                'access' => 1,
                'created' =>  (new Date())->toSql(), 
                'attribs' => '{}',
                'metakey' => '',
                'metadesc' => '',
                'metadata' => '{}'
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

            return $tableArticle->id;
            
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
            // Получаем данные поста из базы данных Kunena, фильтрация промодерированных постов
            $query = $this->db->getQuery(true)
                ->select('*')
                ->from($this->db->quoteName('#__kunena_messages'))
                ->where($this->db->quoteName('id') . ' = ' . (int)$postId)
                ->where($this->db->quoteName('hold') . ' = 0');

            $this->currentPost = $this->db->setQuery($query)->loadObject();

            if (!$this->currentPost) {
                throw new \Exception('Пост не найден или заблокирован: ' . $postId);
            }

            $this->postSize = strlen($this->currentPost->message); // Рассчитываем размер поста

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
            // Формируем информационную строку о посте
            $infoString = $this->formatPostInfo();
            
            // Добавляем информационную строку в статью, если она не пуста
            if (!empty($infoString)) {
                if (!isset($this->currentArticle->fulltext)) {
                    $this->currentArticle->fulltext = '';
                }
                $this->currentArticle->fulltext .= $infoString;
            }

            // Преобразуем BBCode в HTML
            $htmlContent = $this->convertBBCodeToHtml($this->currentPost->message);
            
            // Добавляем преобразованный текст в статью
            $this->currentArticle->fulltext .= $htmlContent;
            
            // Обновляем размер статьи
            $this->articleSize += strlen($htmlContent);

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
        // Находим индекс текущего поста в списке
        $currentIndex = array_search($this->postId, $this->postIdList);
        
        // Если текущий пост найден и есть следующий элемент
        if ($currentIndex !== false && isset($this->postIdList[$currentIndex + 1])) {
            $this->postId = $this->postIdList[$currentIndex + 1];
        } else {
            // Если больше нет постов
            $this->postId = 0;
        }

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
            ->where($this->db->quoteName('id') . ' = ' . $firstPostId);
        
        $threadId = $this->db->setQuery($query)->loadResult();
        
            // Получаем все посты темы
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('id'))
                ->from($this->db->quoteName('#__kunena_messages'))
                ->where($this->db->quoteName('thread') . ' = ' . (int)$threadId)
                ->where($this->db->quoteName('hold') . ' = 0')
                ->order($this->db->quoteName('time') . ' ASC');

            $postIds = $this->db->setQuery($query)->loadColumn();
                
            if (empty($postIds)) {    // эта проверка в принципе не нужна, так как минимум 1 пост с id=$firstPostId в список попадет
                throw new \Exception(Text::sprintf('COM_KUNENATOPIC2ARTICLE_NO_POSTS_IN_TOPIC', $firstPostId));
            }

            return $postIds;
            
        } catch (\Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return [];
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
    private function formatPostInfo()
    {
        if ($this->currentPost === null) {
            return '';
        }

        // Получаем данные пользователя
        $userId = $this->currentPost->userid;
        $userName = $this->getUserName($userId);
        
        // Форматируем дату создания поста
      //  $date = new Date($this->currentPost->time);
       // $formattedDate = $date->format(Text::_('DATE_FORMAT_LC2'));

        // Формируем информационную строку
      //  $infoString = '<div class="post-info">';
      //  $infoString .= '<p><strong>' . Text::_('COM_KUNENATOPIC2ARTICLE_AUTHOR') . ':</strong> ' . htmlspecialchars($userName) . '</p>';
      //  $infoString .= '<p><strong>' . Text::_('COM_KUNENATOPIC2ARTICLE_DATE') . ':</strong> ' . $formattedDate . '</p>';
      //  $infoString .= '</div><hr />';

        return "";    // $infoString;
    }

    /**
     * Получение имени пользователя по ID
     * @param   int  $userId  ID пользователя
     * @return  string  Имя пользователя
     */
    private function getUserName($userId)
    {
        try {
            $query = $this->db->getQuery(true)
                ->select($this->db->quoteName('name'))
                ->from($this->db->quoteName('#__users'))
                ->where($this->db->quoteName('id') . ' = ' . (int)$userId);

            $userName = $this->db->setQuery($query)->loadResult();
                
            return $userName ? $userName : Text::_('COM_KUNENATOPIC2ARTICLE_UNKNOWN_USER');
        } catch (\Exception $e) {
            return Text::_('COM_KUNENATOPIC2ARTICLE_UNKNOWN_USER');
        }
    }

    /**
     * Преобразование BBCode в HTML
     * @param   string  $text  Текст с BBCode
     * @return  string  HTML-текст
     */
    private function convertBBCodeToHtml($text)
    {
        try {
            // Используем парсер KunenaBbcode
            $bbcode = KunenaBbcode::getInstance();
            return $bbcode->parse($text);
        } catch (\Exception $e) {
            // В случае ошибки
            $this->app->enqueueMessage('Ошибка парсинга BBCode: ' . $e->getMessage(), 'warning');
            return $text; // Возвращаем исходный текст при ошибке
        }
    }
}
