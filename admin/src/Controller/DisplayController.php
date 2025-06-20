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
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

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

          /** проверку наличия Kunena и NBBC-парсера пропускаю, выдалась ошибка Kunena is not installed.
       // Проверяем наличие Kunena
        if (!class_exists('KunenaFactory')) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_YOURCOMPONENT_KUNENA_NOT_INSTALLED'), 'error');
            $this->setRedirect('index.php?option=com_yourcomponent');
            return false;
        }
        // Проверяем наличие NBBC-парсера
        if (!file_exists(JPATH_BASE . '/libraries/kunena/External/Nbbc/Nbbc.php') && !class_exists('KunenaBbcode')) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_YOURCOMPONENT_BBCODE_PARSER_NOT_FOUND'), 'error');
            $this->setRedirect('index.php?option=com_yourcomponent');
            return false;
        }
        */
        
        // Получаем приложение и документ
        $app = Factory::getApplication();
        // При каждой загрузке формы принудительно сбрасываем флаг успеха. дж
        // Это гарантирует, что кнопка "Create" всегда будет неактивна при первом показе.
        // ??!! это не работает Create деактивирована не только при вызове компонента, но и после save 
        // ??!! $app->setUserState('com_kunenatopic2article.save.success', false);
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

    /**
     * Метод для сохранения параметров (Remember)
     * @since 1.0.0
     */
    public function save()
    {
         $this->checkToken(); // Для безопасности в Joomla нужно проверять CSRF-токен перед редиректом:
        // Получаем модель
        $model = $this->getModel('Topic');
        $data = $this->input->get('jform', [], 'array');

        if ($model->save($data)) {
            $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic'), Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_SAVED'), 'success');
        } else {
            $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic'), Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED'), 'error');
        }
    }

    /**
     * Метод для сброса параметров (Reset)
     * @since 1.0.0
     */
    public function reset()
    {
        // Получаем модель
        $model = $this->getModel('Topic');
        if ($model->reset()) {
            $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic'), Text::_('COM_KUNENATOPIC2ARTICLE_PARAMS_RESET'), 'success');
        } else {
            $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic'), Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED'), 'error');
        }
    }

    /**
     * Метод для создания статей (Create Articles)
     * @since 1.0.0
     */
   public function create()
    {
        $this->checkToken();
        $this->app->setUserState('com_kunenatopic2article.save.success', false); // деактивируем create article
        
        Factory::getApplication()->enqueueMessage('DisplayController::create called', 'info'); // ОТЛАДКА
               
        // Прямой вызов ArticleController::create - вместо редиректа, чтобы избежать проблемы с CSRF-токеном
        // Получаем компонент и фабрику
    $container = Factory::getApplication()->bootComponent('com_kunenatopic2article');
    /** @var MVCFactoryInterface $mvcFactory */
    $mvcFactory = $container->getMVCFactory();

    // Создаём контроллер Article в административном контексте // передаём 5 аргументов
    $controller = $mvcFactory->createController(
        'Article',
        'Administrator',
        [], // конфигурация, если есть
        Factory::getApplication(),
        Factory::getApplication()->input
    );
    $controller->execute('create');
        
        // Редирект на view не нужен, так как ArticleController::create сам редиректирует на view=result
    }
}
