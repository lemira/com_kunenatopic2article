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
        
        parent::display($tpl);
    }
}
