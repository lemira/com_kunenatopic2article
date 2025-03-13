<?php
defined('_JEXEC') or die;

class KunenaTopic2ArticleController extends JControllerLegacy
{
    public function display($cachable = false, $urlparams = false)
    {
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
        return parent::display($cachable, $urlparams);
    }

    public function edit()
    {
        JFactory::getApplication()->enqueueMessage('Redirecting to edit layout', 'message');
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics&layout=edit');
    }

    public function save()
    {
        $app = JFactory::getApplication();
        $model = $this->getModel('Topic', 'KunenaTopic2ArticleModel');
        $data = $app->input->get('jform', array(), 'array');
        
        if ($model->save($data)) {
            $app->enqueueMessage('Parameters saved successfully', 'success');
        }
        
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
    }

    public function reset()
    {
        $app = JFactory::getApplication();
        $model = $this->getModel('Topic', 'KunenaTopic2ArticleModel');
        
        if ($model->reset()) {
            $app->enqueueMessage('Parameters reset to default values', 'success');
        }
        
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
    }

    public function create()
    {
        JFactory::getApplication()->enqueueMessage('Article creation not implemented yet', 'warning');
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
    }
}
