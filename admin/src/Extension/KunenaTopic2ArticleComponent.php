namespace Joomla\Component\KunenaTopic2Article\Administrator\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\Component\KunenaTopic2Article\Administrator\Service\Provider\KunenaTopic2ArticleServiceProvider;
use Joomla\CMS\DependencyInjection\ServiceRegistryInterface;
use Joomla\DI\Container;
use Psr\Container\ContainerInterface;

class KunenaTopic2ArticleComponent extends MVCComponent implements BootableExtensionInterface
{
    use HTMLRegistryAwareTrait;

    public function boot(ContainerInterface $container): void
    {
        // При необходимости – инициализация
    }

    public function getContainerExtension(): ?ServiceRegistryInterface
    {
        return new class (new KunenaTopic2ArticleServiceProvider()) implements ServiceRegistryInterface {
            private KunenaTopic2ArticleServiceProvider $provider;

            public function __construct(KunenaTopic2ArticleServiceProvider $provider)
            {
                $this->provider = $provider;
            }

            public function register(Container $container): void
            {
                $this->provider->register($container);
            }
        };
    }
}
