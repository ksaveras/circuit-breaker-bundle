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

use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\InMemoryStorageFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class InMemoryStorageFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new InMemoryStorageFactory();
        $expectedId = 'ksaveras_circuit_breaker.storage.dummy';

        self::assertEquals('in_memory', $factory->getType());

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects(self::once())
            ->method('setDefinition')
            ->with($expectedId, new ChildDefinition('ksaveras_circuit_breaker.storage.in_memory.abstract'));

        $id = $factory->create($container, 'dummy');

        self::assertEquals($expectedId, $id);
    }
}
