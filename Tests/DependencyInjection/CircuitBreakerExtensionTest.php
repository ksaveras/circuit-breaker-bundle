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
use Ksaveras\CircuitBreaker\Storage\PhpArray;
use Ksaveras\CircuitBreakerBundle\DependencyInjection\CircuitBreakerExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CircuitBreakerExtensionTest extends TestCase
{
    public function testLoadConfig(): void
    {
        $config = [
            [
                'circuit_breakers' => [
                    'demo' => [
                        'storage' => 'storage_service',
                    ],
                ],
            ],
        ];

        $container = new ContainerBuilder();
        $container->register('event_dispatcher', EventDispatcherInterface::class);
        $container->register('storage_service', PhpArray::class);

        $extension = new CircuitBreakerExtension();
        $extension->load($config, $container);

        $this->assertTrue($container->has('ksaveras.circuit_breaker.demo'));

        $definition = $container->getDefinition('ksaveras.circuit_breaker.demo');
        $this->assertEquals(CircuitBreaker::class, $definition->getClass());

        $this->assertEquals('demo', $definition->getArgument(0));
        $this->assertEquals(60, $definition->getArgument(2));

        $methodCalls = $definition->getMethodCalls();
        $this->assertContains(['setFailureThreshold', [5]], $methodCalls);
    }

    public function testInvalidStorageService(): void
    {
        $config = [
            [
                'circuit_breakers' => [
                    'demo' => [
                        'storage' => 'file_storage',
                    ],
                ],
            ],
        ];

        $container = new ContainerBuilder();
        $container->register('file_storage', \stdClass::class);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid "file_storage" storage service. Service must implement "Ksaveras\CircuitBreaker\Storage\StorageInterface" interface.');

        $extension = new CircuitBreakerExtension();
        $extension->load($config, $container);
    }
}
