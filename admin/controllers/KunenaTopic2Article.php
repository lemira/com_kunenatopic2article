<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleController extends JControllerLegacy
{
    public function __construct($config = array())
    {
        parent::__construct($config);
        JFactory::getApplication()->enqueueMessage('Controller KunenaTopic2ArticleController initialized', 'message');
    }

    public function display($cachable = false, $urlparams = false)
    {
        JFactory::getApplication()->enqueueMessage('Display called', 'message');
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
        return parent::display($cachable, $urlparams);
    }

}
