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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Component\KunenaTopic2Article\Administrator\Repository\ParamsRepository;
use Joomla\Component\KunenaTopic2Article\Administrator\Service\ArticleResultService;

class ArticleController extends BaseController
{
    private ?ArticleResultService $articleResultService = null;

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
            $app->setUserState(
                'com_kunenatopic2article.result_data',
                $this->getArticleResultService()->buildResultData($articleLinks, $emailResult)
            );
            
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
    try {
        $this->checkToken('POST');
        
        $model = $this->getModel('Article', 'Administrator');
        // $isPreview = true (создает статью со state=0)
        $articleData = $model->createArticlesFromTopic(true); 
        
        $this->sendJsonResponse($this->getArticleResultService()->buildPreviewResponse($articleData));
        
    } catch (\Exception $e) {
        $this->sendJsonResponse(null, $e->getMessage(), true, 500);
    }
}
    
    public function deletePreview(): void
    {
        try {
            // Проверка токена
            $this->checkToken('POST');
            
            $id = $this->input->getInt('id');
            
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
            
            $this->sendJsonResponse(null, 'Preview deleted.');
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(null, $e->getMessage(), true, 500);
        }
    }

    private function sendJsonResponse($data = null, ?string $message = null, bool $error = false, int $statusCode = 200): void
    {
        if (ob_get_length()) {
            ob_clean();
        }

        if ($statusCode >= 400) {
            http_response_code($statusCode);
        }

        echo new JsonResponse($data, $message, $error);
        Factory::getApplication()->close();
    }

    private function getArticleResultService(): ArticleResultService
    {
        if ($this->articleResultService === null) {
            $this->articleResultService = new ArticleResultService();
        }

        return $this->articleResultService;
    }
    
    private function resetTopicSelection()
    {
        try {
            $db = Factory::getContainer()->get('DatabaseDriver');
            $paramsRepository = new ParamsRepository($db);
            $paramsRepository->resetTopicSelection();
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage(
                'Ошибка сброса topic_selection: ' . $e->getMessage(), 
                'error'
            );
        }
    }
}
