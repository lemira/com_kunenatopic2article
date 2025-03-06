<?php
defined('_JEXEC') or die;
use Joomla\CMS\Factory;
// Загружаем контроллер явно
JLoader::register('KunenaTopic2ArticleController', JPATH_COMPONENT . '/controllers/KunenaTopic2Article.php');
$controller = JControllerLegacy::getInstance('KunenaTopic2Article');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
