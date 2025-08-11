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
        error_log('Controller: Starting preview method');
        
        // вызываем новую функцию createPreviewArticle() 
        $articleData = $model->createPreviewArticle();
        
        // Отладка результата createPreviewArticle
        error_log('createPreviewArticle result: ' . print_r($articleData, true));
        
        if (!$articleData) {
            throw new Exception($model->getError() ?: 'Модель не вернула данные для превью.');
        }
        
        error_log('Controller: About to create preview URL');
        
        // URL для админки
       // $previewUrl = Route::_(
         //   'index.php?option=com_content&view=article&id=' . $articleData['id'] . ':' . $articleData['alias'] . '&catid=' . $articleData['catid'] . '&tmpl=component',
           // false
       // );
        // URL для фронтенда сайта
$previewUrl = Uri::root() . 'index.php?option=com_content&view=article&id=' . $articleData['id'] . ':' . $articleData['alias'] . '&catid=' . $articleData['catid'] . '&tmpl=component';
        
        error_log('Controller: Preview URL created: ' . $previewUrl);
        
        $response = ['success' => true, 'data' => ['url' => $previewUrl, 'id' => $articleData['id']]];
        
      error_log('Controller: About to send JSON response');
error_log('Controller: Response data: ' . print_r($response, true));

try {
    $jsonResponse = new JsonResponse($response);
    error_log('Controller: JsonResponse created successfully');
    
    echo $jsonResponse;
    error_log('Controller: JSON echoed successfully');
    
    $app->close();
    error_log('Controller: App closed successfully');
    
} catch (\Exception $e) {
    error_log('Controller: Exception during JSON response: ' . $e->getMessage());
    error_log('Controller: Exception trace: ' . $e->getTraceAsString());
    
    // Fallback - отправим простой JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    $app->close();
}
    
    /**
     * Метод для удаления временной статьи.
     */
    public function deletePreview(): void
    {
        $this->checkToken();
        $app = Factory::getApplication();
        $id = $app->input->getInt('id');

        try {
            if (!$id) {
                throw new Exception('ID статьи для удаления не предоставлен.');
            }

            $model = $this->getModel('Article', 'Administrator');

            if (!$model->delete($id)) {
                throw new Exception($model->getError() ?: 'Ошибка при удалении статьи из модели.');
            }

            $response = ['success' => true];

        } catch (Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
            error_log('DeletePreview exception: ' . $e->getMessage());
        }

        echo new JsonResponse($response);
        $app->close();
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
