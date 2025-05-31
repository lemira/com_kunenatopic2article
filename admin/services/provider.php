<?php
/**
 * @package     KunenaTopic2Article
 * @subpackage  com_kunenatopic2article
 */

defined('_JEXEC') or die;

var_dump(class_exists('Joomla\Component\KunenaTopic2Article\Administrator\Extension\KunenaTopic2ArticleComponent'));
exit;

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\KunenaTopic2Article\Administrator\Extension\KunenaTopic2ArticleComponent;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * Service provider for KunenaTopic2Article component
 */
return new class implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        // Регистрируем MVC фабрику
        $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\KunenaTopic2Article\\Administrator'));
        
        // Регистрируем диспетчер
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomla\\Component\\KunenaTopic2Article\\Administrator'));

        // Регистрируем главный компонент
        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new KunenaTopic2ArticleComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setRegistry($container->get(Registry::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                return $component;
            }
        );
    }
};
