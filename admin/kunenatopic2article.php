<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

$logFile = JPATH_BASE . '/logs/controller_debug.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}
$message = "Loading component at " . date('Y-m-d H:i:s') . "\n";
file_put_contents($logFile, $message, FILE_APPEND);

$controller = JControllerLegacy::getInstance('KunenaTopic2Article');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
