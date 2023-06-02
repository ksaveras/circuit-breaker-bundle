<?php declare(strict_types=1);
/*
 * This file is part of ksaveras/circuit-breaker-bundle.
 *
 * (c) Ksaveras Sakys <xawiers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Ksaveras\CircuitBreakerBundle\Tests\DependencyInjection\Factory\Storage;

use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\RedisStorageFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RedisStorageFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new RedisStorageFactory();
        $expectedId = 'ksaveras_circuit_breaker.storage.dummy';

        self::assertEquals('redis', $factory->getType());

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects(self::once())
            ->method('setDefinition')
            ->with($expectedId, self::callback(static function ($definition): bool {
                self::assertInstanceOf(ChildDefinition::class, $definition);
                self::assertEquals('ksaveras_circuit_breaker.storage.redis.abstract', $definition->getParent());
                self::assertEquals(new Reference('redis_service_id'), $definition->getArgument(0));

                return true;
            }));

        $id = $factory->create($container, 'dummy', ['client' => 'redis_service_id']);

        self::assertEquals($expectedId, $id);
    }
}
