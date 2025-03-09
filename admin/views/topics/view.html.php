<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class KunenaTopic2ArticleViewTopics extends JViewLegacy
{
    protected $form;

    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        parent::display($tpl);
    }
}
