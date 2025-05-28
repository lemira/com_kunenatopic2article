// Файл: admin/kunenatopic2article.php  
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

// Access check.
if (!Factory::getUser()->authorise('core.manage', 'com_kunenatopic2article')) {
    throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

$app = Factory::getApplication();
$input = $app->input;

// Отладочная информация
$app->enqueueMessage('Main entry point loaded', 'notice');

// Создаем контроллер для Joomla 5
$controllerClass = '\\Joomla\\Component\\KunenaTopic2Article\\Administrator\\Controller\\DisplayController';

try {
    if (class_exists($controllerClass)) {
        $controller = new $controllerClass();
        $app->enqueueMessage('Controller instance created successfully', 'success');
    } else {
        // Fallback на старый способ
        $controller = BaseController::getInstance('KunenaTopic2Article');
        $app->enqueueMessage('Using fallback controller creation', 'warning');
    }
} catch (Exception $e) {
    $app->enqueueMessage('Error creating controller: ' . $e->getMessage(), 'error');
    throw $e;
}

// Perform the Request task
$task = $input->getCmd('task');
try {
    $controller->execute($task);
} catch (Exception $e) {
    $app->enqueueMessage($e->getMessage(), 'error');
}

// Redirect if set by the controller
$controller->redirect();
