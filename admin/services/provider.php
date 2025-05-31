<?php
defined('_JEXEC') or die;

// Принудительная регистрация namespace для Joomla 5
JLoader::registerNamespace(
    'Joomla\\Component\\KunenaTopic2Article\\Administrator',
    JPATH_ADMINISTRATOR . '/components/com_kunenatopic2article/src',
    false,
    false,
    'psr4'
);

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
