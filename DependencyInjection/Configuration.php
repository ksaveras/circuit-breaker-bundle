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

        $this->addStorageSection($rootNode);
        $this->addCircuitBreakerSection($rootNode);

        return $treeBuilder;
    }

    private function addStorageSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('storage')
                    ->useAttributeAsKey('name')
                    ->beforeNormalization()
                        ->always(static function ($config) {
                            if (!\is_array($config)) {
                                return [];
                            }

                            foreach ($config as $name => $v) {
                                if (null === $v) {
                                    $config[$name] = ['type' => $name];

                                    continue;
                                }
                                if (\is_string($v)) {
                                    if (0 === strpos($v, '@')) {
                                        $config[$name] = ['type' => 'service', 'id' => substr($v, 1)];
                                    } else {
                                        switch ($name) {
                                          case 'apcu':
                                          case 'in_memory':
                                              $config[$name] = ['type' => $v];

                                              break;
                                          case 'redis':
                                          case 'predis':
                                              $config[$name] = ['type' => $name, 'client' => $v];

                                              break;
                                          case 'psr_cache':
                                              $config[$name] = ['type' => $name, 'pool' => $v];

                                              break;
                                          default:
                                              $config[$name] = ['type' => 'service', 'id' => $v];
                                        }
                                    }
                                }
                            }

                            return $config;
                        })
                    ->end()
                    ->arrayPrototype()
                        ->validate()
                            ->ifTrue(function ($v) { return 'service' === $v['type'] && !isset($v['id']); })
                            ->thenInvalid('You must specify service "id" for storage type "service".')
                        ->end()
                        ->validate()
                            ->ifTrue(function ($v) { return 'redis' === $v['type'] && !isset($v['client']); })
                            ->thenInvalid('You must specify "client" for storage type "redis".')
                        ->end()
                        ->validate()
                            ->ifTrue(function ($v) { return 'predis' === $v['type'] && !isset($v['client']); })
                            ->thenInvalid('You must specify "client" for storage type "predis".')
                        ->end()
                        ->validate()
                            ->ifTrue(function ($v) { return 'psr_cache' === $v['type'] && !isset($v['pool']); })
                            ->thenInvalid('You must specify "pool" for storage type "psr_cache".')
                        ->end()
                        ->children()
                            ->enumNode('type')
                                ->values(['service', 'apcu', 'in_memory', 'redis', 'psr_cache', 'predis'])
                                ->isRequired()
                            ->end()
                            ->scalarNode('id')->end()
                            ->scalarNode('client')->end()
                            ->scalarNode('pool')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
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
                            ->info('Circuit Breaker Storage name')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->integerNode('failure_threshold')
                            ->info('Number of failures before opening the circuit')
                            ->defaultValue(3)
                            ->min(1)
                        ->end()
                        ->arrayNode('retry_policy')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->enumNode('type')
                                    ->values(['exponential', 'constant', 'linear'])
                                    ->defaultValue('exponential')
                                    ->cannotBeEmpty()
                                ->end()
                                ->arrayNode('options')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('reset_timeout')
                                            ->defaultValue(60)
                                            ->min(1)
                                        ->end()
                                        ->integerNode('maximum_timeout')
                                            ->defaultValue(86400)
                                            ->min(1)
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
