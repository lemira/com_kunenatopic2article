<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Utilities\ArrayHelper;

/**
 * Article Controller
 *
 * @since  1.0.0
 */
class KunenaTopic2ArticleControllerArticle extends BaseController
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
        
        // Регистрация задачи create
        $this->registerTask('create', 'create');
    }

    /**
     * Create an article from selected topic
     *
     * @return void
     */
    public function create()
    {
        // Проверка токена безопасности
        Session::checkToken() or die(Text::_('JINVALID_TOKEN'));
        
        // Получение выбранных ID тем
        $app = Factory::getApplication();
        $input = $app->input;
        $cid = $input->get('cid', array(), 'array');
        $cid = ArrayHelper::toInteger($cid);
        
        if (empty($cid)) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_NO_TOPIC_SELECTED'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topics', false));
            return;
        }
        
        // Заглушка для демонстрации работы кнопки
        $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FEATURE_COMING_SOON'), 'notice');
        $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topics', false));
    }
}
