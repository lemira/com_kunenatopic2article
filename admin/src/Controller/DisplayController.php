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
    /**
     * The default view for the display method.
     * @var    string
     * @since  1.0.0
     */
    protected $default_view = 'topic';

    /**
     * Method to display a view.
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An associative array of URL parameters
     * @return  \Joomla\CMS\MVC\Controller\BaseController|boolean
     * @since   1.0.0
     */
    public function display($cachable = false, $urlparams = [])
    {
        // Получаем приложение и документ
        $app = Factory::getApplication();
        $document = $app->getDocument();
        $input = $app->input;
        
        // Получаем параметры view и format (нужны для getView)
        $vName = $input->getCmd('view', $this->default_view);
        $vFormat = $document->getType();
        
        try {
            // Получаем view
            $view = $this->getView($vName, $vFormat);
            
            if (!$view) {
                throw new \Exception(Text::sprintf('JLIB_APPLICATION_ERROR_VIEW_NOT_FOUND', $vName, $vFormat));
            }
            
            // Получаем модель
            $model = $this->getModel($vName);
            
            if ($model) {
                $view->setModel($model, true);
            }
            
            // Устанавливаем layout и document
            $view->setLayout($input->getCmd('layout', 'default'));
            $view->document = $document;
            
            // Отображаем view
            $view->display();
            
        } catch (\Exception $e) {
            // Логируем ошибку
            $app->enqueueMessage($e->getMessage(), 'error');
            
            // Возвращаемся к базовому отображению
            return parent::display($cachable, $urlparams);
        }
        
        return $this;
    }

    /**
     * Сохранение параметров (Remember)
     */
    public function save()
    {
        // Получаем приложение
        $app = Factory::getApplication();
        
        // Получаем данные из формы
        $data = $this->input->post->get('jform', [], 'array');

        // Получаем модель
        $model = $this->getModel('Topic');

        if (!$model) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_MODEL_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic', false));
            return false;
        }

        // Пытаемся сохранить данные
        if ($model->save($data)) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_REMEMBERED'), 'success');
        } else {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED'), 'error');
        }

        // Редирект обратно на форму
        $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic', false));
        return true;
    }

    /**
     * Сброс параметров к значениям по умолчанию (Reset)
     */
    public function reset()
    {
        // Получаем приложение
        $app = Factory::getApplication();
        
        // Получаем модель
        $model = $this->getModel('Topic');

        if (!$model) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_MODEL_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic', false));
            return false;
        }

        // Пытаемся сбросить параметры
        if ($model->reset()) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_RESET'), 'success');
        } else {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED'), 'error');
        }

        // Редирект обратно на форму
        $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic', false));
        return true;
    }

    /**
     * Создание статей (Create Articles)
     */
    public function create()
    {
        // Получаем приложение
        $app = Factory::getApplication();
        
        // Получаем модель
        $model = $this->getModel('Topic');

        if (!$model) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_MODEL_NOT_FOUND'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic', false));
            return false;
        }

        // Проверяем, что параметры были запомнены
        if (!$model->getParamsRemembered()) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_PLEASE_REMEMBER_PARAMS_FIRST'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic', false));
            return false;
        }

        // Выполняем создание статей
        if ($model->createArticles()) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLES_CREATED'), 'success');
        } else {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_CREATE_FAILED'), 'error');
        }

        // Редирект обратно на форму
        $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic', false));
        return true;
    }

    /**
     * Method to get a model object, loading it if required.
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel|boolean
     * @since   1.0.0
     */
    public function getModel($name = '', $prefix = '', $config = [])
    {
        if (empty($name)) {
            $name = $this->input->get('view', $this->default_view);
        }
        
        // В Joomla 5 используем parent::getModel без префикса
        return parent::getModel($name, '', $config);
    }
}
