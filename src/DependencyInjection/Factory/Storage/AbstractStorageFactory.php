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

abstract class AbstractStorageFactory implements StorageFactoryInterface
{
    protected function serviceId(string $name): string
    {
        return \sprintf('ksaveras_circuit_breaker.storage.%s', $name);
    }
}
