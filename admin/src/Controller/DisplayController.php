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

    public function save()
    {
        $this->checkToken();
        $data = $this->input->get('jform', [], 'array');

        // Загружаем модель динамически
        $this->model = $this->getModel('Topic');
        if (!$this->model) {
            throw new \RuntimeException('Model Topic not loaded in save');
        }

        if ($this->model->save($data)) {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_SAVED');
            $type = 'success';
            Factory::getApplication()->setUserState('com_kunenatopic2article.can_create', true);
        } else {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED');
            $type = 'error';
        }

        // Явно указываем представление
        $view = $this->getView('topic', 'html', '', ['base_path' => JPATH_COMPONENT_ADMINISTRATOR . '/src/View']);
        if (!$view) {
            throw new \RuntimeException('View topic not created');
        }

        $view->message = $message;
        $view->messageType = $type;
        $view->display();
        return true;
    }

    public function reset()
    {
        // Загружаем модель динамически
        $this->model = $this->getModel('Topic');
        if (!$this->model) {
            throw new \RuntimeException('Model Topic not loaded in reset');
        }

        if ($this->model->reset()) {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_RESET');
            $type = 'success';
        } else {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED');
            $type = 'error';
        }

        // Явно указываем представление
        $view = $this->getView('topic', 'html', '', ['base_path' => JPATH_COMPONENT_ADMINISTRATOR . '/src/View']);
        if (!$view) {
            throw new \RuntimeException('View topic not created');
        }

        $view->message = $message;
        $view->messageType = $type;
        $view->display();
        return true;
    }

    // function create() в в ArticleController

} // КОНЕЦ КЛАССА
