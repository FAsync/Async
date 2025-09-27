<?php

use Hibla\Async\Handlers\AsyncExecutionHandler;
use Hibla\Async\Handlers\ConcurrencyHandler;
use Hibla\Async\Handlers\PromiseCollectionHandler;
use Hibla\Async\Timer;

require 'vendor/autoload.php';

$collection = new PromiseCollectionHandler();
$concurrencyHandler = new ConcurrencyHandler(new AsyncExecutionHandler);

// Test with array keys
$promise = [
    'first' => Timer::delay(0.01)->then(fn() => 'first_value'),  
    'second' => Timer::delay(0.02)->then(fn() => 'second_value'), 
    'third' => Timer::delay(0.005)->then(fn() => 'third_value')  
];

$results = await($concurrencyHandler->concurrent($promise));

echo 'concurrent (with keys)' . PHP_EOL;
print_r($results);

// Test without array keys
$promiseNoKeys = [
    Timer::delay(0.01)->then(fn() => 'first_value'),  
    Timer::delay(0.02)->then(fn() => 'second_value'), 
    Timer::delay(0.005)->then(fn() => 'third_value')  
];

$results = await($concurrencyHandler->concurrent($promiseNoKeys));

echo 'concurrent (without keys)' . PHP_EOL;
print_r($results);

// Batch with keys
$anotherPromise = [
    'first' => Timer::delay(0.01)->then(fn() => 'first_value'),  
    'second' => Timer::delay(0.02)->then(fn() => 'second_value'), 
    'third' => Timer::delay(0.005)->then(fn() => 'third_value')  
];

$results = await($concurrencyHandler->batch($anotherPromise));

echo 'batch (with keys)' . PHP_EOL;
print_r($results);

// Batch without keys
$anotherPromiseNoKeys = [
    Timer::delay(0.01)->then(fn() => 'first_value'),  
    Timer::delay(0.02)->then(fn() => 'second_value'), 
    Timer::delay(0.005)->then(fn() => 'third_value')  
];

$results = await($concurrencyHandler->batch($anotherPromiseNoKeys));

echo 'batch (without keys)' . PHP_EOL;
print_r($results);

// Collection all with keys
$yetAnotherPromise = [
    'first' => Timer::delay(0.01)->then(fn() => 'first_value'),  
    'second' => Timer::delay(0.02)->then(fn() => 'second_value'), 
    'third' => Timer::delay(0.005)->then(fn() => 'third_value')  
];

$results = await($collection->all($yetAnotherPromise));

echo 'all (with keys)' . PHP_EOL;
print_r($results);

// Collection all without keys
$yetAnotherPromiseNoKeys = [
    Timer::delay(0.01)->then(fn() => 'first_value'),  
    Timer::delay(0.02)->then(fn() => 'second_value'), 
    Timer::delay(0.005)->then(fn() => 'third_value')  
];

$results = await($collection->all($yetAnotherPromiseNoKeys));

echo 'all (without keys)' . PHP_EOL;
print_r($results);