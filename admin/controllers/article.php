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

use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * Article Controller
 *
 * @since  0.0.1
 */
class KunenaTopic2ArticleControllerArticle extends AdminController
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
            'topic_selection' => $topicId, // 3232, ID первого поста
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
            Factory::getApplication()->setUserState('com_kunenatopic2article.can_create', false); // управление флагом can_create
        } catch (Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }
        
        // Перенаправляем на страницу с результатами
        $app->redirect('index.php?option=com_kunenatopic2article&view=result');
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
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return null;
        }
    }

        // Проверка существования темы по first_post_id
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

     /**
     * Сохранение параметров темы
     *
     * @return void
     * @throws Exception
     */
    public function save()
    {
        $model = $this->getModel('Topic', 'Administrator');
        $data = Factory::getApplication()->input->get('jform', [], 'array');
        
        if ($model->save($data)) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_SUCCESS'), 'success');
            Factory::getApplication()->setUserState('com_kunenatopic2article.can_create', true);  // управление флагом can_create
        } else {
            Factory::getApplication()->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED'), 'error');
        }
        
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
    }

    /**
     * Сброс параметров темы к значениям по умолчанию
     *
     * @return void
     * @throws Exception
     */
    public function reset()
    {
        $model = $this->getModel('Topic', 'Administrator');
        
        if ($model->reset()) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_RESET_SUCCESS'), 'success');
            Factory::getApplication()->setUserState('com_kunenatopic2article.can_create', false); // управление флагом can_create
        } else {
            Factory::getApplication()->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED'), 'error');
        }
        
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
    }    

   /** Если display() не делает ничего особенного, удалим его, и Joomla будет использовать display() из BaseController.
     * Метод по умолчанию для отображения
     *
     * @param bool $cachable Кэшируемый ли запрос
     * @param array $urlparams Параметры URL
     * @return $this
  
    public function display($cachable = false, $urlparams = [])
    {
        Factory::getApplication()->enqueueMessage('Display method called in KunenaTopic2ArticleControllerArticle', 'notice');
        $view = Factory::getApplication()->input->get('view', 'topics');
        $viewObject = Factory::getApplication()->getMVCFactory()->createView(
            ucfirst($view),
            'Html',
            ['base_path' => JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article']
        );
        $model = Factory::getApplication()->getMVCFactory()->createModel('Topic', 'Administrator', ['base_path' => JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article']);
        $viewObject->setModel($model, true);
        $viewObject->display();
        return $this;
    }   
   */
    
}
