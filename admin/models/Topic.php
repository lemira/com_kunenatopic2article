<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modelform');

class KunenaTopic2ArticleModelTopic extends JModelForm
{
    public function getForm($data = array(), $loadData = true)
    {
        $logFile = JPATH_BASE . '/administrator/logs/model_debug.log';
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }
        $message = "Loading form in KunenaTopic2ArticleModelTopic at " . date('Y-m-d H:i:s') . "\n";
        file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);

        JForm::addFormPath(JPATH_COMPONENT . '/models/forms');
        $form = $this->loadForm('com_kunenatopic2article.topic', 'topic', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) {
            $message = "Form loading failed in KunenaTopic2ArticleModelTopic at " . date('Y-m-d H:i:s') . "\n";
            file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);
        } else {
            $message = "Form loaded successfully in KunenaTopic2ArticleModelTopic at " . date('Y-m-d H:i:s') . "\n";
            file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES);
        }

        return $form;
    }
}
