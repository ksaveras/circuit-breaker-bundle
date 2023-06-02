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
use Symfony\Component\DependencyInjection\Reference;

final class PredisStorageFactory extends AbstractStorageFactory
{
    public function create(ContainerBuilder $container, string $name, array $config = []): string
    {
        $id = $this->serviceId($name);
        $definition = new ChildDefinition('ksaveras_circuit_breaker.storage.predis.abstract');
        $definition->replaceArgument(0, new Reference($config['client']));

        $container->setDefinition($id, $definition);

        return $id;
    }

    public function getType(): string
    {
        return 'predis';
    }
}
