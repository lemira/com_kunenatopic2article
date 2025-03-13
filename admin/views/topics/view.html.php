<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleViewTopics extends JViewLegacy
{
    protected $state;
    protected $form;

    public function display($tpl = null)
    {
        $model = $this->getModel();
        $this->params = $model->getParams();
        $this->state = $model->getState();
        
        // Если layout=edit, подключаем форму
        $layout = $this->getLayout();
        if ($layout == 'edit') {
            $this->form = $model->getForm();
            if (!$this->form) {
                JFactory::getApplication()->enqueueMessage('Form failed to load', 'error');
            }
        }
        
        parent::display($tpl);
    }
}
