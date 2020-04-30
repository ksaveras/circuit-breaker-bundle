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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class CircuitBreakerExtension extends ConfigurableExtension
{
    public function loadInternal(array $config, ContainerBuilder $container)
    {
        foreach ($config['circuit_breakers'] as $name => $serviceConfig) {
            $id = sprintf('%s.%s', $this->getAlias(), $name);

            $storageService = new Reference($serviceConfig['storage']);

            $arguments = [
                $name,
                $storageService,
                $serviceConfig['reset_period'],
            ];

            $definition = $container->register($id, 'Ksaveras\CircuitBreaker\CircuitBreaker')
                ->setPublic(false)
                ->setArguments($arguments);
            $definition->addMethodCall('setEventDispatcher', [ref('event_dispatcher')]);
            $definition->addMethodCall('setFailureThreshold', [$serviceConfig['failure_threshold']]);
        }
    }
}
