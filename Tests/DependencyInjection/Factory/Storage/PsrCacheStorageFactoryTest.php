<?php declare(strict_types=1);

namespace Ksaveras\CircuitBreakerBundle\Tests\DependencyInjection\Factory\Storage;

use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\PsrCacheStorageFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PsrCacheStorageFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new PsrCacheStorageFactory();
        $expectedId = 'ksaveras_circuit_breaker.storage.dummy';

        self::assertEquals('psr_cache', $factory->getType());

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects(self::once())
            ->method('setDefinition')
            ->with($expectedId, self::callback(function ($definition) {
                self::assertInstanceOf(ChildDefinition::class, $definition);
                self::assertEquals('ksaveras_circuit_breaker.storage.psr_cache.abstract', $definition->getParent());
                self::assertEquals(new Reference('cache_pool'), $definition->getArgument(0));

                return true;
            }));

        $id = $factory->create($container, 'dummy', ['pool' => 'cache_pool']);

        self::assertEquals($expectedId, $id);
    }
}
