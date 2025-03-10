<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

$logFile = JPATH_BASE . '/logs/controller_debug.log';
$message = "Loading KunenaTopic2ArticleController at " . date('Y-m-d H:i:s') . "\n";
file_put_contents($logFile, $message, FILE_APPEND);

class KunenaTopic2ArticleController extends JControllerLegacy
{
    public function display($cachable = false, $urlparams = false)
    {
        $this->setRedirect('index.php?option=com_kunenatopic2article');
        return $this;
    }
}
