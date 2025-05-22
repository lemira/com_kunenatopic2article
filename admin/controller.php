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
        // Проверяем и сохраняем параметры
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

        // Получаем ID первого поста из параметров
        $firstPostId = (int)$params->topic_selection;

        // Получаем настройки из параметров компонента
        $settings = [
            'topic_selection' => $firstPostId, // 3232, ID первого поста
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
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Сохранение параметров и проверка темы перед созданием статей
     *
     * @return  boolean  True в случае успеха, False в случае ошибки
     */
    public function saveFromCreate()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $data = $input->get('jform', [], 'array');

        $firstPostId = isset($data['topic_selection']) ? (int) $data['topic_selection'] : 0;

        if (!$firstPostId) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_TOPIC_SELECTED'), 'error');
            return false;
        }

        // Проверка существования темы по first_post_id
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'subject']))
            ->from($db->quoteName('#__kunena_topics'))
            ->where($db->quoteName('first_post_id') . ' = ' . $db->quote($firstPostId));
        $topic = $db->setQuery($query)->loadObject();

        if (!$topic) {
            $app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_ERROR_INVALID_TOPIC_ID', $firstPostId), 'error');
            return false;
        }

        // Проверка существования первого поста
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__kunena_messages'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($firstPostId))
            ->where($db->quoteName('hold') . ' = 0');
        $firstPost = $db->setQuery($query)->loadResult();

        if (!$firstPost) {
            $app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_NO_FIRST_POST', $firstPostId), 'error');
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

    /**
     * Отправка ссылок на созданные статьи администратору
     *
     * @param   array  $articleLinks  Массив ссылок на статьи
     * @return  boolean  True в случае успеха, False в случае ошибки
     */
    private function sendLinksToAdministrator($articleLinks)
    {
        $app = Factory::getApplication();

        if (empty($articleLinks)) {
            return false;
        }

        try {
            $messageText = Text::_('COM_KUNENATOPIC2ARTICLE_NEW_ARTICLES_CREATED') . "\n\n";
            
            foreach ($articleLinks as $link) {
                $messageText .= $link['title'] . ': ' . $link['url'] . "\n";
            }

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
