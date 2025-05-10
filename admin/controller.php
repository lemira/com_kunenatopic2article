<?php
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * KunenaTopic2Article Component Controller
 *
 * @since  1.0
 */
class KunenaTopic2ArticleController extends BaseController
{
    protected $default_view = 'topics';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types
     *
     * @return  KunenaTopic2ArticleController  This object to support chaining.
     *
     * @since   1.0
     */
    public function display($cachable = false, $urlparams = false)
    {
        $view = $this->input->get('view', 'topics');

        // Если view не указан или не равен 'topics', перенаправляем
        if (!$view || $view != 'topics') {
            $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topics', false));
            $this->redirect();
        }

        return parent::display($cachable, $urlparams);
    }
}
