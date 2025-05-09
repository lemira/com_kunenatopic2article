<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

class KunenaTopic2ArticleControllerTopic extends BaseController
{
    public function save()
    {
        // Проверка токена формы для безопасности
        $this->checkToken();

        // Получаем данные из формы
        $app = Factory::getApplication();
        $input = $app->input;
        $data = $input->get('jform', array(), 'array');

        // Проверка ID темы
        $topicId = $data['topic_selection'] ?? 0;
        if ($topicId > 0) {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName('#__kunena_topics'))
                ->where($db->quoteName('id') . ' = ' . (int) $topicId)
                ->where($db->quoteName('first_post_id') . ' = ' . (int) $topicId);
            $db->setQuery($query);
            $topicExists = $db->loadResult();

            if (!$topicExists) {
                $app->enqueueMessage(Text::sprintf('COM_KUNENATOPIC2ARTICLE_ERROR_INVALID_TOPIC_ID', $topicId), 'error');
                $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topics', false));
                return;
            }
        }

        // Загружаем модель
        $model = $this->getModel('Topics', 'KunenaTopic2ArticleModel');

        // Сохраняем данные через модель
        if ($model->saveParameters($data)) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SUCCESS_PARAMETERS_SAVED'), 'success');
        } else {
            $app->enqueueMessage(Text::_('Error saving parameters'), 'error');
        }

        // Перенаправляем обратно на форму
        $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topics', false));
    }

    public function reset()
    {
        // Загружаем модель
        $model = $this->getModel('Topics', 'KunenaTopic2ArticleModel');

        // Сбрасываем параметры
        if ($model->resetParameters()) {
            Factory::getApplication()->enqueueMessage(Text::_('Parameters reset successfully'), 'success');
        } else {
            Factory::getApplication()->enqueueMessage(Text::_('Error resetting parameters'), 'error');
        }

        // Перенаправляем обратно на форму
        $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topics', false));
    }

    public function createarticles()
    {
        // Пока пустой метод, функционал добавим позже
        Factory::getApplication()->enqueueMessage(Text::_('Functionality not yet implemented'), 'info');
        $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topics', false));
    }
}
