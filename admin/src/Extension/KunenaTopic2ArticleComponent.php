<?php
/**
 * @package     KunenaTopic2Article
 * @subpackage  com_kunenatopic2article
 * @author      Your Name
 * @copyright   Copyright (C) 2024 Your Name. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Joomla\Component\KunenaTopic2Article\Administrator\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Psr\Container\ContainerInterface;
use Joomla\CMS\Factory;

/**
 * Component class for KunenaTopic2Article
 * Поддерживает структуру:
 * - admin/ вместо administrator/
 * - ArticleController, Result/Topic views
 * - ParamsTable
 *
 * @since  1.0.0
 */
class KunenaTopic2ArticleComponent extends MVCComponent implements BootableExtensionInterface
{
    use HTMLRegistryAwareTrait;

    /**
     * Booting the extension. This is the function to set up the environment of the extension like
     * registering new class loaders, etc.
     * If required, some initial set up can be done from services of the container, eg.
     * registering HTML services.
     * @param   ContainerInterface  $container  The container
     * @return  void
     * @since   1.0.0
     */
public function boot(ContainerInterface $container)
{
    $task = Factory::getApplication()->input->get('task', '', 'cmd');

    // Вместо создания контроллера попробуем вызвать его через диспетчер
    $dispatcher = ComponentHelper::bootComponent('com_kunenatopic2article');

    if (!empty($task)) {
        $dispatcher->dispatch();
    }
}
    
    }
