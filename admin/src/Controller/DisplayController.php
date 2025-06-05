<?php
/**
 * @package     Kunena Topic to Article
 * @subpackage  com_kunenatopic2article
 * @version     1.0.0
 * @copyright   Copyright (C) 2025 lr. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Lr\Component\Kunenatopic2article\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;

/**
 * Kunena Topic to Article Display Controller
 *
 * @since  1.0.0
 */
class DisplayController extends BaseController
{
    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  1.0.0
     */
    protected $default_view = 'topic';

    /**
     * Constructor.
     *
     * @param   array                $config   An optional associative array of configuration settings.
     * @param   MVCFactoryInterface  $factory  The factory.
     * @param   CMSApplication       $app      The Application for the dispatcher
     * @param   \JInput              $input    Input
     *
     * @since   1.0.0
     */
    public function __construct($config = array(), MVCFactoryInterface $factory = null, $app = null, $input = null)
    {
        parent::__construct($config, $factory, $app, $input);
    }

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types.
     *
     * @return  static  This object to support chaining.
     *
     * @since   1.0.0
     */
    public function display($cachable = false, $urlparams = array())
    {
        $view = $this->input->get('view', 'topic');
        $layout = $this->input->get('layout', 'default');
        $id = $this->input->getInt('id');

        return parent::display($cachable, $urlparams);
    }

    /**
     * Method to handle the remember button action.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function remember()
    {
        // Check for request forgeries
        $this->checkToken();

        $app = Factory::getApplication();
        $data = $this->input->post->get('jform', array(), 'array');

        $model = $this->getModel('Topic');
        
        if ($model->remember($data)) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SUCCESS_PARAMETERS_SAVED'), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_SAVE_FAILED'), 'error');
        }

        // Redirect back to the form
        $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic', false));
    }

    /**
     * Method to handle the reset button action.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function reset()
    {
        // Check for request forgeries
        $this->checkToken();

        $app = Factory::getApplication();
        $model = $this->getModel('Topic');
        
        if ($model->reset()) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_RESET_SUCCESS'), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_RESET_FAILED'), 'error');
        }

        // Redirect back to the form
        $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic', false));
    }

    /**
     * Method to handle the create articles button action.
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function createArticles()
    {
        // Check for request forgeries
        $this->checkToken();

        $app = Factory::getApplication();
        $model = $this->getModel('Topic');
        
        if ($model->createArticles()) {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FEATURE_COMING_SOON'), 'message');
        } else {
            $app->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_CREATION_ERROR'), 'error');
        }

        // Redirect back to the form
        $this->setRedirect(Route::_('index.php?option=com_kunenatopic2article&view=topic', false));
    }
}
