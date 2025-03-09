<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class KunenaTopic2ArticleController extends JControllerLegacy
{
    public function display($cachable = false, $urlparams = false)
    {
        $this->setRedirect('index.php?option=com_kunenatopic2article');
        return $this;
    }
}
