<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleViewTopics extends JViewLegacy
{
    protected $state;

    public function display($tpl = null)
    {
        $model = $this->getModel();
        $this->params = $model->getParams();
        $this->state = $model->getState();
        
        if (!$this->params) {
            JFactory::getApplication()->enqueueMessage('No parameters found in database', 'warning');
        } else {
            JFactory::getApplication()->enqueueMessage('Parameters loaded successfully: ' . print_r($this->params, true), 'message');
        }

        if (!$this->state) {
            JFactory::getApplication()->enqueueMessage('State not loaded', 'warning');
        } else {
            JFactory::getApplication()->enqueueMessage('State loaded: ' . print_r($this->state, true), 'message');
        }
        
        parent::display($tpl);
    }
}
