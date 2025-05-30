<?php
/**
 * @package     KunenaTopic2Article
 * @subpackage  com_kunenatopic2article
 * @author      Your Name
 * @copyright   Copyright (C) 2024 Your Name. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @since  1.0.0
 */
class Com_KunenaTopic2ArticleInstallerScript
{
    /**
     * Extension name
     *
     * @var    string
     * @since  1.0.0
     */
    protected $extension = 'com_kunenatopic2article';

    /**
     * Minimum Joomla version
     *
     * @var    string
     * @since  1.0.0
     */
    protected $minimumJoomla = '5.0';

    /**
     * Minimum PHP version
     *
     * @var    string
     * @since  1.0.0
     */
    protected $minimumPhp = '8.1';

    /**
     * Called before any type of action
     *
     * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
     * @param   InstallerAdapter  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
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

        // Временная регистрация namespace для Joomla 5
        if ($route === 'install' || $route === 'update') {
            $extensionPath = $adapter->getParent()->getPath('extension_administrator');
            if (is_dir($extensionPath . '/src')) {
                // В Joomla 5 используем современный автозагрузчик
                $loader = require JPATH_LIBRARIES . '/vendor/autoload.php';
                $loader->addPsr4(
                    'Joomla\\Component\\KunenaTopic2Article\\Administrator\\',
                    $extensionPath . '/src/'
                );
            }
        }

        return true;
    }

    /**
     * Called after any type of action
     *
     * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
     * @param   InstallerAdapter  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
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

    /**
     * Method to install the extension
     *
     * @param   InstallerAdapter  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function install(InstallerAdapter $adapter)
    {
        return true;
    }

    /**
     * Method to uninstall the extension
     *
     * @param   InstallerAdapter  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function uninstall(InstallerAdapter $adapter)
    {
        return true;
    }

    /**
     * Method to update the extension
     *
     * @param   InstallerAdapter  $adapter  The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function update(InstallerAdapter $adapter)
    {
        return true;
    }
}
