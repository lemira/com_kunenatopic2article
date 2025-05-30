<?php
namespace Joomla\Component\KunenaTopic2Article\Administrator\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Psr\Container\ContainerInterface;

class KunenaTopic2ArticleComponent extends MVCComponent implements BootableExtensionInterface
{
    use HTMLRegistryAwareTrait;
    
    public function boot(ContainerInterface $container): void
    {
        // Инициализация при необходимости
    }
}
