<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   Copyright (C) 2023 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Controller;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Article Controller
 * @since  0.0.1
 */
class ArticleController extends BaseController
{
    /**
     * Создание статей из темы форума Kunena
     * @return  void
     */
    public function create()
    {
        Factory::getApplication()->enqueueMessage('create() в ArticleController', 'info'); // ОТЛАДКА
            
        // Check for request forgeries
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

        $app = Factory::getApplication();
        
        /** @var \Joomla\Component\Kunenatopic2article\Administrator\Model\ArticleModel $model */
        $model = $this->getModel('Article');

        // Получаем параметры из таблицы kunenatopic2article_params
        $params = $this->getComponentParams();
        
        if (empty($params) || empty($params->topic_selection)) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_TOPIC_SELECTED'), 'error');
            $this->setRedirect('index.php?option=com_kunenatopic2article');
            return;
        }

          try {
            Factory::getApplication()->enqueueMessage('до перехода в ArticleModel', 'info'); // ОТЛАДКА
            // Создаем статьи из темы Kunena
            $articleLinks = $model->createArticlesFromTopic($settings);
             Factory::getApplication()->enqueueMessage('после возвращения из ArticleModel', 'info'); // ОТЛАДКА
            // Отправляем массив ссылок администратору
            $this->sendLinksToAdministrator($articleLinks);
            
            // Отображаем результаты
            $app->setUserState('com_kunenatopic2article.article_links', $articleLinks);
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLES_CREATED_SUCCESSFULLY'), 'success');
            $app->setUserState('com_kunenatopic2article.can_create', false); // управление флагом can_create
        } catch (\Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }
        
        // Перенаправляем на страницу с результатами
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=result');
    }

    /**
     * Получение параметров компонента из таблиц
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
     * Отправка ссылок на созданные статьи администратору
     * @param   array  $articleLinks  Массив ссылок на статьи
     * @return  boolean  True в случае успеха, False в случае ошибки
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
            // Можно использовать API Kunena или другой подходящий метод
            
            // Пример интеграции с системой сообщений Kunena:
            // if (class_exists('KunenaForum') && \KunenaForum::installed()) {
                // Реализация отправки сообщения через Kunena API
            //    $this->sendKunenaMessage($messageText);
            // } else {
                // Альтернативный способ - отправка email
            //    $this->sendEmailToAdmin($messageText);
            // }

            return true;
        } catch (\Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }
}
