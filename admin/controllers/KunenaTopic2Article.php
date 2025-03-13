<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleController extends JControllerLegacy
{
    public function display($cachable = false, $urlparams = false)
    {
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
        return parent::display($cachable, $urlparams);
    }

    public function save()
    {
        $app = JFactory::getApplication();
        $model = $this->getModel('Topic', 'KunenaTopic2ArticleModel');
        $data = $app->input->get('jform', array(), 'array');
        
        JFactory::getApplication()->enqueueMessage('Save task called. Data: ' . print_r($data, true), 'message');
        
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
        $model = $this->getModel('Topic', 'KunenaTopic2ArticleModel');
        
        JFactory::getApplication()->enqueueMessage('Reset task called', 'message');
        
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
