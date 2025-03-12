<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleViewTopics extends JViewLegacy
{
    public function display($tpl = null)
    {
        $model = $this->getModel();
        $this->params = $model->getParams();
        
        if (!$this->params) {
            JFactory::getApplication()->enqueueMessage('No parameters found in database', 'warning');
        }
        
        parent::display($tpl);
    }
}
