<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');

$logFile = JPATH_BASE . '/administrator/logs/controller_debug.log';
$message = "Loading KunenaTopic2ArticleControllerKunenaTopic2Article at " . date('Y-m-d H:i:s') . "\n";
file_put_contents($logFile, $message, FILE_APPEND);

class KunenaTopic2ArticleControllerKunenaTopic2Article extends JControllerForm
{
    public function save($key = null, $urlVar = null)
    {
        $app = JFactory::getApplication();
        $data = $app->input->get('jform', [], 'array');

        $model = $this->getModel('KunenaTopic2Article');
        if ($model->save($data)) {
            $app->enqueueMessage(JText::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_SAVED_SUCCESSFULLY'), 'success');
        } else {
            $app->enqueueMessage(JText::_('COM_KUNENATOPIC2ARTICLE_ARTICLE_SAVE_FAILED'), 'error');
        }

        $this->setRedirect('index.php?option=com_kunenatopic2article');
    }
}
