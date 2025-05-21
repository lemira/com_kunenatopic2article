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

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Article Controller
 *
 * @since  0.0.1
 */
class KunenaTopic2ArticleControllerArticle extends BaseController
{
    /**
     * Создание статей из темы форума Kunena
     *
     * @return  void
     */
    public function create()
    {
     // На случай, если юзер не сделал save и для проверки Topic ID Сохраняем параметры и проверяем тему
    if (!$this->saveFromCreate()) {
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
        return;
    }
        
        // Check for request forgeries
        $this->checkToken();

        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Article');

        // Получаем параметры из таблицы kunenatopic2article_params
        $params = $this->getComponentParams();
        
        if (empty($params) || empty($params->topic_selection)) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_TOPIC_SELECTED'), 'error');
            $app->redirect('index.php?option=com_kunenatopic2article');
            return;
        }

        // Получаем ID темы из параметров компонента
        $topicId = (int)$params->topic_selection;
        
        // Получаем настройки из параметров компонента
        $settings = [
            'topic_selection' => $topicId,
            'post_transfer_scheme' => ($params->post_transfer_scheme == 'THREADED') ? 'tree' : 'flat',
            'article_category' => (int)$params->article_category,
            'post_author' => (int)$params->post_author,
            'max_article_size' => (int)$params->max_article_size,
        ];

        try {
            // Создаем статьи из темы Kunena
            $articleLinks = $model->createArticlesFromTopic($settings);
            
            // Отправляем массив ссылок администратору
            $this->sendLinksToAdministrator($articleLinks);
            
            // Отображаем результаты
            $app->setUserState('com_kunenatopic2article.article_links', $articleLinks);
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLES_CREATED_SUCCESSFULLY'), 'success');
        } catch (Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }

        // Перенаправляем на страницу с результатами
        $app->redirect('index.php?option=com_kunenatopic2article&view=result');
    }

    /**
     * Получение параметров компонента из таблицы
     *
     * @return  object|null  Объект с параметрами компонента
     */
    private function getComponentParams()
    {
        try {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select('*')
                ->from('#__kunenatopic2article_params')
                ->where('id = 1'); // Предполагаем, что параметры хранятся в записи с ID=1
            
            $params = $db->setQuery($query)->loadObject();
            
            if (!$params) {
                Factory::getApplication()->enqueueMessage(
                    Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_NOT_FOUND'), 
                    'error'
                );
                return null;
            }
            
            return $params;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return null;
        }
    }

    public function saveFromCreate()
{
    $app = \JFactory::getApplication();
    $input = $app->input;
    $data = $input->get('jform', array(), 'array');

    $topicId = isset($data['topic_selection']) ? (int) $data['topic_selection'] : 0;

    // Проверка существования темы
    $db = \JFactory::getDbo();
    $query = $db->getQuery(true)
        ->select($db->qn(['id', 'subject']))
        ->from($db->qn('#__kunena_topics'))
        ->where($db->qn('first_post_id') . ' = ' . $db->q($topicId));
    $db->setQuery($query);
    $topic = $db->loadObject();

    if (!$topic) {
        // Ошибка — неверный Topic ID
        $app->enqueueMessage(\JText::sprintf('COM_KUNENATOPIC2ARTICLE_ERROR_INVALID_TOPIC_ID', $topicId), 'error');
        return false;
    }

    // Показать заголовок темы
    $app->enqueueMessage('Тема: ' . $topic->subject, 'message');

    // Сохранение параметров
    $model = $this->getModel('Topic');
    if ($model->save($data)) {
        $app->enqueueMessage('Parameters saved successfully', 'success');
        return true;
    } else {
        $app->enqueueMessage('Failed to save parameters', 'error');
        return false;
    }
}

    
    private function sendLinksToAdministrator($articleLinks)
    {
        // Получаем объект приложения
        $app = Factory::getApplication();

        // Проверяем, есть ли статьи для отправки
        if (empty($articleLinks)) {
            return false;
        }

        try {
            // Создаем текст сообщения со ссылками на созданные статьи
            $messageText = Text::_('COM_KUNENATOPIC2ARTICLE_NEW_ARTICLES_CREATED') . "\n\n";
            
            foreach ($articleLinks as $link) {
                $messageText .= $link['title'] . ': ' . $link['url'] . "\n";
            }

            // Здесь должен быть код для отправки личного сообщения администратору
            // Используйте API Kunena или другой подходящий метод
            
            // Пример интеграции с системой сообщений Kunena:
            if (class_exists('KunenaForum') && KunenaForum::installed()) {
                // Реализация отправки сообщения
                // ...
            }

            return true;
        } catch (Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }
}
