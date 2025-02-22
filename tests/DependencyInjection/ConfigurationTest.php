<?php declare(strict_types=1);
/*
 * This file is part of ksaveras/circuit-breaker-bundle.
 *
 * (c) Ksaveras Sakys <xawiers@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ksaveras\CircuitBreakerBundle\Tests\DependencyInjection;

use Ksaveras\CircuitBreakerBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    #[DataProvider('configsDataProvider')]
    public function testConfiguration(array $configs, array $expected): void
    {
        $configuration = new Configuration();

        $processor = new Processor();
        $config = $processor->processConfiguration(
            $configuration,
            ['ksaveras_circuit_breaker' => $configs],
        );

        self::assertEquals($expected, $config);
    }

    public static function configsDataProvider(): iterable
    {
        yield 'empty configuration' => [
            [],
            ['circuit_breakers' => [], 'storages' => []],
        ];

        yield 'storage configuration' => [
            [
                'storages' => [
                    'in_memory' => null,
                    'my_memory' => ['type' => 'in_memory'],
                    'cache' => 'cache.pool.array',
                    'cache_redis' => ['type' => 'cache', 'pool' => 'cache.pool.redis'],
                    'service_one' => '@app_service.one',
                    'service_two' => ['type' => 'service', 'id' => 'app_service.two'],
                ],
            ],
            [
                'circuit_breakers' => [],
                'storages' => [
                    'in_memory' => ['type' => 'in_memory'],
                    'my_memory' => ['type' => 'in_memory'],
                    'cache' => ['type' => 'cache', 'pool' => 'cache.pool.array'],
                    'cache_redis' => ['type' => 'cache', 'pool' => 'cache.pool.redis'],
                    'service_one' => ['type' => 'service', 'id' => 'app_service.one'],
                    'service_two' => ['type' => 'service', 'id' => 'app_service.two'],
                ],
            ],
        ];

        yield 'exponential retry policy defaults' => [
            [
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'memory',
                    ],
                ],
            ],
            [
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'memory',
                        'failure_threshold' => 3,
                        'retry_policy' => self::getDefaultPolicyConfiguration(),
                        'header_policy' => ['retry_after', 'rate_limit'],
                    ],
                ],
                'storages' => [],
            ],
        ];

        $policyConfig = self::getDefaultPolicyConfiguration();
        $policyConfig['exponential']['enabled'] = false;
        $policyConfig['linear']['enabled'] = true;

        yield 'linear retry policy defaults' => [
            [
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'memory',
                        'retry_policy' => [
                            'linear' => true,
                        ],
                    ],
                ],
            ],
            [
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'memory',
                        'failure_threshold' => 3,
                        'retry_policy' => $policyConfig,
                        'header_policy' => ['retry_after', 'rate_limit'],
                    ],
                ],
                'storages' => [],
            ],
        ];

        $policyConfig = self::getDefaultPolicyConfiguration();
        $policyConfig['exponential']['enabled'] = false;
        $policyConfig['constant']['enabled'] = true;

        yield 'constant retry policy defaults' => [
            [
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'memory',
                        'retry_policy' => [
                            'constant' => true,
                        ],
                    ],
                ],
            ],
            [
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'memory',
                        'failure_threshold' => 3,
                        'retry_policy' => $policyConfig,
                        'header_policy' => ['retry_after', 'rate_limit'],
                    ],
                ],
                'storages' => [],
            ],
        ];

        yield 'header policies as null' => [
            [
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'memory',
                        'header_policy' => null,
                    ],
                ],
            ],
            [
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'memory',
                        'failure_threshold' => 3,
                        'retry_policy' => self::getDefaultPolicyConfiguration(),
                        'header_policy' => [],
                    ],
                ],
                'storages' => [],
            ],
        ];
    }

    public function testMissingTypeServiceId(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('You must specify service "id" for storage type "service".');

        $configs = [
            'ksaveras_circuit_breaker' => [
                'storages' => [
                    'my_storage' => [
                        'type' => 'service',
                    ],
                ],
            ],
        ];

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), $configs);
    }

    public function testMissingCachePoolConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('You must specify "pool" for storage type "cache".');

        $configs = [
            'ksaveras_circuit_breaker' => [
                'storages' => [
                    'my_storage' => [
                        'type' => 'cache',
                    ],
                ],
            ],
        ];

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), $configs);
    }

    private static function getDefaultPolicyConfiguration(): array
    {
        return [
            'exponential' => [
                'reset_timeout' => 10,
                'base' => 2,
                'maximum_timeout' => 86400,
                'enabled' => true,
            ],
            'linear' => [
                'reset_timeout' => 10,
                'step' => 60,
                'maximum_timeout' => 86400,
                'enabled' => false,
            ],
            'constant' => [
                'reset_timeout' => 10,
                'enabled' => false,
            ],
        ];
    }
}
