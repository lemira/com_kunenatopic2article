<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

// Проверка прав доступа (обновлено для Joomla 5)
$user = Factory::getUser();
if (!$user->authorise('core.manage', 'com_kunenatopic2article')) {
    throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

// Создание контроллера (обновлено для Joomla 5)
$controller = BaseController::getInstance('KunenaTopic2Article');
$controller->execute(Factory::getApplication()->input->get('task'));
$controller->redirect();
