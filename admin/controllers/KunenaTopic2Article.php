<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleController extends JControllerLegacy
{
    public function display($cachable = false, $urlparams = false)
    {
        JFactory::getApplication()->enqueueMessage('Display called', 'message');
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
        return parent::display($cachable, $urlparams);
    }

    public function save()
    {
        $app = JFactory::getApplication();
        $app->enqueueMessage('Save task triggered', 'message');
        
        $data = $app->input->get('jform', array(), 'array');
        $app->enqueueMessage('Form data received: ' . print_r($data, true), 'message');
        
        $model = $this->getModel('Topic', 'KunenaTopic2ArticleModel');
        if ($model->save($data)) {
            $app->enqueueMessage('Parameters saved successfully', 'success');
        } else {
            $app->enqueueMessage('Failed to save parameters', 'error');
        }
        
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
    }

    public function reset()
    {
        $app = JFactory::getApplication();
        $app->enqueueMessage('Reset task triggered', 'message');
        
        $model = $this->getModel('Topic', 'KunenaTopic2ArticleModel');
        if ($model->reset()) {
            $app->enqueueMessage('Parameters reset to default values', 'success');
        } else {
            $app->enqueueMessage('Failed to reset parameters', 'error');
        }
        
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
    }

    public function create()
    {
        JFactory::getApplication()->enqueueMessage('Article creation not implemented yet', 'warning');
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
    }
}
