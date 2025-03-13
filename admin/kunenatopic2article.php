<?php
defined('_JEXEC') or die;

if (!JFactory::getUser()->authorise('core.manage', 'com_kunenatopic2article')) {
    throw new JException(JText::_('JERROR_ALERTNOAUTHOR'), 404);
}

$controller = JControllerLegacy::getInstance('KunenaTopic2Article');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();

