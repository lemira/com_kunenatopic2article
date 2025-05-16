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

  public function reset()
    {
        $logFile = JPATH_BASE . '/logs/controller_debug.log';
        $message = "Reset method called in main controller at " . date('Y-m-d H:i:s') . "\n";
        file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);
        
        $app = JFactory::getApplication();
        $app->enqueueMessage('Reset task triggered in main controller', 'message');
        
        try {
            // Получаем модель
            $model = $this->getModel('Topic');
            
            if (!$model) {
                $message = "Model 'Topic' not found in reset method at " . date('Y-m-d H:i:s') . "\n";
                file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);
                throw new Exception('Unable to get Topic model');
            }
            
            $message = "Model class: " . get_class($model) . " at " . date('Y-m-d H:i:s') . "\n";
            file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);
            
            if (method_exists($model, 'reset')) {
                if ($model->reset()) {
                    $app->enqueueMessage('Parameters reset to default values', 'success');
                } else {
                    $app->enqueueMessage('Failed to reset parameters', 'error');
                }
            } else {
                $message = "Reset method not found in model at " . date('Y-m-d H:i:s') . "\n";
                file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);
                throw new Exception('Reset method not found in model');
            }
        } catch (Exception $e) {
            $message = "Error in reset: " . $e->getMessage() . " at " . date('Y-m-d H:i:s') . "\n";
            file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);
            $app->enqueueMessage('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
    }
    
    public function create()
    {
        JFactory::getApplication()->enqueueMessage('Article creation not implemented yet', 'warning');
        $this->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
    }
}
