<?php

declare(strict_types=1);

namespace Ksaveras\CircuitBreakerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('ksaveras_circuit_breaker');

        $treeBuilder->getRootNode()
            ->fixXmlConfig('circuit_breaker')
            ->children()
                ->arrayNode('circuit_breakers')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('storage')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->integerNode('reset_period')
                                ->defaultValue(60)
                                ->min(0)
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('storage')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('service')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
