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
   /**
     * Registers the service provider with a DI container.
     * @param   Container  $container  The DI container.
     */
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

        // Расширяем MVCFactory для добавления путей
       $container->extend(
            MVCFactoryInterface::class,
            function (MVCFactoryInterface $factory, Container $container) {
                // Проверяем, что $factory является экземпляром Joomla\CMS\MVC\Factory\MVCFactory
                if ($factory instanceof \Joomla\CMS\MVC\Factory\MVCFactory) {
                    $factory->addViewPath(JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/src/View');
                    $factory->addTemplatePath(JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/tmpl');
                }

                return $factory;
            }
        );
    }
}
