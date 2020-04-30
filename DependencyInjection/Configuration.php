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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('ksaveras_circuit_breaker');
        $rootNode = $treeBuilder->getRootNode();

        $this->addCircuitBreakerSection($rootNode);
        $this->addStorageSection($rootNode);

        return $treeBuilder;
    }

    private function addCircuitBreakerSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->fixXmlConfig('circuit_breaker')
            ->children()
                ->arrayNode('circuit_breakers')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                    ->children()
                        ->scalarNode('storage')
                            ->info('Circuit Breaker Storage service name')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->integerNode('reset_period')
                            ->info('Circuit Breaker reset period in seconds')
                            ->defaultValue(60)
                            ->min(0)
                        ->end()
                        ->integerNode('failure_threshold')
                            ->info('Number of failures before opening the circuit')
                            ->defaultValue(5)
                            ->min(0)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addStorageSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('storage')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('service')->isRequired()->cannotBeEmpty()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
