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
use Joomla\CMS\Mail\MailTemplate;
use Joomla\CMS\Mail\Mail;
use Joomla\CMS\Uri\Uri;

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
    $config     = Factory::getConfig();
    $mailer     = Factory::getMailer();

    $siteName   = $config->get('sitename');
    $topicTitle = $this->subject;
    $author     = Factory::getUser($this->topicAuthorId);
    $authorName = $author->name;
    $authorEmail = $author->email;

    // Простая ссылка по postid 
    $postId = (int) $this->params->topic_selection;
    $topicLink = Uri::root() . 'index.php?option=com_kunena&view=topic&postid=' . $postId;

    // Список статей
    $articleList = '';
    foreach ($articleLinks as $link) {
        $articleList .= "- {$link['title']}: {$link['url']}\n";
    }

    // Тело письма с языковой константой
    $body = Text::sprintf(
        'COM_KUNENATOPIC2ARTICLE_MAIL_BODY',
        $siteName,
        $topicTitle,
        $topicLink,
        $authorName,
        $articleList
    );

    $subject = Text::sprintf('COM_KUNENATOPIC2ARTICLE_MAIL_SUBJECT', $siteName);

    // Отправка письма администратору и автору
    $adminEmail = $config->get('mailfrom');
    $recipients = [$adminEmail, $authorEmail];

    foreach ($recipients as $email) {
        $mailer->sendMail(
            $adminEmail, // From
            $email,      // To
            $subject,
            $body,
            false        // Plain-text
        );
    }

    // Флаги для View
    $this->emailsSent = true;
    $this->emailsSentTo = $recipients;
}

}
