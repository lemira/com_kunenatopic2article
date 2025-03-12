<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

defined('_JEXEC') or die('Restricted access');

$controller = JControllerLegacy::getInstance('KunenaTopic2Article');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
