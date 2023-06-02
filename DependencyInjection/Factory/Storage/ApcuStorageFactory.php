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

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ApcuStorageFactory extends AbstractStorageFactory
{
    public function create(ContainerBuilder $container, string $name, array $config = []): string
    {
        $id = $this->serviceId($name);
        $container->setDefinition($id, new ChildDefinition('ksaveras_circuit_breaker.storage.apcu.abstract'));

        return $id;
    }

    public function getType(): string
    {
        return 'apcu';
    }
}
