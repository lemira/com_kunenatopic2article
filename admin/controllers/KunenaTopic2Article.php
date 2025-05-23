<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * KunenaTopic2Article Default Controller
 */
class KunenaTopic2ArticleController extends BaseController
{
    public function __construct($config = array())
    {
        parent::__construct($config);
        Factory::getApplication()->enqueueMessage('Controller KunenaTopic2ArticleController initialized', 'message');
    }

    public function display($cachable = false, $urlparams = false)
    {
        Factory::getApplication()->enqueueMessage('Display called', 'message');
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
        return parent::display($cachable, $urlparams);
    }
}
