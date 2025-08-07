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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\Json\JsonResponse;


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

       if ($isPreview && ($articleId = $model->getLastArticleId())) {
            $this->setRedirect(
                Route::link(
                    'site', 
                    'index.php?option=com_content&view=article&id='.$articleId.'&tmpl=component&return='.urlencode(
                        Route::_('index.php?option=com_kunenatopic2article&task=article.deletePreviewArticle', false)
                    ),
                    false
                )
            );
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

/**
     * Метод для создания временной статьи и возврата URL.
     */
    public function preview(): void
    {
        $this->checkToken('POST');
        $app = Factory::getApplication();
        $data = $app->input->post->get('jform', [], 'array');

        // Читаем флаг из запроса
         $isPreview = $app->input->getBool('is_preview', false);

        /** @var \Joomla\Component\KunenaTopic2Article\Administrator\Model\ArticleModel $model */
        $model = $this->getModel('Article');
        
        // Вызываем основную функцию createArticle, передавая ей данные и флаг
        $articleData = $model->createArticlesFromTopic($data, $isPreview);

        if ($articleData) {
            $previewUrl = Route::_(
                'index.php?option=com_content&view=article&id=' . $articleData['id'] . ':' . $articleData['alias'] . '&catid=' . $articleData['catid'] . '&tmpl=component',
                false
            );

            $app->enqueueMessage(new JsonResponse(['success' => true, 'data' => ['url' => $previewUrl, 'id' => $articleData['id']]]));
        } else {
            $app->enqueueMessage(new JsonResponse(['success' => false, 'message' => $model->getError()], 500));
        }
    }

    /**
     * Метод для удаления временной статьи.
     */
    public function deletePreview(): void
    {
        $this->checkToken('POST');
        $app = Factory::getApplication();
        $id = $app->input->getInt('id');

        if ($id) {
            /** @var \YourNamespace\Component\Kunenatopic2article\Administrator\Model\ArticleModel $model */
            $model = $this->getModel('Article');
            if ($model->delete($id)) {
                 $app->enqueueMessage(new JsonResponse(['success' => true]));
            } else {
                 $app->enqueueMessage(new JsonResponse(['success' => false, 'message' => $model->getError()], 500));
            }
        } else {
            $app->enqueueMessage(new JsonResponse(['success' => false, 'message' => 'No article ID provided'], 400));
        }
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
