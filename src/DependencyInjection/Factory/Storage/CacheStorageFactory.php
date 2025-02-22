<?php declare(strict_types=1);
/*
 * This file is part of ksaveras/circuit-breaker-bundle.
 *
 * (c) Ksaveras Sakys <xawiers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage;

use Ksaveras\CircuitBreaker\Storage\CacheStorage;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class CacheStorageFactory extends AbstractStorageFactory
{
    public function create(ContainerBuilder $container, string $name, array $config = []): string
    {
        $id = $this->serviceId($name);
        $definition = $container->register($id, CacheStorage::class);
        $definition->setArguments([new Reference($config['pool'])]);

        return $id;
    }

    public function getType(): string
    {
        return 'cache';
    }
}
