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

use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\PredisStorageFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PredisStorageFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new PredisStorageFactory();
        $expectedId = 'ksaveras_circuit_breaker.storage.dummy';

        self::assertEquals('predis', $factory->getType());

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects(self::once())
            ->method('setDefinition')
            ->with($expectedId, self::callback(function ($definition) {
                self::assertInstanceOf(ChildDefinition::class, $definition);
                self::assertEquals('ksaveras_circuit_breaker.storage.predis.abstract', $definition->getParent());
                self::assertEquals(new Reference('predis_service_id'), $definition->getArgument(0));

                return true;
            }));

        $id = $factory->create($container, 'dummy', ['client' => 'predis_service_id']);

        self::assertEquals($expectedId, $id);
    }
}
