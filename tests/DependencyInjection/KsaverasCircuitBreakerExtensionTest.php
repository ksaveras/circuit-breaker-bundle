<?php declare(strict_types=1);
/*
 * This file is part of ksaveras/circuit-breaker-bundle.
 *
 * (c) Ksaveras Sakys <xawiers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Ksaveras\CircuitBreakerBundle\Tests\DependencyInjection;

use Ksaveras\CircuitBreaker\CircuitBreaker;
use Ksaveras\CircuitBreaker\CircuitBreakerFactory;
use Ksaveras\CircuitBreaker\CircuitBreakerInterface;
use Ksaveras\CircuitBreaker\Policy\ExponentialRetryPolicy;
use Ksaveras\CircuitBreaker\Storage\CacheStorage;
use Ksaveras\CircuitBreaker\Storage\InMemoryStorage;
use Ksaveras\CircuitBreakerBundle\DependencyInjection\KsaverasCircuitBreakerExtension;
use Ksaveras\CircuitBreakerBundle\KsaverasCircuitBreakerBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

final class KsaverasCircuitBreakerExtensionTest extends TestCase
{
    public function testUnknownStorage(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('You have requested a non-existent service "ksaveras_circuit_breaker.storage.foo_bar".');

        $container = $this->createContainer();

        $container->loadFromExtension('ksaveras_circuit_breaker', [
            'circuit_breakers' => [
                'web_api' => [
                    'storage' => 'foo_bar',
                    'failure_threshold' => 3,
                    'retry_policy' => [
                        'exponential' => [
                            'reset_timeout' => 200,
                        ],
                    ],
                ],
            ],
        ]);

        $this->compileContainer($container);
        $container->get('ksaveras_circuit_breaker.circuit.web_api');
    }

    public function testFullConfig(): void
    {
        $container = $this->createContainer();

        $container->loadFromExtension('ksaveras_circuit_breaker', [
            'circuit_breakers' => [
                'web-api' => [
                    'storage' => 'memory',
                    'failure_threshold' => 2,
                    'retry_policy' => [
                        'exponential' => [
                            'reset_timeout' => 2,
                            'maximum_timeout' => 600,
                        ],
                    ],
                ],
                'intraApi' => [
                    'storage' => 'cache',
                    'failure_threshold' => 2,
                    'retry_policy' => [
                        'linear' => [
                            'reset_timeout' => 2,
                            'maximum_timeout' => 600,
                        ],
                    ],
                ],
            ],
            'storages' => [
                'memory' => [
                    'type' => 'in_memory',
                ],
                'cache' => 'cache.adapter.array',
                'my_service' => [
                    'type' => 'service',
                    'id' => 'custom_storage_service',
                ],
            ],
        ]);

        $this->compileContainer($container);

        self::assertTrue($container->has('ksaveras_circuit_breaker.circuit.web_api'));
        $definition = $container->getDefinition('ksaveras_circuit_breaker.circuit.web_api');

        $this->runAssertCircuitBreakerDefinition($definition, 'web_api');
        $this->runAssertArgumentAlias($container, '$webApi', 'ksaveras_circuit_breaker.circuit.web_api');

        self::assertTrue($container->has('ksaveras_circuit_breaker.circuit.intraApi'));
        $definition = $container->getDefinition('ksaveras_circuit_breaker.circuit.intraApi');

        $this->runAssertCircuitBreakerDefinition($definition, 'intraApi');
        $this->runAssertArgumentAlias($container, '$intraApi', 'ksaveras_circuit_breaker.circuit.intraApi');
    }

    public function testInMemoryStorage(): void
    {
        $container = $this->createContainer();

        $container->loadFromExtension('ksaveras_circuit_breaker', [
            'storages' => [
                'memory' => ['type' => 'in_memory'],
            ],
        ]);

        $this->compileContainer($container);

        self::assertTrue($container->has('ksaveras_circuit_breaker.storage.memory'));
        $storageDefinition = $container->getDefinition('ksaveras_circuit_breaker.storage.memory');
        self::assertSame(InMemoryStorage::class, $storageDefinition->getClass());
    }

    public function testCacheStorage(): void
    {
        $container = $this->createContainer();

        $container->loadFromExtension('ksaveras_circuit_breaker', [
            'storages' => [
                'cache' => 'cache.adapter.array',
                'cache_one' => ['type' => 'cache', 'pool' => 'cache.adapter.redis'],
            ],
        ]);

        $this->compileContainer($container);

        self::assertTrue($container->has('ksaveras_circuit_breaker.storage.cache'));
        $storageDefinition = $container->getDefinition('ksaveras_circuit_breaker.storage.cache');
        self::assertSame(CacheStorage::class, $storageDefinition->getClass());
        self::assertInstanceOf(Reference::class, $storageDefinition->getArgument(0));
        self::assertSame('cache.adapter.array', (string) $storageDefinition->getArgument(0));

        self::assertTrue($container->has('ksaveras_circuit_breaker.storage.cache_one'));
        $storageDefinition = $container->getDefinition('ksaveras_circuit_breaker.storage.cache_one');
        self::assertSame(CacheStorage::class, $storageDefinition->getClass());
        self::assertInstanceOf(Reference::class, $storageDefinition->getArgument(0));
        self::assertSame('cache.adapter.redis', (string) $storageDefinition->getArgument(0));
    }

    public function testServiceStorage(): void
    {
        $container = $this->createContainer();

        $container->loadFromExtension('ksaveras_circuit_breaker', [
            'storages' => [
                'my_service1' => '@my_service_id_one',
                'my_service2' => ['type' => 'service', 'id' => 'my_service_id_two'],
            ],
        ]);

        $this->compileContainer($container);

        self::assertTrue($container->has('ksaveras_circuit_breaker.storage.my_service1'));
        $storageAlias = $container->getAlias('ksaveras_circuit_breaker.storage.my_service1');
        self::assertSame('my_service_id_one', (string) $storageAlias);

        self::assertTrue($container->has('ksaveras_circuit_breaker.storage.my_service2'));
        $storageAlias = $container->getAlias('ksaveras_circuit_breaker.storage.my_service2');
        self::assertSame('my_service_id_two', (string) $storageAlias);
    }

    public function testCircuitBreakerFactory(): void
    {
        $container = $this->createContainer();

        $container->loadFromExtension('ksaveras_circuit_breaker', [
            'circuit_breakers' => [
                'web-api' => [
                    'storage' => 'in_memory',
                    'failure_threshold' => 3,
                    'retry_policy' => [
                        'exponential' => [
                            'reset_timeout' => 10,
                            'maximum_timeout' => 600,
                        ],
                    ],
                ],
            ],
            'storages' => [
                'in_memory' => null,
            ],
        ]);

        $container->setAlias(
            'circuit_breaker_factory_public',
            'ksaveras_circuit_breaker.factory.web_api'
        )->setPublic(true);

        $this->compileContainer($container);

        self::assertTrue($container->has('ksaveras_circuit_breaker.factory.web_api'));

        $definition = $container->getDefinition('ksaveras_circuit_breaker.factory.web_api');
        self::assertSame(CircuitBreakerFactory::class, $definition->getClass());

        self::assertSame(3, $definition->getArgument(0));

        self::assertInstanceOf(Reference::class, $definition->getArgument(1));
        self::assertSame('ksaveras_circuit_breaker.storage.in_memory', (string) $definition->getArgument(1));

        $retryPolicyDefinition = $definition->getArgument(2);
        self::assertInstanceOf(Definition::class, $retryPolicyDefinition);
        self::assertSame(ExponentialRetryPolicy::class, $retryPolicyDefinition->getClass());
        self::assertSame([10, 600, 2.0], $retryPolicyDefinition->getArguments());

        $factory = $container->get('circuit_breaker_factory_public');
        self::assertInstanceOf(CircuitBreakerFactory::class, $factory);
    }

    private function runAssertCircuitBreakerDefinition(Definition $definition, string $name): void
    {
        self::assertSame(CircuitBreaker::class, $definition->getClass());
        self::assertSame([$name], $definition->getArguments());

        $factory = $definition->getFactory();
        self::assertIsArray($factory);
        self::assertInstanceOf(Definition::class, $factory[0]);
        self::assertSame(CircuitBreakerFactory::class, $factory[0]->getClass());
        self::assertSame('create', $factory[1]);
    }

    private function runAssertArgumentAlias(ContainerBuilder $container, string $name, string $serviceId): void
    {
        $argumentAlias = CircuitBreakerInterface::class.' '.$name;
        self::assertTrue($container->hasAlias($argumentAlias));

        $aliasDefinition = $container->getAlias($argumentAlias);
        self::assertSame($serviceId, (string) $aliasDefinition);
        self::assertTrue($aliasDefinition->isPrivate());
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.container_class' => 'TestContainer',
        ]));
        $container->registerExtension(new KsaverasCircuitBreakerExtension());

        return $container;
    }

    private function compileContainer(ContainerBuilder $container): void
    {
        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->getCompilerPassConfig()->setAfterRemovingPasses([]);

        $bundle = new KsaverasCircuitBreakerBundle();
        $bundle->build($container);

        $container->compile();
    }
}
