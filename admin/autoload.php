defined('_JEXEC') or die;

use Joomla\CMS\Factory;

// Отладка
Factory::getApplication()->enqueueMessage('Autoload executed', 'notice');

JLoader::registerNamespace(
    'Joomla\\Component\\KunenaTopic2Article',
    JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/admin/src',
    false,
    false,
    'psr4'
);
