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
        // Устанавливаем заголовки для JSON сразу
        header('Content-Type: application/json');
        
        try {
           // Проверка токена безопасности
            try {
                $this->checkToken('POST');
            //    error_log('Token check passed');
            } catch (\Exception $e) {
                $token = $this->input->get(Session::getFormToken(), '', 'alnum');
                if (empty($token)) {
                    throw new \Exception('Invalid token: ' . $e->getMessage());
                }
              }
            
          /** @var \Joomla\Component\KunenaTopic2Article\Administrator\Model\ArticleModel $model */
            $model = $this->getModel('Article', 'Administrator');
            
            if (!$model) {
                throw new \Exception('Could not get Article model');
            }
            
        //    error_log('Creating preview article...');
            // Создаем временную статью для preview
            $articleData = $model->createArticlesFromTopic(true); // $isPreview = true
            
            if (!$articleData || !isset($articleData['id'])) {
                throw new \Exception(Text::_('COM_KUNENATOPIC2ARTICLE_ERROR_PREVIEW_ARTICLE_CREATION_FAILED'));
            }
            
            // Формируем URL для фронтенда
           $previewUrl = Uri::root() . 'index.php?option=com_content&view=article&id=' // кл,дс
          . $articleData['id'] 
         . '&preview=1';  // '&tmpl=component' - выдает в окно т статью, НО ПОЛУЧАЕТСЯ НЕКРАСИВО (дс)
            
            // Декодируем HTML-сущности
            $previewUrl = html_entity_decode($previewUrl, ENT_QUOTES, 'UTF-8');
            
            // Формируем успешный ответ
            $response = [
                'success' => true,
                'data'    => [
                    'url' => $previewUrl,
                    'id'  => $articleData['id']
                ]
            ];
            
           echo json_encode($response);
            
        } catch (\Exception $e) {
          $errorResponse = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            
            http_response_code(500);
            echo json_encode($errorResponse);
        }
        
        Factory::getApplication()->close();
    }

    /**
 * Отображает статью для предпросмотра (frontend)
 * @return void
 */
public function displayPreview(): void
{
    $app = Factory::getApplication();
    $id = $app->input->getInt('id');
    
    if (!$id) {
        throw new \Exception('Article ID not specified');
    }
    
    // Получаем статью напрямую из БД, игнорируя состояние
    $db = Factory::getDbo();
    $query = $db->getQuery(true)
        ->select('*')
        ->from('#__content')
        ->where('id = ' . (int)$id);
    
    $db->setQuery($query);
    $article = $db->loadObject();
    
    if (!$article) {
        throw new \Exception('Article not found');
    }
    
    // Просто рендерим статью
    header('Content-Type: text/html; charset=utf-8');
    echo $article->introtext . $article->fulltext;
    $app->close();
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
