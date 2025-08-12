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
     * Создает временную статью, возвращает URL для предпросмотра в формате JSON.
     *
     * @return void
     * @since  1.0.0
     */
    public function preview(): void
    {
        // Проверка токена безопасности
        $this->checkToken('POST');

        try {
            /** @var \Joomla\Component\KunenaTopic2Article\Administrator\Model\ArticleModel $model */
            $model = $this->getModel('Article', 'Administrator');

            // Создаем временную статью для preview
            $articleData = $model->createPreviewArticle();

            if (!$articleData || !isset($articleData['id'])) {
                // Если модель не смогла создать статью, генерируем ошибку
                throw new \Exception(Text::_('COM_KUNENATOPIC2ARTICLE_ERROR_PREVIEW_ARTICLE_CREATION_FAILED'));
            }

            // Формируем URL для фронтенда (не админки), используя Joomla API
            $previewUrl = Uri::root() . 'index.php?option=com_content&view=article&id='
                . $articleData['id'] . ':' . $articleData['alias']
                . '&catid=' . $articleData['catid']
                . '&tmpl=component'; // tmpl=component убирает все лишнее с сайта

              // Декодируем HTML-сущности перед отправкой, чтобы избежать проблем сзаменой & на &amp; в URL
        $previewUrl = html_entity_decode($previewUrl, ENT_QUOTES, 'UTF-8');

            // Формируем успешный ответ
            $response = [
                'success' => true,
                'data'    => [
                    'url' => $previewUrl,
                    'id'  => $articleData['id']
                ]
            ];

            // Отправляем успешный JSON-ответ и завершаем работу
            echo new JsonResponse($response);

        } catch (\Exception $e) {
            // Если в блоке try произошла любая ошибка, мы ее ловим здесь
            // Формируем JSON-ответ с сообщением об ошибке
            $errorResponse = [
                'success' => false,
                'message' => $e->getMessage()
            ];

            // Отправляем JSON с ошибкой и HTTP-статусом 500 (внутренняя ошибка сервера)
            echo new JsonResponse($errorResponse, 500);
        }

        // В любом случае завершаем приложение, чтобы Joomla не пыталась рендерить HTML
        Factory::getApplication()->close();
    }
    
     public function deletePreview(): void
    {
        $this->checkToken('POST'); // Или GET, если метод запроса другой

        try {
            $id = $this->input->getInt('id');
            if (!$id) {
                throw new \Exception(Text::_('COM_KUNENATOPIC2ARTICLE_ERROR_PREVIEW_NO_ID_PROVIDED'));
            }

            /** @var \Joomla\Component\KunenaTopic2Article\Administrator\Model\ArticleModel $model */
            $model = $this->getModel('Article', 'Administrator');

            // Передаем ID в модель для удаления
            if (!$model->deletePreviewArticleById($id)) {
                 throw new \Exception(Text::_('COM_KUNENATOPIC2ARTICLE_ERROR_PREVIEW_DELETE_FAILED'));
            }
            
            // Если все прошло успешно
            echo new JsonResponse(['success' => true, 'message' => 'Preview deleted.']);

        } catch (\Exception $e) {
            // Если что-то пошло не так
            echo new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
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
