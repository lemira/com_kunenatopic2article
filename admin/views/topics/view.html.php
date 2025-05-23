<?php
defined('_JEXEC') or die;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class KunenaTopic2ArticleViewTopics extends HtmlView
{
    protected $state;
    protected $form;

    public function display($tpl = null)
    {
        $model = $this->getModel();
        $this->params = $model->getParams();
        $this->state = $model->getState();
        $this->form = $model->getForm();
        
        if (!$this->form) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_KUNENATOPIC2ARTICLE_FORM_FAILED_TO_LOAD'), 'error');
        }
        
        parent::display($tpl);
    }
}
