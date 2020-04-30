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

        $this->assertEquals($expected, $config);
    }

    public function configsDataProvider(): \Generator
    {
        yield [
            [],
            [
                'circuit_breakers' => [],
                'storage' => [],
            ],
        ];

        yield [
            [
                'ksaveras_circuit_breaker' => [
                    'circuit_breakers' => [
                        'web_api' => [
                            'storage' => 'storage_service',
                        ],
                    ],
                ],
            ],
            [
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'storage_service',
                        'reset_period' => 60,
                        'failure_threshold' => 5,
                    ],
                ],
                'storage' => [],
            ],
        ];

        yield [
            [
                'ksaveras_circuit_breaker' => [
                    'circuit_breakers' => [
                        'web_api' => [
                            'storage' => 'storage_service',
                            'reset_period' => 300,
                            'failure_threshold' => 10,
                        ],
                    ],
                ],
            ],
            [
                'circuit_breakers' => [
                    'web_api' => [
                        'storage' => 'storage_service',
                        'reset_period' => 300,
                        'failure_threshold' => 10,
                    ],
                ],
                'storage' => [],
            ],
        ];

        yield [
            [
                'ksaveras_circuit_breaker' => [
                    'storage' => [
                        'apcu' => [
                            'service' => 'storage_service',
                        ],
                    ],
                ],
            ],
            [
                'circuit_breakers' => [],
                'storage' => [
                    'apcu' => [
                        'service' => 'storage_service',
                    ],
                ],
            ],
        ];
    }
}
