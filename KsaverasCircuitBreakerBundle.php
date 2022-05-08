<?php declare(strict_types=1);
/*
 * This file is part of ksaveras/circuit-breaker-bundle.
 *
 * (c) Ksaveras Sakys <xawiers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Ksaveras\CircuitBreakerBundle;

use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\ApcuStorageFactory;
use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\InMemoryStorageFactory;
use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\PredisStorageFactory;
use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\PsrCacheStorageFactory;
use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\RedisStorageFactory;
use Ksaveras\CircuitBreakerBundle\DependencyInjection\Factory\Storage\ServiceStorageFactory;
use Ksaveras\CircuitBreakerBundle\DependencyInjection\KsaverasCircuitBreakerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KsaverasCircuitBreakerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        /** @var KsaverasCircuitBreakerExtension $extension */
        $extension = $container->getExtension('ksaveras_circuit_breaker');
        $extension->addStorageFactory(new ApcuStorageFactory());
        $extension->addStorageFactory(new InMemoryStorageFactory());
        $extension->addStorageFactory(new PredisStorageFactory());
        $extension->addStorageFactory(new PsrCacheStorageFactory());
        $extension->addStorageFactory(new RedisStorageFactory());
        $extension->addStorageFactory(new ServiceStorageFactory());
    }
}
