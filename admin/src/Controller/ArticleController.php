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
use RuntimeException;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri; // ?
use Joomla\CMS\Mail\Mailer; // ?
use Joomla\Component\Users\Administrator\Model\UsersModel; // ?
use Joomla\CMS\MVC\Factory\MVCFactoryInterface; // ?
use Joomla\CMS\Dispatcher\AbstractController;  // ?


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
    $this->checkToken();

    $app = Factory::getApplication();
     $isPreview = $this->input->getBool('is_preview', false);
    
    try {
        $model = $this->getModel('Article', 'Administrator');
        $model->setState('is_preview', $isPreview);
        
        // Получаем параметры
        $params = $this->getComponentParams(); 

if (empty($params) || empty($params->topic_selection)) {
    throw new \RuntimeException(Text::_('COM_KUNENATOPIC2ARTICLE_NO_TOPIC_SELECTED'));
}
        
        // Создаем статьи
        $articleLinks = $model->createArticlesFromTopic($params);

       // Режим preview
       if ($isPreview && ($articleId = $model->getLastArticleId())) {
            // Сохраняем ID статьи в сессии для последующего удаления
            $app->setUserState('com_kunenatopic2article.preview_article_id', $articleId);
            
            // Перенаправляем на просмотр с return-URL
            $returnUrl = Route::_('index.php?option=com_kunenatopic2article&task=article.deletePreviewArticle', false);
            $url = Route::link('site', 'index.php?option=com_content&view=article&id='.$articleId.'&return='.urlencode($returnUrl));
            $this->setRedirect($url);
            return true;
        }
        
        $this->resetTopicSelection();    // Сбрасываем Topic ID после успешного создания статей
        
         // Отправляем уведомления (кроме preview)
        if (!$isPreview) {
            $emailResult = $model->sendLinksToAdministrator($articleLinks);
        }
        
        // Устанавливаем флаг блокировки
        $app->setUserState('com_kunenatopic2article.can_create', false);
        
        // Сохраняем данные для отображения
        $app->setUserState('com_kunenatopic2article.result_data', [
            'articles' => $articleLinks,
            'emails' => [
                'sent' => $emailResult['success'],
                'recipients' => $emailResult['recipients']
            ]
        ]);
        
        // Используем фабрику, встроенную в контроллер 
        $view = $this->getView('result', 'html');
        $view->display();       // Отображаем представление
             return true;

    } catch (\Exception $e) {
        $app->enqueueMessage($e->getMessage(), 'error');
        $this->setRedirect(
            Route::_('index.php?option=com_kunenatopic2article', false)
        );
        return false;
    }
}

    // Метод удаления Preview в контроллере
 public function deletePreviewArticle()
{
    $this->checkToken();

    try {
        $model = $this->getModel('Article');
        if ($articleId = $model->getLastArticleId()) {
            $table = $model->getTable('Content');
            $table->delete($articleId);
        }
    } catch (Exception $e) {
        // Логирование ошибки
        Factory::getApplication()->enqueueMessage($e->getMessage(), 'error', true);
    }

  $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article', false)); 
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
   
private function resetTopicSelection()
{
    try {
        $db = Factory::getContainer()->get('DatabaseDriver');
        $query = $db->getQuery(true)
            ->update('#__kunenatopic2article_params')
            ->set($db->quoteName('topic_selection') . ' = ' . $db->quote('0'))
            ->where($db->quoteName('id') . ' = 1');
        
        $db->setQuery($query);
        $db->execute();
        
    } catch (\Exception $e) {
        Factory::getApplication()->enqueueMessage(
            'Ошибка сброса topic_selection: ' . $e->getMessage(), 
            'error'
        );
    }
}

}
