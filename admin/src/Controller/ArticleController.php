  <?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   Copyright (C) 2023 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Kunenatopic2article\Controller;

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Article Controller
 * @since  0.0.1
 */
class ArticleController extends AdminController
{
    /**
     * Создание статей из темы форума Kunena
     * @return  void
     */
    public function create()
    {
        // Check for request forgeries
        $this->checkToken();

        $app = Factory::getApplication();
        $input = $app->input;
        
        /** @var \Joomla\Component\Kunenatopic2article\Administrator\Model\ArticleModel $model */
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

        // Проверяем существование темы по first_post_id
        if (!$this->validateTopic($topicId)) {
            $app->redirect('index.php?option=com_kunenatopic2article');
            return;
        }

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
            $app->setUserState('com_kunenatopic2article.can_create', false); // управление флагом can_create
        } catch (\Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }
        
        // Перенаправляем на страницу с результатами
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=result');
        return true;
    }

    /**
     * Получение параметров компонента из таблиц
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
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Проверка существования темы по first_post_id
     * @param   int  $topicId  ID темы (first_post_id)
     * @return  boolean  True если тема существует, False если нет
     */
    private function validateTopic($topicId)
    {
        try {
            $app = Factory::getApplication();
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName(['id', 'subject']))
                ->from($db->quoteName('#__kunena_topics'))
                ->where($db->quoteName('first_post_id') . ' = ' . $db->quote($topicId));
            $topic = $db->setQuery($query)->loadObject();

            if (!$topic) {
                $app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_ERROR_INVALID_TOPIC_ID', $topicId), 'error');
                return false;
            }

            // Показать заголовок темы
            $app->enqueueMessage('Тема: ' . $topic->subject, 'message');
            return true;
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

   
    /**
     * Сохранение параметров темы
     * @return void
     * @throws \Exception
     */
    public function save()
    {
        // Check for request forgeries
        $this->checkToken();
        
        $app = Factory::getApplication();
        
        /** @var \Joomla\Component\Kunenatopic2article\Administrator\Model\TopicModel $model */
        $model = $this->getModel('Topic');
        $data = $app->input->get('jform', [], 'array');
        
        if ($model->save($data)) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_SUCCESS'), 'success');
            $app->setUserState('com_kunenatopic2article.can_create', true);  // управление флагом can_create
        } else {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED'), 'error');
        }
        
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topic');
    }

    /**
     * Сброс параметров темы к значениям по умолчанию
     * @return void
     * @throws \Exception
     */
    public function reset()
    {
        // Check for request forgeries
        $this->checkToken();
        
        $app = Factory::getApplication();
        
        /** @var \Joomla\Component\Kunenatopic2article\Administrator\Model\TopicModel $model */
        $model = $this->getModel('Topic');
        
        if ($model->reset()) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_RESET_SUCCESS'), 'success');
            $app->setUserState('com_kunenatopic2article.can_create', false); // управление флагом can_create
        } else {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED'), 'error');
        }
        
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topic');
    }
 /**
     * Отправка ссылок на созданные статьи администратору
     * @param   array  $articleLinks  Массив ссылок на статьи
     * @return  boolean  True в случае успеха, False в случае ошибки
   
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
   }
  }
      */
  
  /**  заготовки кода, возможны ошибки
            // для sendLinksToAdministrator Здесь должен быть код для отправки личного сообщения администратору
            // Используйте API Kunena или другой подходящий метод
            
            // Пример интеграции с системой сообщений Kunena:
            // if (class_exists('KunenaForum') && \KunenaForum::installed()) {
                // Реализация отправки сообщения через Kunena API
            //    $this->sendKunenaMessage($messageText);
            // } else {
                // Альтернативный способ - отправка email
            //    $this->sendEmailToAdmin($messageText);
            }

            return true;
        } catch (\Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }
      */

    /**
     * Отправка сообщения через Kunena API
     * @param   string  $messageText  Текст сообщения
     * @return  boolean
    
    private function sendKunenaMessage($messageText)
    {
        try {
            // Получаем администратора (первый пользователь с правами Super User)
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select('u.id')
                ->from($db->quoteName('#__users', 'u'))
                ->join('LEFT', $db->quoteName('#__user_usergroup_map', 'm') . ' ON u.id = m.user_id')
                ->where($db->quoteName('m.group_id') . ' = 8') // Super Users
                ->setLimit(1);
            
            $adminId = $db->setQuery($query)->loadResult();
            
            if (!$adminId) {
                return false;
            }

            // Здесь должна быть реализация отправки через Kunena API
            // Пример кода будет зависеть от версии Kunena
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
     */

    /**
     * Отправка email администратору
     * @param   string  $messageText  Текст сообщения
     * @return  boolean
     
    private function sendEmailToAdmin($messageText)
    {
        try {
            $app = Factory::getApplication();
            $config = Factory::getConfig();
            
            $mailer = Factory::getMailer();
            $mailer->setSender([$config->get('mailfrom'), $config->get('fromname')]);
            $mailer->addRecipient($config->get('mailfrom'));
            $mailer->setSubject(Text::_('COM_KUNENATOPIC2ARTICLE_NEW_ARTICLES_CREATED'));
            $mailer->setBody($messageText);
            
            return $mailer->Send();
        } catch (\Exception $e) {
            return false;
        }
    }
    */

}
