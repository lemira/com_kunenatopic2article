<?php
defined('_JEXEC') or die;

namespace Joomla\Component\KunenaTopic2Article\Extension;

use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

class KunenaTopic2ArticleComponent implements ComponentInterface
{
    /**
     * @var ComponentDispatcherFactoryInterface
     */
    protected $dispatcherFactory;

    /**
     * @var MVCFactoryInterface|null
     */
    private $mvcFactory;

    /**
     * Конструктор компонента
     *
     * @param ComponentDispatcherFactoryInterface $dispatcherFactory
     */
    public function __construct(ComponentDispatcherFactoryInterface $dispatcherFactory)
    {
        $this->dispatcherFactory = $dispatcherFactory;
    }

    /**
     * Устанавливает MVC фабрику
     *
     * @param MVCFactoryInterface $mvcFactory
     */
    public function setMVCFactory(MVCFactoryInterface $mvcFactory): void
    {
        $this->mvcFactory = $mvcFactory;
    }

    /**
     * Возвращает имя компонента
     *
     * @return string
     */
    public function getName(): string
    {
        return 'KunenaTopic2Article';
    }

    /**
     * Регистрирует сервисы компонента
     *
     * @param Container $container
     */
    public function register(Container $container): void
    {
        $container->set(
            ComponentInterface::class,
            $this
        );
    }

    /**
     * Создает диспетчер компонента
     *
     * @return DispatcherInterface
     */
    public function getDispatcher($application)
    {
        return $this->dispatcherFactory->createDispatcher($application);
    }
}
