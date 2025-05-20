<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   Copyright (C) 2023 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\Registry\Registry;

/**
 * Article Model
 *
 * @since  0.0.1
 */
class KunenaTopic2ArticleModelArticle extends BaseDatabaseModel
{
    /**
     * Текущая статья
     *
     * @var    object
     */
    private $currentArticle = null;

    /**
     * Текущий размер статьи
     *
     * @var    int
     */
    private $articleSize = 0;

    /**
     * Массив ссылок на созданные статьи
     *
     * @var    array
     */
    private $articleLinks = [];

    /**
     * Текущий ID поста
     *
     * @var    int
     */
    private $postId = 0;

    /**
     * Размер текущего поста
     *
     * @var    int
     */
    private $postSize = 0;

    /**
     * Список ID постов для обработки
     *
     * @var    array
     */
    private $postIdList = [];

    /**
     * Текущий пост
     *
     * @var    object
     */
    private $currentPost = null;

    /**
     * Создание статей из темы форума Kunena
     *
     * @param   array  $settings  Настройки для создания статей
     *
     * @return  array  Массив ссылок на созданные статьи
     */
    public function createArticlesFromTopic($settings)
    {
        // Инициализация массива ссылок
        $this->articleLinks = [];

        try {
            // Отладка: выводим настройки
            $app = Factory::getApplication();
            $app->enqueueMessage('Настройки: ' . print_r($settings, true), 'notice');

            // Проверяем валидность категории статьи
            if (!$this->isCategoryValid($settings['article_category'])) {
                throw new Exception(Text::_('COM_KUNENATOPIC2ARTICLE_INVALID_CATEGORY_ID'));
            }

            // Устанавливаем ID первого поста темы
            $this->postId = $this->getFirstPostId($settings['topic_selection']);

            // Формируем список ID постов в зависимости от схемы обхода
            if ($settings['post_transfer_scheme'] == 'tree') {
                $this->postIdList = $this->buildTreePostIdList($settings['topic_selection']);
            } else {
                $this->postIdList = $this->buildFlatPostIdList($settings['topic_selection']);
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
                        $this->closeArticle();
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
                $this->closeArticle();
            }

            return $this->articleLinks;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return $this->articleLinks;
        }
    }

    /**
     * Проверка валидности категории
     *
     * @param   int  $categoryId  ID категории
     *
     * @return  boolean  True если категория существует
     */
    private function isCategoryValid($categoryId)
    {
        try {
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                ->select('id')
                ->from('#__categories')
                ->where('id = ' . (int)$categoryId)
                ->where('extension = ' . $db->quote('com_content'));
            
            $exists = $db->setQuery($query)->loadResult();
            
            // Отладка: выводим результат проверки категории
            $app = Factory::getApplication();
            $app->enqueueMessage('Проверка категории ID ' . $categoryId . ': ' . ($exists ? 'Существует' : 'Не существует'), 'notice');
            
            return !empty($exists);
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Открытие статьи для её заполнения
     *
     * @param   array  $settings  Настройки для создания статьи
     *
     * @return  boolean  True в случае успеха
     */
    private function openArticle($settings)
    {
        try {
            // Получаем заголовок темы для формирования заголовка статьи
            $topic = $this->getTopicData($settings['topic_selection']);
            
            // Формируем базовый заголовок статьи
            $title = $topic->subject;
            
            // Если это не первая статья, добавляем номер части
            if (!empty($this->articleLinks)) {
                $partNum = count($this->articleLinks) + 1;
                $title .= ' - ' . Text::sprintf('COM_KUNENATOPIC2ARTICLE_PART_NUMBER', $partNum);
            }

            // Создаем новую статью
            $this->currentArticle = Table::getInstance('Content');

            if (!$this->currentArticle) {
                throw new Exception(Text::_('COM_KUNENATOPIC2ARTICLE_CANNOT_CREATE_ARTICLE_INSTANCE'));
            }

            // Формируем уникальный алиас
            $baseAlias = OutputFilter::stringURLSafe($title);
            $uniqueAlias = $this->getUniqueAlias($baseAlias);

            // Заполняем базовые поля статьи
            $this->currentArticle->title = $title;
            $this->currentArticle->alias = $uniqueAlias;
            $this->currentArticle->introtext = '';
            $this->currentArticle->fulltext = '';
            $this->currentArticle->state = 1; // Опубликовано
            $this->currentArticle->catid = (int)$settings['article_category'];
            $this->currentArticle->created = (new Date())->toSql();
            $this->currentArticle->created_by = (int)$settings['post_author'];
            $this->currentArticle->created_by_alias = '';
            $this->currentArticle->modified = (new Date())->toSql();
            $this->currentArticle->modified_by = (int)$settings['post_author'];
            $this->currentArticle->publish_up = null;
            $this->currentArticle->publish_down = null;
            $this->currentArticle->images = '{}';
            $this->currentArticle->urls = '{}';
            $this->currentArticle->attribs = '{}';
            $this->currentArticle->version = 1;
            $this->currentArticle->ordering = 0;
            $this->currentArticle->metakey = '';
            $this->currentArticle->metadesc = '';
            $this->currentArticle->access = 1; // Публичный доступ
            $this->currentArticle->hits = 0;
            $this->currentArticle->metadata = '{}';
            $this->currentArticle->featured = 0;
            $this->currentArticle->language = '*'; // Все языки
            $this->currentArticle->xreference = '';

            // Устанавливаем parent_id в 0, чтобы избежать ошибки "Invalid parent ID"
            $this->currentArticle->parent_id = 0;

            // Создаем параметры статьи
            $articleParams = new Registry();
            $this->currentArticle->params = $articleParams->toString();

            // Сбрасываем текущий размер статьи
            $this->articleSize = 0;

            // Отладка
            $app = Factory::getApplication();
            $app->enqueueMessage('Статья подготовлена: ' . $title . ', категория: ' . $settings['article_category'] . ', alias: ' . $uniqueAlias, 'notice');

            return true;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Ошибка при открытии статьи: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Генерация уникального алиаса для статьи
     *
     * @param   string  $baseAlias  Базовый алиас
     *
     * @return  string  Уникальный алиас
     */
    private function getUniqueAlias($baseAlias)
    {
        $db = $this->getDbo();
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
     *
     * @param   string  $alias  Алиас для проверки
     *
     * @return  boolean  True если алиас существует
     */
    private function aliasExists($alias)
    {
        try {
            $db = $this->getDbo();
            $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__content')
                ->where('alias = ' . $db->quote($alias));
                
            $count = $db->setQuery($query)->loadResult();
            
            return $count > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Закрытие и сохранение статьи
     *
     * @return  boolean  True в случае успеха
     */
    private function closeArticle()
    {
        if ($this->currentArticle === null) {
            return false;
        }

        try {
            // Логирование перед сохранением для отладки
            $app = Factory::getApplication();
            $app->enqueueMessage('Сохранение статьи: ' . $this->currentArticle->title . ', ID категории: ' . $this->currentArticle->catid, 'notice');

            // Проверяем и настраиваем дополнительные поля
            if (!isset($this->currentArticle->parent_id) || $this->currentArticle->parent_id === null) {
                $this->currentArticle->parent_id = 0;
            }
            
            if (empty($this->currentArticle->asset_id)) {
                $this->currentArticle->asset_id = 0;
            }
            
            if (empty($this->currentArticle->fulltext)) {
                $this->currentArticle->fulltext = '';
            }
            
            if (empty($this->currentArticle->introtext) && !empty($this->currentArticle->fulltext)) {
                // Если introtext пуст, но есть fulltext, переместим часть контента
                $maxIntroLength = 500; // Максимальная длина введения
                if (strlen($this->currentArticle->fulltext) > $maxIntroLength) {
                    $this->currentArticle->introtext = substr($this->currentArticle->fulltext, 0, $maxIntroLength) . '...';
                } else {
                    $this->currentArticle->introtext = $this->currentArticle->fulltext;
                }
            }

            // Проверка всех необходимых полей
            $requiredFields = ['title', 'alias', 'introtext', 'catid', 'state', 'access', 'language'];
            foreach ($requiredFields as $field) {
                if (empty($this->currentArticle->$field)) {
                    $app->enqueueMessage("Отсутствует обязательное поле: {$field}", 'warning');
                    
                    // Устанавливаем значения по умолчанию
                    switch ($field) {
                        case 'alias':
                            $this->currentArticle->alias = OutputFilter::stringURLSafe($this->currentArticle->title) . '-' . uniqid();
                            break;
                        case 'state':
                            $this->currentArticle->state = 1;
                            break;
                        case 'access':
                            $this->currentArticle->access = 1;
                            break;
                        case 'language':
                            $this->currentArticle->language = '*';
                            break;
                        case 'introtext':
                            $this->currentArticle->introtext = '...';
                            break;
                    }
                }
            }
            
            // Отладка: вывод всех полей статьи перед сохранением
            $app->enqueueMessage('Содержимое статьи перед сохранением: ' . print_r($this->currentArticle->getProperties(), true), 'notice');

            // Проверяем поля статьи перед сохранением
            if (!$this->currentArticle->check()) {
                throw new Exception('Ошибка проверки статьи: ' . $this->currentArticle->getError());
            }

            // Сохраняем статью в базе данных
            if (!$this->currentArticle->store()) {
                throw new Exception('Ошибка сохранения статьи: ' . $this->currentArticle->getError());
            }

            // Логирование после сохранения для отладки
            $app->enqueueMessage('Статья успешно сохранена с ID: ' . $this->currentArticle->id, 'notice');

            // Формируем URL для статьи
            $link = Route::_('index.php?option=com_content&view=article&id=' . $this->currentArticle->id);
            
            // Добавляем ссылку и заголовок в массив для последующего вывода
            $this->articleLinks[] = [
                'title' => $this->currentArticle->title,
                'url' => Uri::root() . ltrim($link, '/'),
                'id' => $this->currentArticle->id
            ];

            // Сбрасываем текущую статью
            $this->currentArticle = null;

            return true;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Ошибка сохранения статьи: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Открытие поста для доступа к его параметрам
     *
     * @param   int  $postId  ID поста
     *
     * @return  boolean  True в случае успеха
     */
    private function openPost($postId)
    {
        try {
            // Получаем данные поста из базы данных Kunena
            $query = $this->getDbo()->getQuery(true)
                ->select('*')
                ->from('#__kunena_messages')
                ->where('id = ' . (int)$postId);

            $this->currentPost = $this->getDbo()->setQuery($query)->loadObject();

            if (!$this->currentPost) {
                throw new Exception(Text::sprintf('COM_KUNENATOPIC2ARTICLE_POST_NOT_FOUND', $postId));
            }

            // Рассчитываем размер поста
            $this->postSize = strlen($this->currentPost->message);

            return true;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Перенос поста в статью
     *
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
                $this->currentArticle->fulltext .= $infoString;
            }

            // Преобразуем BBCode в HTML
            $htmlContent = $this->convertBBCodeToHtml($this->currentPost->message);
            
            // Добавляем преобразованный текст в статью
            $this->currentArticle->fulltext .= $htmlContent;
            
            // Обновляем размер статьи
            $this->articleSize += strlen($this->currentArticle->fulltext);

            return true;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Переход к следующему посту
     *
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

    /**
     * Получение ID первого поста темы
     *
     * @param   int  $topicId  ID темы
     *
     * @return  int  ID первого поста
     */
    private function getFirstPostId($topicId)
    {
        try {
            $query = $this->getDbo()->getQuery(true)
                ->select('first_post_id')
                ->from('#__kunena_topics')
                ->where('id = ' . (int)$topicId);

            $firstPostId = $this->getDbo()->setQuery($query)->loadResult();
            
            if (!$firstPostId) {
                throw new Exception(Text::sprintf('COM_KUNENATOPIC2ARTICLE_TOPIC_NOT_FOUND', $topicId));
            }

            return $firstPostId;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return 0;
        }
    }

    /**
     * Получение данных темы
     *
     * @param   int  $topicId  ID темы
     *
     * @return  object  Объект с данными темы
     */
    private function getTopicData($topicId)
    {
        try {
            $query = $this->getDbo()->getQuery(true)
                ->select('*')
                ->from('#__kunena_topics')
                ->where('id = ' . (int)$topicId);

            $topic = $this->getDbo()->setQuery($query)->loadObject();
            
            if (!$topic) {
                throw new Exception(Text::sprintf('COM_KUNENATOPIC2ARTICLE_TOPIC_NOT_FOUND', $topicId));
            }

            return $topic;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return new stdClass();
        }
    }

    /**
     * Построение списка ID постов для плоской схемы обхода (по времени создания)
     *
     * @param   int  $topicId  ID темы
     *
     * @return  array  Список ID постов
     */
    private function buildFlatPostIdList($topicId)
    {
        try {
            $query = $this->getDbo()->getQuery(true)
                ->select('id')
                ->from('#__kunena_messages')
                ->where('thread = ' . (int)$topicId)
                ->order('time ASC');

            $postIds = $this->getDbo()->setQuery($query)->loadColumn();
            
            if (empty($postIds)) {
                throw new Exception(Text::sprintf('COM_KUNENATOPIC2ARTICLE_NO_POSTS_IN_TOPIC', $topicId));
            }

            return $postIds;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Построение списка ID постов для древовидной схемы обхода
     *
     * @param   int  $topicId  ID темы
     *
     * @return  array  Список ID постов
     */
    private function buildTreePostIdList($topicId)
    {
        // Заглушка: в реальной реализации здесь должен быть алгоритм обхода дерева
        // На данный момент возвращаем плоский список как временное решение
        return $this->buildFlatPostIdList($topicId);
    }

    /**
     * Формирование информационной строки о посте
     *
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
        $infoString .= Text::sprintf('COM_KUNENATOPIC2ARTICLE_POST_INFO_FORMAT', $userName, $formattedDate);
        $infoString .= '</div>';

        return $infoString;
    }

    /**
     * Получение имени пользователя по ID
     *
     * @param   int  $userId  ID пользователя
     *
     * @return  string  Имя пользователя
     */
    private function getUserName($userId)
    {
        try {
            $query = $this->getDbo()->getQuery(true)
                ->select('name')
                ->from('#__users')
                ->where('id = ' . (int)$userId);

            $userName = $this->getDbo()->setQuery($query)->loadResult();
            
            return $userName ? $userName : Text::_('COM_KUNENATOPIC2ARTICLE_UNKNOWN_USER');
        } catch (Exception $e) {
            return Text::_('COM_KUNENATOPIC2ARTICLE_UNKNOWN_USER');
        }
    }

    /**
     * Преобразование BBCode в HTML
     *
     * @param   string  $text  Текст с BBCode
     *
     * @return  string  HTML-текст
     */
    private function convertBBCodeToHtml($text)
    {
        // Проверяем наличие класса KunenaBbcode
        if (!class_exists('KunenaBbcode')) {
            // Если класс не найден, используем простую замену
            return $this->simpleBBCodeToHtml($text);
        }

        try {
            // Используем парсер KunenaBbcode
            $bbcode = KunenaBbcode::getInstance();
            return $bbcode->parse($text);
        } catch (Exception $e) {
            // В случае ошибки, используем простую замену
            return $this->simpleBBCodeToHtml($text);
        }
    }

    /**
     * Простое преобразование BBCode в HTML
     *
     * @param   string  $text  Текст с BBCode
     *
     * @return  string  HTML-текст
     */
    private function simpleBBCodeToHtml($text)
    {
        // Массив замен BBCode на HTML
        $bbcode = [
            '/\[b\](.*?)\[\/b\]/is' => '<strong>$1</strong>',
            '/\[i\](.*?)\[\/i\]/is' => '<em>$1</em>',
            '/\[u\](.*?)\[\/u\]/is' => '<u>$1</u>',
            '/\[url\=(.*?)\](.*?)\[\/url\]/is' => '<a href="$1">$2</a>',
            '/\[url\](.*?)\[\/url\]/is' => '<a href="$1">$1</a>',
            '/\[img\](.*?)\[\/img\]/is' => '<img src="$1" alt="" />',
            '/\[quote\](.*?)\[\/quote\]/is' => '<blockquote>$1</blockquote>',
            '/\[quote\=(.*?)\](.*?)\[\/quote\]/is' => '<blockquote cite="$1">$2</blockquote>',
            '/\[code\](.*?)\[\/code\]/is' => '<pre><code>$1</code></pre>',
            '/\[size\=(.*?)\](.*?)\[\/size\]/is' => '<span style="font-size:$1px">$2</span>',
            '/\[color\=(.*?)\](.*?)\[\/color\]/is' => '<span style="color:$1">$2</span>',
            '/\[list\](.*?)\[\/list\]/is' => '<ul>$1</ul>',
            '/\[list\=1\](.*?)\[\/list\]/is' => '<ol>$1</ol>',
            '/\[\*\](.*?)(\n|\r\n?)/is' => '<li>$1</li>',
        ];

        // Применение замен
        $html = preg_replace(array_keys($bbcode), array_values($bbcode), $text);
        
        // Замена переносов строк на HTML-теги
        $html = nl2br($html);

        return $html;
    }
}
