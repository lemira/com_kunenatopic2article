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
    private $currentArticle = null;    /** @var object|null */
    private $articleSize = 0;    // Текущий размер статьи , @var    int 
    private $articleLinks = [];  // Массив ссылок на созданные статьи  @var array 
    private $postId = 0;   // Текущий ID поста @var    int
    private $postSize = 0; // Размер текущего поста var    int
    private $postIdList = []; // Список ID постов для обработки @var    array
    private $currentPost = null;  // Текущий пост @var    object
    private $subject = ''; // Переменная модели для хранения subject
    
        public function __construct($config = [])
    {
        parent::__construct($config);
        $this->app = Factory::getApplication();
        $this->db = Factory::getContainer()->get(DatabaseInterface::class);
    }

    /**
     * Создание статей из темы форума Kunena
     * @param   array  $settings  Настройки для создания статей
     * @return  array  Массив ссылок на созданные статьи
     */
    public function createArticlesFromTopic($settings)
    {
        // Инициализация массива ссылок
        $this->articleLinks = [];
        try {
         // Получаем параметры из таблицы kunenatopic2article_params
        $params = $this->getComponentParams();
       
            // Получаем ID первого поста
            $firstPostId = $params->topic_selection; // 3232
            Factory::getApplication()->enqueueMessage('ArticleModel $firstPostId: ' . $firstPostId, 'info'); // ОТЛАДКА          
           
            $topicId = $firstPostId; // текущий id 
                      
            $this->$subject = $this->getTopicSubject($firstPostId);
           Factory::getApplication()->enqueueMessage('ArticleModel $subject: ' . $this->$subject, 'info'); // ОТЛАДКА 
            
            // Формируем список ID постов в зависимости от схемы обхода
            if ($settings['post_transfer_scheme'] == 'tree') {
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
                    ($this->articleSize + $this->postSize > $settings['max_article_size'] && $this->articleSize > 0)) {
                   
                    // Если статья уже открыта, закрываем её перед открытием новой
                    if ($this->currentArticle !== null) {
                        $this->closeArticle($settings);
                    }
                   
                    // Открываем новую статью
                    $this->openArticle($settings);
                }

                // Переносим содержимое поста в статью
                $this->transferPost();

                // Переходим к следующему посту
                $this->nextPost();
            }

            // Закрываем последнюю статью
            if ($this->currentArticle !== null) {
                $this->closeArticle($settings);
            }

            return $this->articleLinks;
         } catch (\Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return $this->articleLinks;
        }
    }

    /**
     * Открытие статьи для её заполнения
     * @param   array  $settings  Настройки для создания статьи
     * @return  boolean  True в случае успеха
     */
    private function openArticle($settings)
    {
          try {
            // Формируем базовый заголовок статьи
            $title = $subject;
           
            // Если это не первая статья, добавляем номер части
            if (!empty($this->articleLinks)) {
                $partNum = count($this->articleLinks) + 1;
                $title .= ' - ' . Text::sprintf('COM_KUNENATOPIC2ARTICLE_PART_NUMBER', $partNum);
            }

            // Формируем уникальный алиас
            $baseAlias = OutputFilter::stringURLSafe($title);
            $uniqueAlias = $this->getUniqueAlias($baseAlias);

            // Создаем новую статью как объект (вместо массива для лучшей совместимости)
            $this->currentArticle = (object) [
                'title' => $title,
                'alias' => $uniqueAlias,
                'introtext' => '',
                'fulltext' => '',
                'catid' => (int)$settings['article_category'],
                'created_by' => (int)$settings['post_author']
            ];

            // Сбрасываем текущий размер статьи
            $this->articleSize = 0;

            // Отладка
            $this->app->enqueueMessage('Статья подготовлена: ' . $title . ', категория: ' . $settings['article_category'] . ', alias: ' . $uniqueAlias, 'notice');

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
        $uniqueAlias = $baseAlias;
        $counter = 0;
        
        // Проверяем уникальность алиаса
        while ($this->aliasExists($uniqueAlias)) {
            $counter++;
            $uniqueAlias = $baseAlias . '-' . $counter;
        }
        
        return $uniqueAlias;
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
                ->select('COUNT(*)')
                ->from($this->db->quoteName('#__content'))
                ->where($this->db->quoteName('alias') . ' = ' . $this->db->quote($alias));
                
            $count = $this->db->setQuery($query)->loadResult();
                
            return $count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Закрытие и сохранение статьи
     * @param   array  $settings  Настройки для создания статьи
     * @return  boolean  True в случае успеха
     */
    private function closeArticle($settings)
    {
        if ($this->currentArticle === null) {
            return false;
        }

        try {
            $this->app->enqueueMessage('Сохранение статьи: ' . $this->currentArticle->title, 'notice');

            // Обработка introtext, если он пустой
            if (empty($this->currentArticle->introtext) && !empty($this->currentArticle->fulltext)) {
                $maxIntroLength = 500; // Максимальная длина введения
                if (strlen($this->currentArticle->fulltext) > $maxIntroLength) {
                    $this->currentArticle->introtext = substr($this->currentArticle->fulltext, 0, $maxIntroLength) . '...';
                } else {
                    $this->currentArticle->introtext = $this->currentArticle->fulltext;
                }
            }

            // Создаем статью через Table
            $articleId = $this->createArticleViaTable($this->currentArticle, $settings);
            
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
     * @param   object  $article   Данные статьи
     * @param   array   $settings  Настройки
     * @return  boolean|int  False в случае неудачи, ID статьи в случае успеха
     */
    protected function createArticleViaTable($article, $settings)
    {
        try {
            // Получаем table для контента
            $table = Table::getInstance('Content');
            
            if (!$table) {
                throw new \Exception('Не удалось получить таблицу контента');
            }

            // Подготавливаем данные
            $data = [
                'title' => $article->title,
                'alias' => $article->alias,
                'introtext' => $article->introtext,
                'fulltext' => $article->fulltext,
                'catid' => (int) $settings['article_category'],
                'created_by' => (int) $settings['post_author'],
                'state' => 1, // Published
                'language' => '*',
                'access' => 1,
                'created' => Factory::getDate()->toSql(),
                'attribs' => '{}',
                'metakey' => '',
                'metadesc' => '',
                'metadata' => '{}'
            ];

            // Привязываем данные к таблице
            if (!$table->bind($data)) {
                throw new \Exception('Ошибка привязки данных: ' . $table->getError());
            }

            // Проверяем данные
            if (!$table->check()) {
                throw new \Exception('Ошибка проверки данных: ' . $table->getError());
            }

            // Сохраняем
            if (!$table->store()) {
                throw new \Exception('Ошибка сохранения: ' . $table->getError());
            }

            return $table->id;
            
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
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('subject'))
            ->from($this->db->quoteName('#__kunena_messages'))
            ->where($this->db->quoteName('id') . ' = ' . $firstPostId);
        
        $subject = $this->db->setQuery($query)->loadResult();
        
        return $subject;
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
    private function buildTreePostIdList($topicId)
    {
        // Заглушка: в реальной реализации здесь должен быть алгоритм обхода дерева
        // На данный момент возвращаем список из 1го поста темы как временное решение
        try {
            $postIds = [$firstPostId];
            return $postIds;
            }
            
            return $this->buildFlatPostIdList($firstPostId);
        } catch (\Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            return [];
        }
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
        $date = new Date($this->currentPost->time);
        $formattedDate = $date->format(Text::_('DATE_FORMAT_LC2'));

        // Формируем информационную строку
        $infoString = '<div class="post-info">';
        $infoString .= '<p><strong>' . Text::_('COM_KUNENATOPIC2ARTICLE_AUTHOR') . ':</strong> ' . htmlspecialchars($userName) . '</p>';
        $infoString .= '<p><strong>' . Text::_('COM_KUNENATOPIC2ARTICLE_DATE') . ':</strong> ' . $formattedDate . '</p>';
        $infoString .= '</div><hr />';

        return $infoString;
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
     * Получение параметров компонента из таблиц - копия из контроллера
     * @return  object|null  Объект с параметрами компонента
     */
    private function getComponentParams()
    {
        try {
            $db = Factory::getContainer()->get('DatabaseDriver');
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
     * Преобразование BBCode в HTML
     * @param   string  $text  Текст с BBCode
     * @return  string  HTML-текст
     */
    private function convertBBCodeToHtml($text)
    {
        // Проверяем наличие класса KunenaBbcode
        if (class_exists('KunenaBbcode')) {
            try {
                // Используем парсер KunenaBbcode
                $bbcode = KunenaBbcode::getInstance();
                return $bbcode->parse($text);
            } catch (\Exception $e) {
                // В случае ошибки, используем простую замену
                $this->app->enqueueMessage('Ошибка парсинга BBCode: ' . $e->getMessage(), 'warning');
            }
        }
        
        // Используем простую замену
        return $this->simpleBBCodeToHtml($text);
    }

    /**
     * Простое преобразование BBCode в HTML
     * @param   string  $text  Текст с BBCode
     * @return  string  HTML-текст
     */
    private function simpleBBCodeToHtml($text)
    {
        // Экранируем HTML теги сначала
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        
        // Массив замен BBCode на HTML
        $bbcode = [
            '/\[b\](.*?)\[\/b\]/is' => '<strong>$1</strong>',
            '/\[i\](.*?)\[\/i\]/is' => '<em>$1</em>',
            '/\[u\](.*?)\[\/u\]/is' => '<u>$1</u>',
            '/\[url\=(.*?)\](.*?)\[\/url\]/is' => '<a href="$1" target="_blank" rel="noopener">$2</a>',
            '/\[url\](.*?)\[\/url\]/is' => '<a href="$1" target="_blank" rel="noopener">$1</a>',
            '/\[img\](.*?)\[\/img\]/is' => '<img src="$1" alt="" class="img-responsive" />',
            '/\[quote\](.*?)\[\/quote\]/is' => '<blockquote class="blockquote">$1</blockquote>',
            '/\[quote\=(.*?)\](.*?)\[\/quote\]/is' => '<blockquote class="blockquote"><cite>$1</cite>$2</blockquote>',
            '/\[code\](.*?)\[\/code\]/is' => '<pre><code>$1</code></pre>',
            '/\[size\=(\d+)\](.*?)\[\/size\]/is' => '<span style="font-size:$1px">$2</span>',
            '/\[color\=(#?[a-zA-Z0-9]+)\](.*?)\[\/color\]/is' => '<span style="color:$1">$2</span>',
            '/\[list\](.*?)\[\/list\]/is' => '<ul>$1</ul>',
            '/\[list\=1\](.*?)\[\/list\]/is' => '<ol>$1</ol>',
            '/\[\*\]\s*(.*?)(?=\[\*\]|\[\/list\]|$)/is' => '<li>$1</li>',
        ];

        // Применение замен
        $html = preg_replace(array_keys($bbcode), array_values($bbcode), $text);
        
        // Замена переносов строк на HTML-теги
        $html = nl2br($html);

        return $html;
    }
}
