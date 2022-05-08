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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @dataProvider configsDataProvider
     */
    public function testConfiguration(array $configs, array $expected): void
    {
        $configuration = new Configuration();

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);

        self::assertEquals($expected, $config);
    }

    public function configsDataProvider(): iterable
    {
        yield [
            [
                'ksaveras_circuit_breaker' => [
                    'circuit_breakers' => [
                        'web_api' => [
                            'storage' => 'in_memory',
                            'retry_policy' => [
                                'type' => 'exponential',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'storage' => [],
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'in_memory',
                        'failure_threshold' => 3,
                        'retry_policy' => [
                            'type' => 'exponential',
                            'options' => [
                                'reset_timeout' => 60,
                                'maximum_timeout' => 86400,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield [
            [],
            [
                'storage' => [],
                'circuit_breakers' => [],
            ],
        ];

        yield [
            [
                'ksaveras_circuit_breaker' => [
                    'circuit_breakers' => [
                        'web_api' => [
                            'storage' => 'in_memory',
                        ],
                    ],
                ],
            ],
            [
                'storage' => [],
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'in_memory',
                        'failure_threshold' => 3,
                        'retry_policy' => [
                            'type' => 'exponential',
                            'options' => [
                                'reset_timeout' => 60,
                                'maximum_timeout' => 86400,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield [
            [
                'ksaveras_circuit_breaker' => [
                    'circuit_breakers' => [
                        'web_api' => [
                            'storage' => 'storage_service',
                            'failure_threshold' => 10,
                            'retry_policy' => [
                                'type' => 'constant',
                                'options' => [
                                    'reset_timeout' => 600,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'storage' => [],
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'storage_service',
                        'failure_threshold' => 10,
                        'retry_policy' => [
                            'type' => 'constant',
                            'options' => [
                                'reset_timeout' => 600,
                                'maximum_timeout' => 86400,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider storageTypeConfigProvider
     */
    public function testStorageTypeConfiguration(array $config, array $expected): void
    {
        $configuration = new Configuration();

        $configs = [
            'ksaveras_circuit_breaker' => $config,
        ];

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);

        self::assertEquals($expected, $config['storage']);
    }

    public function storageTypeConfigProvider(): iterable
    {
        yield [
            [],
            [],
        ];

        yield [
            [
                'storage' => [
                    'apcu' => null,
                ],
            ],
            [
                'apcu' => [
                    'type' => 'apcu',
                ],
            ],
        ];

        yield [
            [
                'storage' => [
                    'my_storage' => [
                        'type' => 'apcu',
                    ],
                ],
            ],
            [
                'my_storage' => [
                    'type' => 'apcu',
                ],
            ],
        ];

        yield [
            [
                'storage' => [
                    'my_storage' => 'private_storage',
                ],
            ],
            [
                'my_storage' => [
                    'type' => 'service',
                    'id' => 'private_storage',
                ],
            ],
        ];
    }

    public function testStorageTypeService(): void
    {
        $configuration = new Configuration();

        $configs = [
            'ksaveras_circuit_breaker' => [
                'storage' => [
                    'my_storage' => '@service_id',
                ],
            ],
        ];

        $expected = [
            'my_storage' => [
                'type' => 'service',
                'id' => 'service_id',
            ],
        ];

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);

        self::assertEquals($expected, $config['storage']);
    }

    public function testMissingTypeServiceId(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('You must specify service "id" for storage type "service".');

        $configs = [
            'ksaveras_circuit_breaker' => [
                'storage' => [
                    'my_storage' => [
                        'type' => 'service',
                    ],
                ],
            ],
        ];

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), $configs);
    }

    public function testMissingPhpRedisClientConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('You must specify "client" for storage type "redis".');

        $configs = [
            'ksaveras_circuit_breaker' => [
                'storage' => [
                    'my_storage' => [
                        'type' => 'redis',
                    ],
                ],
            ],
        ];

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), $configs);
    }

    public function testMissingPredisClientConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('You must specify "client" for storage type "predis".');

        $configs = [
            'ksaveras_circuit_breaker' => [
                'storage' => [
                    'my_storage' => [
                        'type' => 'predis',
                    ],
                ],
            ],
        ];

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), $configs);
    }

    public function testMissingPsrCachePoolConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('You must specify "pool" for storage type "psr_cache".');

        $configs = [
            'ksaveras_circuit_breaker' => [
                'storage' => [
                    'my_storage' => [
                        'type' => 'psr_cache',
                    ],
                ],
            ],
        ];

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), $configs);
    }
}
