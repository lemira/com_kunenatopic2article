<?php
defined('_JEXEC') or die;

// Принудительная регистрация пространства имен
JLoader::registerNamespace(
    'Joomla\\Component\\KunenaTopic2Article\\Administrator',
    JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/src',
    false,
    false,
    'psr4'
);

JLoader::registerNamespace(
    'Joomla\\Component\\KunenaTopic2Article\\Administrator\\Controller',
    JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/src/Controller',
    false,
    false,
    'psr4'
);

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
