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
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Mail\Mailer;
use Joomla\Component\Users\Administrator\Model\UsersModel;
use RuntimeException;

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
        // Проверка токена (современный способ)
        $this->checkToken() or die(Text::_('JINVALID_TOKEN'));

        Factory::getApplication()->enqueueMessage('create() в ArticleController', 'info'); $app = Factory::getApplication(); // Отладка

        $app = Factory::getApplication();
        
       try {
         $model = $this->getModel('Article', 'Administrator'); // г.ко вместо  $model = $this->getModel('Article');
        // гр чтобы явно указать область Administrator,  для фронтенд-контроллере будет $this->getModel('Article', 'Site');



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
            $articleLinks = $model->createArticlesFromTopic($params);
 Factory::getApplication()->enqueueMessage('после возвращения из ArticleModel', 'info'); // ОТЛАДКА
            // Отправляем массив ссылок администратору
            $this->sendLinksToAdministrator($articleLinks);

            $model->setState('articleLinks', $articleLinks);  // для View
            $model->emailsSent = true;
            $model->emailsSentTo = $recipients;
            
            // Отображаем результаты
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLES_CREATED_SUCCESSFULLY'), 'success');
            $app->setUserState('com_kunenatopic2article.can_create', false); // управление флагом can_create
        } catch (\Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }
          // Возвращаемся в DisplayController
         } catch (\Exception $e) {
        $app->enqueueMessage($e->getMessage(), 'error');
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
     * Отправка ссылок на созданные статьи администратору и автору
     * @param   array  $articleLinks  Массив ссылок на статьи
     * @return  boolean  True в случае успеха, False в случае ошибки
     */
protected function sendLinksToAdministrator(array $articleLinks): void
{
    try {
        $app = Factory::getApplication();
        $config = Factory::getConfig();
        
        // Получаем модель пользователей
        $usersModel = $app->bootComponent('com_users')
                        ->getMVCFactory()
                        ->createModel('Users', 'Administrator', ['ignore_request' => true]);
        
        // Находим суперадминистратора (группа 8)
        $usersModel->setState('filter.group_id', 8);
        $superUsers = $usersModel->getItems();
        
        if (empty($superUsers)) {
            throw new RuntimeException('Не найден суперадминистратор');
        }
        
        $superAdminEmail = $superUsers[0]->email;
        $author = Factory::getUser($this->topicAuthorId);
        
        // Формируем письмо
        $mailer = Factory::getMailer();
        $mailer->setSubject(
            Text::sprintf('COM_KUNENATOPIC2ARTICLE_MAIL_SUBJECT', $config->get('sitename'))
        );
        
        $mailer->setBody(
            Text::sprintf(
                'COM_KUNENATOPIC2ARTICLE_MAIL_BODY',
                $config->get('sitename'),
                $this->subject,
                Uri::root() . 'index.php?option=com_kunena&view=topic&postid=' . (int)$this->params->topic_selection,
                $author->name,
                implode("\n", array_map(
                    function($link) {
                        return "- {$link['title']}: {$link['url']}";
                    },
                    $articleLinks
                ))
            )
        );
        
        // Отправляем письма
        $mailer->addRecipient($superAdminEmail);
        $mailer->addRecipient($author->email);
        $mailer->Send();
        
        $this->emailsSent = true;
        $this->emailsSentTo = [$superAdminEmail, $author->email];
        
    } catch (\Exception $e) {
        $app->enqueueMessage($e->getMessage(), 'error');
        $this->emailsSent = false;
    }
}
}
