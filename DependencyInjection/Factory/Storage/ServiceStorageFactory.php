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

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceStorageFactory extends AbstractStorageFactory
{
    public function create(ContainerBuilder $container, string $name, array $config = []): string
    {
        $id = $this->serviceId($name);
        $container->setAlias($id, $config['id']);

        return $id;
    }

    public function getType(): string
    {
        return 'service';
    }
}
