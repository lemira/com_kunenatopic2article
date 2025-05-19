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
        // Check for request forgeries
        $this->checkToken();

        $app = Factory::getApplication();
        $input = $app->input;
        $model = $this->getModel('Article');

        // Получаем ID темы из параметров запроса
        $topicId = $input->getInt('topic_id', 0);
        
        if (!$topicId) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_TOPIC_SELECTED'), 'error');
            $app->redirect('index.php?option=com_kunenatopic2article');
            return;
        }

        // Получаем настройки из формы или используем настройки по умолчанию
        $settings = [
            'topic_selection' => $topicId,
            'post_transfer_scheme' => $input->getString('post_transfer_scheme', 'flat'),
            'article_category' => $input->getInt('article_category', 0),
            'post_author' => $input->getInt('post_author', 0),
            'max_article_size' => $input->getInt('max_article_size', 10000),
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
     * Отправка ссылок на созданные статьи администратору
     *
     * @param   array  $articleLinks  Массив ссылок на созданные статьи
     *
     * @return  boolean  True в случае успеха
     */
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
