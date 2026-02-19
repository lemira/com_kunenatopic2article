<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use RuntimeException;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Response\Json\JsonResponse;

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
          
            // Создаем статьи (обычный режим)
            $articleLinks = $model->createArticlesFromTopic(false); // $isPreview = false

            $this->resetTopicSelection();
            
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
            
            // Отображаем представление результата
            $view = $this->getView('result', 'html');
            $view->display();
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
     * Создает временную статью для предпросмотра, возвращает URL в формате JSON
     * @return void
     */
   public function preview(): void
{
    // 1. Убираем всё, что могло попасть в вывод до этого момента
    if (ob_get_length()) ob_clean();
    
    header('Content-Type: application/json');
    
    try {
        $this->checkToken('POST');
        
        $model = $this->getModel('Article', 'Administrator');
        // $isPreview = true (создает статью со state=0)
        $articleData = $model->createArticlesFromTopic(true); 
        
        if (!$articleData || !isset($articleData['id'])) {
            throw new \Exception('Failed to create preview article');
        }
        
        // Формируем чистую ссылку на фронтенд
        $previewUrl = \Joomla\CMS\Uri\Uri::root() . 'index.php?option=com_content&view=article&id=' . $articleData['id'];
        
        echo json_encode([
            'success' => true,
            'data'    => [
                'url' => $previewUrl,
                'id'  => $articleData['id']
            ]
        ]);
        
    } catch (\Exception $e) {
        // Если ошибка — тоже отдаем JSON, а не HTML страницу
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    // ВАЖНО: Немедленно прерываем выполнение, чтобы Joomla не дописывала HTML
    \Joomla\CMS\Factory::getApplication()->close();
}
    
    public function deletePreview(): void
    {
        // Устанавливаем заголовки для JSON сразу
        header('Content-Type: application/json');
        
        try {
       //     error_log('DeletePreview method started');
            
            // Проверка токена
            try {
                $this->checkToken('POST');
            } catch (\Exception $e) {
               $token = $this->input->get(Session::getFormToken(), '', 'alnum');
                if (empty($token)) {
                    throw new \Exception('Invalid delete token: ' . $e->getMessage());
                }
           }
            
            $id = $this->input->getInt('id');
          // error_log('Delete ID received: ' . $id);
            
            if (!$id) {
                throw new \Exception(Text::_('COM_KUNENATOPIC2ARTICLE_ERROR_PREVIEW_NO_ID_PROVIDED'));
            }
            
            /** @var \Joomla\Component\KunenaTopic2Article\Administrator\Model\ArticleModel $model */
            $model = $this->getModel('Article', 'Administrator');
            
            if (!$model) {
                throw new \Exception('Could not get Article model for delete');
            }
         
            $deleteResult = $model->deletePreviewArticleById($id);
            
            if (!$deleteResult) {
                throw new \Exception(Text::_('COM_KUNENATOPIC2ARTICLE_ERROR_PREVIEW_DELETE_FAILED'));
            }
            
            $response = ['success' => true, 'message' => 'Preview deleted.'];
            
            echo json_encode($response);
            
        } catch (\Exception $e) {
         
            $errorResponse = ['success' => false, 'message' => $e->getMessage()];
            http_response_code(500);
            echo json_encode($errorResponse);
        }
        
        Factory::getApplication()->close();
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
