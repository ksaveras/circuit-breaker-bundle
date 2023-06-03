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
use Ksaveras\CircuitBreaker\CircuitBreakerInterface;
use Ksaveras\CircuitBreaker\Policy\ConstantRetryPolicy;
use Ksaveras\CircuitBreaker\Policy\ExponentialRetryPolicy;
use Ksaveras\CircuitBreaker\Policy\LinearRetryPolicy;
use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\StorageFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class KsaverasCircuitBreakerExtension extends ConfigurableExtension
{
    /**
     * @var array<string, StorageFactoryInterface>
     */
    private array $storageFactories = [];

    public function addStorageFactory(StorageFactoryInterface $factory): void
    {
        $this->storageFactories[$factory->getType()] = $factory;
    }

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $this->createStorages($container, $mergedConfig['storages']);
        $this->createCircuitBreakers($container, $mergedConfig['circuit_breakers']);
    }

    private function createStorages(ContainerBuilder $container, array $storages): void
    {
        foreach ($storages as $name => $storageConfig) {
            if (!isset($this->storageFactories[$storageConfig['type']])) {
                throw new \RuntimeException(sprintf('Storage factory of type "%s" is not registered', $storageConfig['type']));
            }
            $this->storageFactories[$storageConfig['type']]->create($container, $name, $storageConfig);
        }
    }

    private function createCircuitBreakers(ContainerBuilder $container, array $circuitBreakers): void
    {
        foreach ($circuitBreakers as $name => $serviceConfig) {
            $policyDefinition = $this->createRetryPolicyDefinition($serviceConfig['retry_policy']);

            $factory = $container
                ->register(sprintf('ksaveras_circuit_breaker.factory.%s', $name), CircuitBreakerFactory::class)
                ->setArguments([
                    $serviceConfig['failure_threshold'],
                    new Reference(sprintf('ksaveras_circuit_breaker.storage.%s', $serviceConfig['storage'])),
                    $policyDefinition,
                ]);

            $id = sprintf('ksaveras_circuit_breaker.circuit.%s', $name);
            $container->register($id, CircuitBreaker::class)
                ->setFactory([$factory, 'create'])
                ->setArguments([$name])
                ->setPublic(true);

            $container->registerAliasForArgument($id, CircuitBreakerInterface::class, $name)->setPublic(false);
        }
    }

    private function createRetryPolicyDefinition(array $policyOptions): Definition
    {
        foreach ($policyOptions as $type => $options) {
            if ($options['enabled']) {
                return match ($type) {
                    'constant' => new Definition(ConstantRetryPolicy::class, [$options['reset_timeout']]),
                    'exponential' => new Definition(ExponentialRetryPolicy::class, [$options['reset_timeout'], $options['maximum_timeout'], $options['base']]),
                    'linear' => new Definition(LinearRetryPolicy::class, [$options['reset_timeout']]),
                    default => throw new \InvalidArgumentException(),
                };
            }
        }

        throw new \InvalidArgumentException();
    }
}
