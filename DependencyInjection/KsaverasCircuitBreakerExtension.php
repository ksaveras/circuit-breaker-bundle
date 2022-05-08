<?php declare(strict_types=1);
/*
 * This file is part of ksaveras/circuit-breaker-bundle.
 *
 * (c) Ksaveras Sakys <xawiers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Ksaveras\CircuitBreakerBundle\DependencyInjection;

use Ksaveras\CircuitBreaker\CircuitBreaker;
use Ksaveras\CircuitBreaker\CircuitBreakerFactory;
use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\StorageFactoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class KsaverasCircuitBreakerExtension extends ConfigurableExtension
{
    /**
     * @var array<string, StorageFactoryInterface>
     */
    private $storageFactories = [];

    public function addStorageFactory(StorageFactoryInterface $factory): void
    {
        $this->storageFactories[$factory->getType()] = $factory;
    }

    protected function loadInternal(array $config, ContainerBuilder $container)
    {
        $configPath = implode(\DIRECTORY_SEPARATOR, [__DIR__, '..', 'Resources', 'config']);
        $loader = new Loader\XmlFileLoader($container, new FileLocator($configPath));
        $loader->load('storage.xml');

        $storage = [];
        foreach ($config['storage'] as $name => $storageConfig) {
            if (!isset($this->storageFactories[$storageConfig['type']])) {
                throw new \RuntimeException(sprintf('Storage factory of type "%s" is not registered', $storageConfig['type']));
            }
            $storage[$name] = $this->storageFactories[$storageConfig['type']]->create(
                $container, $name, $storageConfig
            );
        }

        foreach ($config['circuit_breakers'] as $name => $serviceConfig) {
            $storageName = $serviceConfig['storage'];
            unset($serviceConfig['storage']);

            $factory = $container
                ->register(sprintf('ksaveras_circuit_breaker.factory.%s', $name), CircuitBreakerFactory::class)
                ->setArguments([$serviceConfig, new Reference($storage[$storageName])]);

            $id = sprintf('ksaveras_circuit_breaker.circuit.%s', $name);
            $container->register($id, CircuitBreaker::class)
                ->setFactory([$factory, 'create'])
                ->setArguments([$name])
                ->setPublic(true);

            $container->registerAliasForArgument($id, CircuitBreaker::class, $name);
        }
    }
}
