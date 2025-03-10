<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class KunenaTopic2ArticleViewTopics extends JViewLegacy
{
    public function display($tpl = null)
    {
        $logFile = JPATH_BASE . '/logs/view_debug.log';
        $message = "Loading KunenaTopic2ArticleViewTopics at " . date('Y-m-d H:i:s') . "\n";
        file_put_contents($logFile, $message, FILE_APPEND);

        $model = $this->getModel();
        $this->form = $model->getForm();

        parent::display($tpl);
    }
}
