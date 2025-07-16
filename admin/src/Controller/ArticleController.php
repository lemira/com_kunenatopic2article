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
    // Проверка токена
    $this->checkToken() or die(Text::_('JINVALID_TOKEN'));

    $app = Factory::getApplication();
    $app->enqueueMessage('create() в ArticleController', 'info'); // ОТЛАДКА

    try {
        $model = $this->getModel('Article', 'Administrator');
        
        // Получаем параметры из таблицы kunenatopic2article_params
        $params = $this->getComponentParams();
        
        if (empty($params) || empty($params->topic_selection)) {        // НЕ НУЖНО, УБРАТЬ?
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_TOPIC_SELECTED'), 'error');
            $this->setRedirect('index.php?option=com_kunenatopic2article');
            return false;
        }

   //     $app->enqueueMessage('До перехода в ArticleModel', 'info'); // ОТЛАДКА
        
        // Создаем статьи
        $articleLinks = $model->createArticlesFromTopic($params);
        
       $app->enqueueMessage('После возвращения из ArticleModel', 'info'); // ОТЛАДКА

        // Отправляем письма
        $mailResult = $this->sendLinksToAdministrator($articleLinks);
        
        // Сохраняем состояние
        $model->setState('articleLinks', $articleLinks);
        $model->emailsSent = $mailResult['success'];
        $model->emailsSentTo = $mailResult['recipients']; // Гарантированно массив

        // Сохраняем модель для View
        $this->app->setUserState('com_kunenatopic2article.model', $model);
        
        $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLES_CREATED_SUCCESSFULLY'), 'success'); // ОТЛАДКА
        $app->setUserState('com_kunenatopic2article.can_create', false); // управление флагом can_create
        
        return true;
        
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
protected function sendLinksToAdministrator(array $articleLinks): array
{
     // Временный код для тестирования в НАЧАЛО метода)
    if (Factory::getApplication()->isClient('administrator')) {
        $logData = [
            'date' => date('Y-m-d H:i:s'),
            'articles' => $articleLinks,
            'subject' => $this->subject,
            'author_id' => $this->topicAuthorId
        ];
        
        file_put_contents(JPATH_ROOT.'/logs/kunena_mail_test.log', 
            json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", 
            FILE_APPEND);
            
        return ['success' => true, 'recipients' => ['test_admin@example.com', 'test_author@example.com']];
    }

    // ОСНОВНОЙ КОД! (временный убрать!)
    $app = Factory::getApplication();
    $result = [
        'success' => false,
        'recipients' => []
    ];

    try {
        $config = Factory::getConfig();
        $mailer = Factory::getMailer();
        
        // Получаем email администратора
        $adminEmail = $config->get('mailfrom');
        
        // Получаем email автора
        $author = Factory::getUser($this->topicAuthorId);
        $authorEmail = $author->email;
        
        // Формируем письмо
        $subject = Text::sprintf('COM_KUNENATOPIC2ARTICLE_MAIL_SUBJECT', $config->get('sitename'));
        $body = Text::sprintf(
            'COM_KUNENATOPIC2ARTICLE_MAIL_BODY',
            $config->get('sitename'),
            $this->subject,
            Uri::root() . 'index.php?option=com_kunena&view=topic&postid=' . (int)$this->params->topic_selection,
            $author->name,
            implode("\n", array_map(
                fn($link) => "- {$link['title']}: {$link['url']}",
                $articleLinks
            ))
        );

        // Настраиваем отправку
        $mailer->setSender([$adminEmail, $config->get('sitename')]);
        $mailer->setSubject($subject);
        $mailer->setBody($body);
        
        // Добавляем получателей
        $recipients = array_filter([$adminEmail, $authorEmail], 'filter_var', FILTER_VALIDATE_EMAIL);
        foreach ($recipients as $email) {
            $mailer->addRecipient($email);
        }
        
        // Отправляем
        $sendResult = $mailer->Send();
        
        $result['success'] = $sendResult === true;
        $result['recipients'] = $recipients;
        
    } catch (\Exception $e) {
        $app->enqueueMessage('Ошибка отправки почты: ' . $e->getMessage(), 'error');
    }
    
    return $result;
}

}
