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
use Ksaveras\CircuitBreaker\Storage\InMemoryStorage;
use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\ServiceStorageFactory;
use Ksaveras\CircuitBreakerBundle\DependencyInjection\KsaverasCircuitBreakerExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class KsaverasCircuitBreakerExtensionTest extends TestCase
{
    public function testLoadConfig(): void
    {
        $config = [
            [
                'circuit_breakers' => [
                    'demo' => [
                        'storage' => 'private',
                    ],
                ],
                'storage' => [
                    'private' => [
                        'type' => 'service',
                        'id' => 'storage_service',
                    ],
                ],
            ],
        ];

        $container = new ContainerBuilder();
        $container->register('storage_service', InMemoryStorage::class);

        $extension = new KsaverasCircuitBreakerExtension();
        $extension->addStorageFactory(new ServiceStorageFactory());
        $extension->load($config, $container);

        $this->assertTrue($container->has('ksaveras_circuit_breaker.factory.demo'));

        $definition = $container->getDefinition('ksaveras_circuit_breaker.factory.demo');
        $this->assertEquals(CircuitBreakerFactory::class, $definition->getClass());
        $this->assertEquals(new Reference('ksaveras_circuit_breaker.storage.private'), $definition->getArgument(1));

        $this->assertTrue($container->has('ksaveras_circuit_breaker.circuit.demo'));

        $definition = $container->getDefinition('ksaveras_circuit_breaker.circuit.demo');
        $this->assertEquals(CircuitBreaker::class, $definition->getClass());
        $this->assertEquals('demo', $definition->getArgument(0));
    }

    public function testUnregisteredStorage(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Storage factory of type "service" is not registered');

        $config = [
            [
                'storage' => [
                    'private' => [
                        'type' => 'service',
                        'id' => 'storage_service',
                    ],
                ],
            ],
        ];

        $container = new ContainerBuilder();

        $extension = new KsaverasCircuitBreakerExtension();
        $extension->load($config, $container);
    }
}
