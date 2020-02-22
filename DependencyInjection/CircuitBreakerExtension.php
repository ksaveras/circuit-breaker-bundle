<?php

declare(strict_types=1);

namespace Ksaveras\CircuitBreakerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
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
                $serviceConfig['reset_period']
            ];

            $container->register($id, 'Ksaveras\CircuitBreaker\CircuitBreaker')
                ->setPublic(false)
                ->setArguments($arguments);
        }
    }
}
