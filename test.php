<?php

use Hibla\Async\Timer;
use Hibla\Promise\Promise;

use function Hibla\async;
use function Hibla\sleep;

require 'vendor/autoload.php';
$startTime = microtime(true);
await(Promise::all([
    async(function () {
        sleep(1);
    }),
    async(function () {
        sleep(1);
    }),
    async(function () {
        sleep(1);
    }),
]));
$endTime = microtime(true);
$elapsedTime = $endTime - $startTime;
echo 'top level await elapsed time: ' . $elapsedTime . ' seconds' . PHP_EOL;


$startTime1 = microtime(true);
Promise::all([
    async(function () {
        sleep(1);
    }),
    async(function () {
        sleep(1);
    }),
    async(function () {
        sleep(1);
    }),
])->await();
$endTime1 = microtime(true);
$elapsedTime1 = $endTime1 - $startTime1;
echo 'await chain elapsed time: ' . $elapsedTime1 . ' seconds' . PHP_EOL;

echo 'end' . PHP_EOL;
