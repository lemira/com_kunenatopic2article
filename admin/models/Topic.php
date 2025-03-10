<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.modelform');

class KunenaTopic2ArticleModelTopics extends JModelForm
{
    public function getForm($data = array(), $loadData = true)
    {
        $logFile = JPATH_BASE . '/logs/model_debug.log';
        $message = "Loading form in KunenaTopic2ArticleModelTopics at " . date('Y-m-d H:i:s') . "\n";
        file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES | FILE_APPEND_ERRORS);

        JForm::addFormPath(JPATH_COMPONENT . '/models/forms');
        $form = $this->loadForm('com_kunenatopic2article.topics', 'topics', array('control' => 'jform', 'load_data' => $loadData));

        if (empty($form)) {
            $message = "Form loading failed in KunenaTopic2ArticleModelTopics at " . date('Y-m-d H:i:s') . "\n";
            file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES | FILE_APPEND_ERRORS);
        } else {
            $message = "Form loaded successfully in KunenaTopic2ArticleModelTopics at " . date('Y-m-d H:i:s') . "\n";
            file_put_contents($logFile, $message, FILE_APPEND | FILE_IGNORE_NEW_LINES | FILE_APPEND_ERRORS);
        }

        return $form;
    }
}
