<?php declare(strict_types=1);

namespace Ksaveras\CircuitBreakerBundle\Tests\DependencyInjection\Factory\Storage;

use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\RedisStorageFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RedisStorageFactoryTest extends TestCase
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
            ->with($expectedId, self::callback(function ($definition) {
                self::assertInstanceOf(ChildDefinition::class, $definition);
                self::assertEquals('ksaveras_circuit_breaker.storage.redis.abstract', $definition->getParent());
                self::assertEquals(new Reference('redis_service_id'), $definition->getArgument(0));

                return true;
            }));

        $id = $factory->create($container, 'dummy', ['client' => 'redis_service_id']);

        self::assertEquals($expectedId, $id);
    }
}
