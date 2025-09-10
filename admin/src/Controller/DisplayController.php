<?php
/**
 * @package     KunenaTopic2Article
 * @subpackage  Administrator
 */
namespace Joomla\Component\KunenaTopic2Article\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

class DisplayController extends BaseController
{
    protected $default_view = 'topic';
    
   public function display($cachable = false, $urlparams = array())
    {
        // Всегда используем view по умолчанию ('topic')
        $this->input->set('view', $this->default_view);
        
        // Инициализируем can_create = false при первой загрузке или прямом вызове без task
        $app = Factory::getApplication();
        $task = $app->input->get('task');
        
        // Сбрасываем can_create в false если:
        // - нет task (первая загрузка/новый вызов компонента)
        // - task = display (прямое обращение к display)
        if ($task === null || $task === 'display' || $task === '') {
            $app->setUserState('com_kunenatopic2article.can_create', false);
        }
        
        return parent::display($cachable, $urlparams);
    }
    
    public function getModel($name = '', $prefix = '', $config = [])
    {
        if (empty($name)) {
            $name = $this->input->get('view', $this->default_view);
        }
        return parent::getModel($name, '', $config);
    }

    public function save()
    {
 $this->checkToken();
        $model = $this->getModel('Topic');
        $data = $this->input->get('jform', [], 'array');

        if ($model->save($data)) {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_SAVED');
            $type = 'success';
           // Активируем кнопки Create и Preview после успешного сохранения
           Factory::getApplication()->setUserState('com_kunenatopic2article.can_create', true);
        } else {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED');
            $type = 'error';
        }

        $this->setRedirect(
            Route::_('index.php?option=com_kunenatopic2article&view=topic', false),
            $message,
            $type
        );
    }

    public function reset()
    {
        $model = $this->getModel('Topic');
        if ($model->reset()) {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_RESET');
            $type = 'success';
        } else {
            $message = Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED');
            $type = 'error';
        }

        // Деактивируем кнопки Create и Preview после сброса
        Factory::getApplication()->setUserState('com_kunenatopic2article.can_create', false);
        
        $this->setRedirect(
            Route::_('index.php?option=com_kunenatopic2article&view=topic', false),
            $message,
            $type
        );
    }

// function create() в ArticleController

} // КОНЕЦ КЛАССА
