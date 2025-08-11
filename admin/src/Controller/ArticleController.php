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
use Joomla\CMS\Serializer\JoomlaSerializer;
use Joomla\CMS\MVC\Controller\AdminController;

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
    
    try {
        $model = $this->getModel('Article', 'Administrator');
      
        // Создаем статьи
        $articleLinks = $model->createArticlesFromTopic();

        $this->resetTopicSelection();    // Сбрасываем Topic ID после успешного создания статей
        
         // Отправляем уведомления 
              $emailResult = $model->sendLinksToAdministrator($articleLinks);
            
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

    /** @var \Joomla\Component\KunenaTopic2Article\Administrator\Model\ArticleModel $model */
    $model = $this->getModel('Article', 'Administrator');    

    try {
        // Создаем временную статью для preview
        $articleData = $model->createPreviewArticle();
        
        if (!$articleData) {
            throw new Exception($model->getError() ?: 'Модель не вернула данные для превью.');
        }
        
        // Формируем URL для фронтенда (не админки)
        $previewUrl = Uri::root() . 'index.php?option=com_content&view=article&id=' . $articleData['id'] . ':' . $articleData['alias'] . '&catid=' . $articleData['catid'] . '&tmpl=component';
        
        $response = [
            'success' => true, 
            'data' => [
                'url' => $previewUrl, 
                'id' => $articleData['id']
            ]
        ];
        
    } catch (Exception $e) {
        $response = [
            'success' => false, 
            'message' => $e->getMessage()
        ];
    }
    
    // Отправляем JSON ответ
    try {
        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (\Exception $e) {
        // Fallback на случай ошибки
        echo '{"success":false,"message":"JSON encode error"}';
    }
    
    $app->close();
}
    
    /**
     * Метод для удаления временной статьи.
     */
   public function deletePreview()
{
    $this->checkToken();
    $model = $this->getModel();
    
    try {
        if ($model->deletePreviewArticle()) {
            // Возвращаем успешный JSON-ответ
            echo new JsonResponse(['success' => true, 'message' => 'Preview article successfully deleted.']);
        } else {
            throw new \Exception('Failed to delete preview article.');
        }
    } catch (\Exception $e) {
        // Возвращаем JSON с ошибкой
        echo new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }

    $this->app->close(); // Завершаем выполнение приложения
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
