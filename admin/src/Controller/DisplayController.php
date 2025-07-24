<?php
/**
 * @package     KunenaTopic2Article
 * @subpackage  Administrator
 */
namespace Joomla\Component\KunenaTopic2Article\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;

class DisplayController extends BaseController
{
    /** @var \Joomla\CMS\MVC\Model\BaseDatabaseModel */
    protected $model;

    public function __construct($config = [])
    {
        parent::__construct($config);
        // Инициализация модели один раз для всех методов
        $this->model = $this->getModel('Topic', 'Administrator');
        if (!$this->model) {
            throw new \RuntimeException('Model Topic not loaded');
        }
        // Деактивируем кнопку Create при первоначальном вызове контроллера
        Factory::getApplication()->setUserState('com_kunenatopic2article.can_create', false);
    }

    public function save()
    {
        $this->checkToken();
        $data = $this->input->get('jform', [], 'array');

        // Выполняем сохранение
        if ($this->model->save($data)) {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_SAVED');
            $type = 'success';
            // Активируем кнопку Create после успешного сохранения
            Factory::getApplication()->setUserState('com_kunenatopic2article.can_create', true);
        } else {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED');
            $type = 'error';
        }

        // Создаем представление topic напрямую
        $view = $this->getView('topic', 'html');
        if (!$view) {
            throw new \RuntimeException('View object not created for topic');
        }

        // Передаем сообщение и тип в представление
        $view->message = $message;
        $view->messageType = $type;

        // Отображаем представление
        $view->display();
        return true;
    }

    public function reset()
    {
        // Выполняем сброс
        if ($this->model->reset()) {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_RESET');
            $type = 'success';
        } else {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED');
            $type = 'error';
        }

        // Деактивируем кнопку Create после сброса параметров
        Factory::getApplication()->setUserState('com_kunenatopic2article.can_create', false);

        // Создаем представление topic напрямую
        $view = $this->getView('topic', 'html');
        if (!$view) {
            throw new \RuntimeException('View object not created for topic');
        }

        // Передаем сообщение и тип в представление
        $view->message = $message;
        $view->messageType = $type;

        // Отображаем представление
        $view->display();
        return true;
    }

    // function create() в в ArticleController

} // КОНЕЦ КЛАССА
