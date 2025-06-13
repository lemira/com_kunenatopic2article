<?php
namespace Joomla\Component\KunenaTopic2Article\Administrator\Extension;

use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

class KunenaTopic2ArticleServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomla\\Component\\KunenaTopic2Article'));
        $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\KunenaTopic2Article'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new \Joomla\Component\KunenaTopic2Article\Administrator\Extension\KunenaTopic2ArticleComponent(
                    $container->get(\Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface::class)
                );
                
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                
                return $component;
            }
        );
    }
}
