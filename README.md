# Circuit Breaker Symfony Bundle
![Travis (.org) branch](https://img.shields.io/travis/ksaveras/circuit-breaker-bundle/master)
![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability/ksaveras/circuit-breaker-bundle)
![GitHub](https://img.shields.io/github/license/ksaveras/circuit-breaker-bundle)

Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require ksaveras/circuit-breaker-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require ksaveras/circuit-breaker-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Ksaveras\CircuitBreakerBundle\KsaverasCircuitBreakerBundle::class => ['all' => true],
];
```

## Configuration
``` yml
# config/services.yaml
services:
    circuit_breaker_storage_apcu:
        class: Ksaveras\CircuitBreaker\Storage\Apcu
```
``` yml
# config/packages/circuit_breaker.yaml
ksaveras_circuit_breaker:
    circuit_breaker:
        integration_name:
            storage: 'circuit_breaker_storage_apcu'
            reset_period: 600
            failure_threshold: 3
```
