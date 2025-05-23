<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

// Проверка прав доступа
$user = Factory::getUser();
if (!$user->authorise('core.manage', 'com_kunenatopic2article')) {
    throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

// Отладка для проверки вызова
$app = Factory::getApplication();
$app->enqueueMessage('Controller: ' . $app->input->get('controller', 'none'), 'notice');
$app->enqueueMessage('Task: ' . $app->input->get('task', 'none'), 'notice');
$app->enqueueMessage('View: ' . $app->input->get('view', 'none'), 'notice');

// Создание контроллера (загружаем KunenaTopic2ArticleControllerArticle)
$controller = BaseController::getInstance('KunenaTopic2Article', ['controller' => 'Article']);
$controller->execute($app->input->get('task'));
$controller->redirect();
