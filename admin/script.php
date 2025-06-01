<?php
/**
 * @package     KunenaTopic2Article
 * @subpackage  com_kunenatopic2article
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\DI\Container;
use Joomla\Component\KunenaTopic2Article\Administrator\Extension\KunenaTopic2ArticleComponent;

/**
 * Installation class to perform additional changes during install/uninstall/update
 */
class Com_KunenaTopic2ArticleInstallerScript
{
    protected $extension = 'com_kunenatopic2article';
    protected $minimumJoomla = '5.0';
    protected $minimumPhp = '8.1';
// отладка - поиск место ошибки syntax error, unexpected token "public"
    error_reporting(E_ALL);
ini_set('display_errors', 1);

    
    public function preflight($route, InstallerAdapter $adapter)
    {
        // Проверяем минимальную версию Joomla
        if (version_compare(JVERSION, $this->minimumJoomla, 'lt')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('JLIB_INSTALLER_MINIMUM_JOOMLA', $this->minimumJoomla),
                'error'
            );
            return false;
        }

        // Проверяем минимальную версию PHP
        if (version_compare(PHP_VERSION, $this->minimumPhp, 'lt')) {
            Factory::getApplication()->enqueueMessage(
                Text::sprintf('JLIB_INSTALLER_MINIMUM_PHP', $this->minimumPhp),
                'error'
            );
            return false;
        }

        // Регистрация пространства имён
        $container = Factory::getContainer();
        JLoader::registerNamespace(
            'Joomla\\Component\\KunenaTopic2Article\\Administrator',
            JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/src',
            false,
            false,
            'psr4'
        );

        if (!$container->has(ComponentInterface::class)) {
            Factory::getApplication()->enqueueMessage(
                'Ошибка: Компонент KunenaTopic2Article не зарегистрирован в контейнере.',
                'error'
            );
            return false;
        }

        $component = $container->get(ComponentInterface::class);

        if (!$component instanceof KunenaTopic2ArticleComponent) {
            Factory::getApplication()->enqueueMessage(
                'Ошибка: KunenaTopic2ArticleComponent загружен неверно.',
                'error'
            );
            return false;
        }

        Factory::getApplication()->enqueueMessage('KunenaTopic2ArticleComponent загружен успешно!', 'notice');
        return true;
    }

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

    public function install(InstallerAdapter $adapter)
    {
        return true;
    }

    public function uninstall(InstallerAdapter $adapter)
    {
        return true;
    }

    public function update(InstallerAdapter $adapter)
    {
        return true;
    }
}
