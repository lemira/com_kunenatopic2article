<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class KunenaTopic2ArticleViewTopics extends JViewLegacy
{
    public function display($tpl = null)
    {
        $this->items = $this->get('Items');

        parent::display($tpl);
    }
}
