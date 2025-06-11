<?php
namespace Joomla\Component\KunenaTopic2Article\Administrator\Extension;

use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Component\KunenaTopic2Article\Administrator\Extension\KunenaTopic2ArticleComponent;


class KunenaTopic2ArticleServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        // Подключаем фабрики MVC и Dispatcher
  //      $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomla\\Component\\KunenaTopic2Article'));
  //      $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\KunenaTopic2Article'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomla\\Component\\KunenaTopic2Article\\Administrator'));
        $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\KunenaTopic2Article\\Administrator'));

        // Регистрируем сам компонент
        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new KunenaTopic2ArticleComponent(
                    $container->get(ComponentDispatcherFactoryInterface::class)
                );
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                return $component;
            }
        );
    }
}
