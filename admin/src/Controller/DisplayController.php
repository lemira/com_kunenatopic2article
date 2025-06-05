<?php
namespace Joomla\Component\KunenaTopic2Article\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;

/**
 * Main Controller for KunenaTopic2Article component
 *
 * @since  0.0.1
 */
class DisplayController extends BaseController
{
    /**
     * The default view for the display method
     *
     * @var    string
     * @since  0.0.1
     */
    protected $default_view = 'Topic';

    /**
     * Method to display a view
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe url parameters and their variable types
     *
     * @return  static  This object to support chaining
     *
     * @since   0.0.1
     */
    public function display($cachable = false, $urlparams = [])
    {
        $document = Factory::getApplication()->getDocument();
        $input = Factory::getApplication()->input;
        $vName = $input->get('view', $this->default_view);
        $vFormat = $document->getType();
        $lName = $input->get('layout', 'default');
        
        // Get and render the view.
        if ($view = $this->getView($vName, $vFormat)) {
            // Get the model for the view.
            $model = $this->getModel($vName);
            
            // Push the model into the view (as default).
            if ($model) {
                $view->setModel($model, true);
            }
            $view->setLayout($lName);
            // Push document object into the view.
            $view->document = $document;
            $view->display();
        }
        return $this;
    }

    /**
     * Method to get a model object, loading it if required
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  \Joomla\CMS\MVC\Model\BaseDatabaseModel|boolean  Model object on success; otherwise false on failure.
     *
     * @since   0.0.1
     */
    public function getModel($name = '', $prefix = '', $config = [])
    {
        if (empty($name)) {
            $name = $this->input->get('view', $this->default_view);
        }
        
        // В Joomla 5 префикс не нужен, модели загружаются по namespace
        return parent::getModel($name, $prefix, $config);
    }

    /**
     * Save/Remember parameters (обработка кнопки Remember)
     *
     * @return  void
     * @since   0.0.1
     */
    public function save()
    {
        // Проверяем токен безопасности
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = Factory::getApplication();
        $input = $app->input;
        
        try {
            // Получаем модель
            $model = $this->getModel('Topic');
            
            // Получаем данные формы
            $data = $input->post->get('jform', [], 'array');
            
            // Сохраняем параметры
            $result = $model->save($data);
            
            if ($result) {
                // Устанавливаем флаг, что параметры сохранены
                $app->setUserState('com_kunenatopic2article.params.remembered', true);
                
                $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_SAVED_SUCCESS'), 'success');
                
                // Редирект обратно на форму с флагом успеха
                $this->setRedirect(
                    'index.php?option=com_kunenatopic2article&view=topic&params_saved=1'
                );
            } else {
                $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_SAVE_ERROR'), 'error');
                $this->setRedirect('index.php?option=com_kunenatopic2article&view=topic');
            }
            
        } catch (Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            $this->setRedirect('index.php?option=com_kunenatopic2article&view=topic');
        }
    }
    
    /**
     * Reset parameters (обработка кнопки Reset Parameters)
     *
     * @return  void
     * @since   0.0.1
     */
    public function reset()
    {
        // Проверяем токен безопасности
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = Factory::getApplication();
        
        try {
            // Получаем модель
            $model = $this->getModel('Topic');
            
            // Сбрасываем параметры
            $result = $model->reset();
            
            if ($result) {
                // Убираем флаг сохраненных параметров
                $app->setUserState('com_kunenatopic2article.params.remembered', false);
                
                $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_RESET_SUCCESS'), 'warning');
            } else {
                $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_RESET_ERROR'), 'error');
            }
            
        } catch (Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }
        
        // Редирект обратно на форму
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topic');
    }
    
    /**
     * Create articles from topics (обработка кнопки Create Articles)
     *
     * @return  void
     * @since   0.0.1
     */
    public function create()
    {
        // Проверяем токен безопасности
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = Factory::getApplication();
        
        // Проверяем, сохранены ли параметры
        $paramsRemembered = $app->getUserState('com_kunenatopic2article.params.remembered', false);
        
        if (!$paramsRemembered) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_PLEASE_REMEMBER_FIRST'), 'warning');
            $this->setRedirect('index.php?option=com_kunenatopic2article&view=topic');
            return;
        }
        
        try {
            // Получаем модель
            $model = $this->getModel('Topic');
            
            // Создаем статьи (этот метод нужно добавить в модель)
            $result = $model->createArticles();
            
            if ($result && isset($result['success']) && $result['success']) {
                $count = $result['count'] ?? 0;
                $message = Text::sprintf('COM_KUNENATOPIC2ARTICLE_ARTICLES_CREATED_SUCCESS', $count);
                $app->enqueueMessage($message, 'success');
                
                // После создания статей деактивируем кнопку Create Articles
                $app->setUserState('com_kunenatopic2article.params.remembered', false);
                
            } else {
                $error = $result['error'] ?? Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLES_CREATE_ERROR');
                $app->enqueueMessage($error, 'error');
            }
            
        } catch (Exception $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
        }
        
        // Редирект обратно на форму
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topic');
    }
}
