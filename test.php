<?php

use Hibla\Async\Handlers\AwaitHandler;
use Hibla\Async\Handlers\FiberContextHandler;
use Hibla\Promise\Promise;

require __DIR__ . '/vendor/autoload.php';

$fiber = new Fiber(function () {
    $contextHandler = new FiberContextHandler();
    $awaitHandler = new AwaitHandler($contextHandler);

    $promise = new Promise();
    $promise->resolve('fiber result');

    $result = $awaitHandler->await($promise);
    return $result;
});

print_r($fiber);

