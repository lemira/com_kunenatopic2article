<?php
/**
 * @package     com_kunenatopic2article
 * @subpackage  com_kunenatopic2article
 * @author      lemira
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

// Проверка прав доступа
$app = Factory::getApplication();
$user = $app->getIdentity();

if (!$user->authorise('core.manage', 'com_kunenatopic2article')) {
    throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

// Отладка
$app = Factory::getApplication();
$input = $app->input;
$app->enqueueMessage('Request URL: ' . $app->get('uri.request'), 'notice');
$app->enqueueMessage('Controller: ' . $input->get('controller', 'none'), 'notice');
$app->enqueueMessage('Task: ' . $input->get('task', 'none'), 'notice');
$app->enqueueMessage('View: ' . $input->get('view', 'none'), 'notice');
$app->enqueueMessage('Format: ' . $input->get('format', 'none'), 'notice');

// Создание контроллера
$controller = BaseController::getInstance('KunenaTopic2Article', ['controller' => 'Article']);

// Проверяем задачу и представление
$task = $input->get('task', '');
$view = $input->get('view', '');

// Если указан view=topics без задачи, вызываем display()
if (empty($task) && $view === 'topics') {
    $app->enqueueMessage('Loading topics view via display()', 'notice');
    $controller->display();
    return;
}

// Если нет задачи и представления, перенаправляем на view=topics
if (empty($task) && empty($view)) {
    $app->enqueueMessage('No task or view provided, redirecting to default view', 'notice');
    $controller->setRedirect('index.php?option=com_kunenatopic2article&view=topics');
    $controller->redirect();
    return;
}

// Выполняем задачу
$controller->execute($task);
$controller->redirect();
