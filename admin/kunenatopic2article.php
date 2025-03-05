<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;

if (!Factory::getUser()->authorise('core.manage', 'com_kunenatopic2article'))
{
    throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'), 403);
}

$controller = BaseController::getInstance('KunenaTopic2Article');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
