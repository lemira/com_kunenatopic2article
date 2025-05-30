<?php

namespace Joomla\Component\KunenaTopic2Article\Administrator\Service\Provider;

\defined('_JEXEC') or die;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Component\KunenaTopic2Article\Administrator\Extension\KunenaTopic2ArticleComponent;
use Joomla\DI\ContainerAwareInterface;

class KunenaTopic2ArticleServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->set(
            KunenaTopic2ArticleComponent::class,
            function (Container $container) {
                $component = new KunenaTopic2ArticleComponent();
                if ($component instanceof ContainerAwareInterface) {
                    $component->setContainer($container);
                }

                return $component;
            }
        );
    }
}
