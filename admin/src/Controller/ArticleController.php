<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Controller;

defined('_JEXEC') or die('Restricted access');

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
        // Устанавливаем заголовки для JSON сразу
        header('Content-Type: application/json');
        
        try {
            // Отладка
            error_log('Preview method started');
            
            // Проверка токена безопасности
            try {
                $this->checkToken('POST');
                error_log('Token check passed');
            } catch (\Exception $e) {
                error_log('Token check failed: ' . $e->getMessage());
                $token = $this->input->get(Session::getFormToken(), '', 'alnum');
                if (empty($token)) {
                    throw new \Exception('Invalid token: ' . $e->getMessage());
                }
                error_log('Alternative token check passed');
            }
            
            error_log('Getting model...');
            /** @var \Joomla\Component\KunenaTopic2Article\Administrator\Model\ArticleModel $model */
            $model = $this->getModel('Article', 'Administrator');
            
            if (!$model) {
                throw new \Exception('Could not get Article model');
            }
            
            error_log('Creating preview article...');
            // Создаем временную статью для preview
            $articleData = $model->createArticlesFromTopic(true); // $isPreview = true
            
            error_log('Article data: ' . print_r($articleData, true));
            
            if (!$articleData || !isset($articleData['id'])) {
                throw new \Exception(Text::_('COM_KUNENATOPIC2ARTICLE_ERROR_PREVIEW_ARTICLE_CREATION_FAILED'));
            }
            
            // Формируем URL для фронтенда
            $previewUrl = Uri::root() . 'index.php?option=com_content&view=article&id='
                . $articleData['id'] . ':' . $articleData['alias']
                . '&catid=' . $articleData['catid']
                . '&tmpl=component';
                
            // Декодируем HTML-сущности
            $previewUrl = html_entity_decode($previewUrl, ENT_QUOTES, 'UTF-8');
            
            error_log('Preview URL: ' . $previewUrl);
            
            // Формируем успешный ответ
            $response = [
                'success' => true,
                'data'    => [
                    'url' => $previewUrl,
                    'id'  => $articleData['id']
                ]
            ];
            
            error_log('Sending response: ' . json_encode($response));
            echo json_encode($response);
            
        } catch (\Exception $e) {
            error_log('Preview error: ' . $e->getMessage());
            error_log('Preview error trace: ' . $e->getTraceAsString());
            
            $errorResponse = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            
            http_response_code(500);
            echo json_encode($errorResponse);
        }
        
        Factory::getApplication()->close();
    }
    
    public function deletePreview(): void
    {
        // Устанавливаем заголовки для JSON сразу
        header('Content-Type: application/json');
        
        try {
            error_log('DeletePreview method started');
            
            // Проверка токена
            try {
                $this->checkToken('POST');
                error_log('Delete token check passed');
            } catch (\Exception $e) {
                error_log('Delete token check failed: ' . $e->getMessage());
                $token = $this->input->get(Session::getFormToken(), '', 'alnum');
                if (empty($token)) {
                    throw new \Exception('Invalid delete token: ' . $e->getMessage());
                }
                error_log('Delete alternative token check passed');
            }
            
            $id = $this->input->getInt('id');
            error_log('Delete ID received: ' . $id);
            
            if (!$id) {
                throw new \Exception(Text::_('COM_KUNENATOPIC2ARTICLE_ERROR_PREVIEW_NO_ID_PROVIDED'));
            }
            
            error_log('Getting model for delete...');
            /** @var \Joomla\Component\KunenaTopic2Article\Administrator\Model\ArticleModel $model */
            $model = $this->getModel('Article', 'Administrator');
            
            if (!$model) {
                throw new \Exception('Could not get Article model for delete');
            }
            
            error_log('Calling deletePreviewArticleById...');
            $deleteResult = $model->deletePreviewArticleById($id);
            
            error_log('Delete result: ' . ($deleteResult ? 'success' : 'failed'));
            
            if (!$deleteResult) {
                throw new \Exception(Text::_('COM_KUNENATOPIC2ARTICLE_ERROR_PREVIEW_DELETE_FAILED'));
            }
            
            $response = ['success' => true, 'message' => 'Preview deleted.'];
            error_log('Delete response: ' . json_encode($response));
            
            echo json_encode($response);
            
        } catch (\Exception $e) {
            error_log('Delete error: ' . $e->getMessage());
            error_log('Delete error trace: ' . $e->getTraceAsString());
            
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
