<?php declare(strict_types=1);
/*
 * This file is part of ksaveras/circuit-breaker-bundle.
 *
 * (c) Ksaveras Sakys <xawiers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Ksaveras\CircuitBreakerBundle\DependencyInjection;

use Ksaveras\CircuitBreaker\CircuitBreaker;
use Ksaveras\CircuitBreaker\Storage\StorageInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class CircuitBreakerExtension extends ConfigurableExtension
{
    protected function loadInternal(array $config, ContainerBuilder $container)
    {
        $eventDispatcherRef = $container->has('event_dispatcher') ? new Reference('event_dispatcher') : null;

        foreach ($config['circuit_breakers'] as $name => $serviceConfig) {
            $id = sprintf('ksaveras.%s.%s', $this->getAlias(), $name);

            $storageReference = new Reference($serviceConfig['storage']);
            $storageDefinition = $container->getDefinition($serviceConfig['storage']);

            $implements = class_implements((string) $storageDefinition->getClass());
            if (!\array_key_exists(StorageInterface::class, $implements)) {
                throw new InvalidConfigurationException(sprintf('Invalid "%s" storage service. Service must implement "%s" interface.', $serviceConfig['storage'], StorageInterface::class));
            }

            $arguments = [
                $name,
                $storageReference,
                $serviceConfig['reset_period'],
            ];

            $definition = $container->register($id, CircuitBreaker::class)
                ->setPublic(false)
                ->setArguments($arguments);
            $definition->addMethodCall('setFailureThreshold', [$serviceConfig['failure_threshold']]);

            if (null !== $eventDispatcherRef) {
                $definition->addMethodCall('setEventDispatcher', [$eventDispatcherRef]);
            }
        }
    }
}
