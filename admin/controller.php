<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

$logFile = JPATH_BASE . '/logs/controller_debug.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}
$message = "Loading KunenaTopic2ArticleController at " . date('Y-m-d H:i:s') . "\n";
file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);

class KunenaTopic2ArticleController extends JControllerLegacy
{
    public function __construct($config = array())
    {
        parent::__construct($config);

        $logFile = JPATH_BASE . '/logs/controller_debug.log';
        $message = "Constructing KunenaTopic2ArticleController at " . date('Y-m-d H:i:s') . "\n";
        file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);
    }

    // Регистрируем задачи
    $this->registerTask('topic.reset', 'reset');
    
    public function display($cachable = false, $urlparams = false)
    {
        $logFile = JPATH_BASE . '/logs/controller_debug.log';
        $message = "Displaying view in KunenaTopic2ArticleController at " . date('Y-m-d H:i:s') . "\n";
        file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);

        try {
            $view = $this->getView('Topics', 'html');
            $model = $this->getModel('Topic'); // Изменил с 'Topics' на 'Topic'
            if ($model) {
                $view->setModel($model, true);
                $view->display();
            } else {
                $message = "Model 'Topic' not found at " . date('Y-m-d H:i:s') . "\n";
                file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);
            }
        } catch (Exception $e) {
            $message = "Error in display: " . $e->getMessage() . " at " . date('Y-m-d H:i:s') . "\n";
            file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);
        }

        return $this;
    }
}
