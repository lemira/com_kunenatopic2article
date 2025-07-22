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
use Joomla\CMS\Router\Route;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

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
    $this->checkToken('post') or jexit(Text::_('JINVALID_TOKEN'));

    $app = Factory::getApplication();
   
    try {
        $model = $this->getModel('Article', 'Administrator');
        $params = $this->getComponentParams();
        
        if (empty($params) || empty($params->topic_selection)) {
            throw new \RuntimeException(Text::_('COM_KUNENATOPIC2ARTICLE_NO_TOPIC_SELECTED'));
        }

        // Создание статей
        error_log('Starting createArticlesFromTopic');
        $articleLinks = $model->createArticlesFromTopic($params);
        error_log('createArticlesFromTopic completed: ' . print_r($articleLinks, true));

  // Отправка писем (мок для тестирования)
        try {
            $mailResult = ['success' => true, 'recipients' => ['test@example.com']];  // $mailResult = $this->sendLinksToAdministrator($articleLinks);
        } catch (\Exception $e) {
            $mailResult = ['success' => false, 'recipients' => []];
            $app->enqueueMessage($e->getMessage(), 'warning');
            error_log('Mail error: ' . $e->getMessage());
        }

        // Устанавливаем флаг блокировки
        Factory::getApplication()->setUserState('com_kunenatopic2article.can_create', false);

         // Формируем данные для передачи для представления
        $resultData = [
             'articles' => $articleLinks,
            'emails' => [
                 'sent' => $mailResult['success'] ?? false, // Защита от undefined
            'recipients' => $mailResult['recipients'] ?? []
            ]
        ];
       // Отправляем данные через сессию
       $app->setUserState('com_kunenatopic2article.result_data', $resultData);
        error_log('Art Contr: Данные для вью: ' . print_r($resultData, true));

   // Получаем фабрику MVC из контейнера зависимостей
            $factory = $app->get('MVCFactory');
            
    // С помощью фабрики создаем экземпляр нашего Result View
           $view = $factory->createView(   //    Указываем имя ('Result'), префикс ('Administrator'), тип ('Html')
                'Result',
                'Administrator',
                ['name' => 'Html']
            );

            // Явно вызываем метод display() этого вью. Joomla найдет его, так как фабрика знает все о правильной структуре.
            $view->display();

            // Возвращаем true для индикации успешного завершения
            return true;
        
    } catch (\Exception $e) {
        $app->enqueueMessage($e->getMessage(), 'error');
        error_log('Error in create: ' . $e->getMessage());
        $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article', false));
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
/**     // Временный код для тестирования в НАЧАЛО метода)
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
**/ 
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
