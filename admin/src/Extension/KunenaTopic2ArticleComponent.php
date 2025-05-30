<?php
namespace Joomla\Component\KunenaTopic2Article\Administrator\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\Component\KunenaTopic2Article\Administrator\Service\Provider\KunenaTopic2ArticleServiceProvider;
use Joomla\CMS\DependencyInjection\ServiceRegistryInterface;
use Psr\Container\ContainerInterface;
use Joomla\DI\Container;

class KunenaTopic2ArticleComponent extends MVCComponent implements BootableExtensionInterface
{
    use HTMLRegistryAwareTrait;

    /**
     * Boot the component.
     *
     * @param   ContainerInterface  $container  The DI container
     *
     * @return void
     */
    public function boot(ContainerInterface $container): void
    {
        // Здесь можно добавить дополнительную инициализацию, если необходимо
    }

    /**
     * Registers the component's service provider.
     *
     * @return ServiceRegistryInterface|null
     */
    public function getContainerExtension(): ?ServiceRegistryInterface
    {
        return new class implements ServiceRegistryInterface {
            public function register(Container $container): void
            {
                (new KunenaTopic2ArticleServiceProvider())->register($container);
            }
        };
    }
}
