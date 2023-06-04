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
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('ksaveras_circuit_breaker');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $this->addStorageSection($rootNode);
        $this->addCircuitBreakerSection($rootNode);

        return $treeBuilder;
    }

    private function addStorageSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->fixXmlConfig('storage')
            ->children()
                ->arrayNode('storages')
                    ->useAttributeAsKey('name')
                    ->beforeNormalization()
                        ->always(static function ($config): array {
                            if (!\is_array($config)) {
                                return [];
                            }

                            foreach ($config as $name => $v) {
                                if (null === $v) {
                                    $config[$name] = ['type' => $name];

                                    continue;
                                }
                                if (\is_string($v)) {
                                    if (str_starts_with($v, '@')) {
                                        $config[$name] = ['type' => 'service', 'id' => substr($v, 1)];
                                    } else {
                                        $config[$name] = match ($name) {
                                            'in_memory' => ['type' => $v],
                                            'cache' => ['type' => $name, 'pool' => $v],
                                            default => ['type' => 'service', 'id' => $v],
                                        };
                                    }
                                }
                            }

                            return $config;
                        })
                    ->end()
                    ->arrayPrototype()
                        ->validate()
                            ->ifTrue(static fn($v): bool => 'service' === $v['type'] && !isset($v['id']))
                            ->thenInvalid('You must specify service "id" for storage type "service".')
                        ->end()
                        ->validate()
                            ->ifTrue(static fn($v): bool => 'cache' === $v['type'] && !isset($v['pool']))
                            ->thenInvalid('You must specify "pool" for storage type "cache".')
                        ->end()
                        ->children()
                            ->enumNode('type')
                                ->values(['service', 'in_memory', 'cache'])
                                ->isRequired()
                            ->end()
                            ->scalarNode('id')->end()
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
                    ->beforeNormalization()
                        ->always(static function (array $values): array {
                            foreach ($values as $name => $config) {
                                if (!isset($config['retry_policy'])) {
                                    $values[$name]['retry_policy']['exponential'] = ['enabled' => true];
                                }
                            }

                            return $values;
                        })
                    ->end()
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
                            ->beforeNormalization()
                                ->always(static function (array $values): array {
                                    foreach ($values as $type => $value) {
                                        if (!is_array($value)) {
                                            continue;
                                        }
                                        if (isset($value['enabled'])) {
                                            continue;
                                        }
                                        $values[$type]['enabled'] = true;
                                    }

                                    return $values;
                                })
                            ->end()
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->append($this->addExponentialRetryPolicyNode())
                                ->append($this->addLinearRetryPolicyNode())
                                ->append($this->addConstantRetryPolicyNode())
                            ->end()
                            ->validate()
                                ->ifTrue(static fn($v): bool => 1 !== count(array_filter(
                                    array_values($v),
                                    static fn ($i) => $i['enabled'] ?? false
                                )))
                                ->thenInvalid('Only one retry policy can be configured per circuit breaker.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addExponentialRetryPolicyNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('exponential');

        return $treeBuilder->getRootNode()
            ->treatFalseLike(['enabled' => false])
            ->treatTrueLike(['enabled' => true])
            ->treatNullLike(['enabled' => true])
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->integerNode('reset_timeout')
                    ->info('Number of seconds the circuit breaker will be in open state')
                    ->defaultValue(10)
                    ->min(1)
                ->end()
                ->floatNode('base')
                    ->info('Base value for exponential function')
                    ->defaultValue(2.0)
                    ->min(1.01)
                ->end()
                ->integerNode('maximum_timeout')
                    ->info('Maximum number of seconds for open circuit')
                    ->defaultValue(86400)
                    ->min(10)
                ->end()
            ->end();
    }

    private function addLinearRetryPolicyNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('linear');

        return $treeBuilder->getRootNode()
            ->treatFalseLike(['enabled' => false])
            ->treatTrueLike(['enabled' => true])
            ->treatNullLike(['enabled' => true])
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->integerNode('reset_timeout')
                    ->info('Number of seconds the circuit breaker will be in open state')
                    ->defaultValue(10)
                    ->min(1)
                ->end()
                ->integerNode('step')
                    ->info('Step size in seconds for increasing the open circuit TTL')
                    ->defaultValue(60)
                    ->min(1)
                ->end()
                ->integerNode('maximum_timeout')
                    ->info('Maximum number of seconds for open circuit')
                    ->defaultValue(86400)
                    ->min(10)
                ->end()
            ->end();
    }

    private function addConstantRetryPolicyNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('constant');

        return $treeBuilder->getRootNode()
            ->treatFalseLike(['enabled' => false])
            ->treatTrueLike(['enabled' => true])
            ->treatNullLike(['enabled' => true])
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->integerNode('reset_timeout')
                    ->info('Number of seconds the circuit breaker will be in open state')
                    ->defaultValue(10)
                    ->min(1)
                ->end()
            ->end();
    }
}
