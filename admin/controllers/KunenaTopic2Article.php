<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleController extends JControllerLegacy
{
    public function __construct($config = array())
    {
        parent::__construct($config);
        JFactory::getApplication()->enqueueMessage('Controller KunenaTopic2ArticleController initialized', 'message');
    }

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
        
        $task = $app->input->get('task', '', 'string');
        $app->enqueueMessage('Task received: ' . $task, 'message');
        
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

     public function create()
    {
        JFactory::getApplication()->enqueueMessage('Article creation not implemented yet', 'warning');
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
    }
}
