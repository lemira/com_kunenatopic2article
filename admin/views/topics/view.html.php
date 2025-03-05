<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;

class KunenaTopic2ArticleViewTopics extends HtmlView
{
    public function display($tpl = null)
    {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');

        parent::display($tpl);
    }
}
