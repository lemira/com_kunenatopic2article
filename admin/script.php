use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\DI\Container;
use Joomla\Component\KunenaTopic2Article\Administrator\Extension\KunenaTopic2ArticleComponent;

class Com_KunenaTopic2ArticleInstallerScript
{

  public function preflight($route, InstallerAdapter $adapter)
{
    $container = Factory::getContainer(); // Получаем DI-контейнер Joomla

    $app = Factory::getApplication();
    $app->enqueueMessage("Пробую загрузить компонент...", 'notice');

    if (!$container->has(ComponentInterface::class)) {
        $app->enqueueMessage(
            'Ошибка: Компонент KunenaTopic2Article не зарегистрирован в контейнере.',
            'error'
        );
        return false;
    }

    $component = $container->get(ComponentInterface::class);

    var_dump($component); // Отладочный вывод

    if (!$component instanceof KunenaTopic2ArticleComponent) {
        $app->enqueueMessage(
            'Ошибка: KunenaTopic2ArticleComponent загружен неверно.',
            'error'
        );
        return false;
    }

    $app->enqueueMessage('KunenaTopic2ArticleComponent загружен успешно!', 'notice');

    return true;
}
 
}





/** версия от кл врем? заменена на верс от коп 
<?php
//  * @package     KunenaTopic2Article
// * @subpackage  com_kunenatopic2article
//  * @author      Your Name
//  * @copyright   Copyright (C) 2024 Your Name. All rights reserved.
//  * @license     GNU General Public License version 2 or later


defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

// Installation class to perform additional changes during install/uninstall/update
 class Com_KunenaTopic2ArticleInstallerScript
{
    // Extension name
     protected $extension = 'com_kunenatopic2article';

    // Minimum Joomla version
    protected $minimumJoomla = '5.0';

    // Minimum PHP version
     protected $minimumPhp = '8.1';

    public function preflight($route, InstallerAdapter $adapter)
    {
        // Check minimum Joomla version
        if (version_compare(JVERSION, $this->minimumJoomla, 'lt')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomla),
                'error'
            );
            return false;
        }

        // Check minimum PHP version
        if (version_compare(PHP_VERSION, $this->minimumPhp, 'lt')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPhp),
                'error'
            );
            return false;
        }

        // Отладка и регистрация namespace для Joomla 5
        if ($route === 'install' || $route === 'update') {
            $extensionPath = $adapter->getParent()->getPath('extension_administrator');
            
            // Отладочная информация
            $app = Factory::getApplication();
            $app->enqueueMessage("Extension path: " . $extensionPath, 'notice');
            $app->enqueueMessage("Src dir exists: " . (is_dir($extensionPath . '/src') ? 'YES' : 'NO'), 'notice');
            $app->enqueueMessage("Component file exists: " . (file_exists($extensionPath . '/src/Extension/KunenaTopic2ArticleComponent.php') ? 'YES' : 'NO'), 'notice');
            
            if (is_dir($extensionPath . '/src')) {
                // Принудительная регистрация namespace
                \JLoader::registerNamespace(
                    'Joomla\\Component\\KunenaTopic2Article\\Administrator',
                    $extensionPath . '/src',
                    false,
                    false,
                    'psr4'
                );
                
                $app->enqueueMessage("Namespace registered manually", 'notice');
            }
        }

        return true;
    }

    // * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
     * @param   InstallerAdapter  $adapter  The object responsible for running this script
     public function postflight($route, InstallerAdapter $adapter)
    {
        if ($route === 'install') {
            Factory::getApplication()->enqueueMessage(
                Text::_('COM_KUNENATOPIC2ARTICLE_INSTALL_SUCCESS'),
                'message'
            );
        }

        return true;
    }

    // Method to install the extension
     public function install(InstallerAdapter $adapter)
    {
        return true;
    }

    // Method to uninstall the extension
     * @param   InstallerAdapter  $adapter  The object responsible for running this script
     * @return  boolean  True on success
    public function uninstall(InstallerAdapter $adapter)
    {
        return true;
    }

    / Method to update the extension
     * @param   InstallerAdapter  $adapter  The object responsible for running this script
     * @return  boolean  True on success
     public function update(InstallerAdapter $adapter)
    {
        return true;
    }
}
 */
