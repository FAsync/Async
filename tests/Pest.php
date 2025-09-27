<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

use Hibla\Async\Async;
use Hibla\EventLoop\EventLoop;
use Hibla\Promise\Interfaces\CancellablePromiseInterface;
use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Promise\Promise;

pest()->extend(Tests\TestCase::class)->in('Feature');
pest()->extend(Tests\TestCase::class)->in('Unit');
pest()->extend(Tests\TestCase::class)->in('Integration');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBePromise', function () {
    return $this->toBeInstanceOf(PromiseInterface::class);
});

expect()->extend('toBeCancellablePromise', function () {
    return $this->toBeInstanceOf(CancellablePromiseInterface::class);
});

expect()->extend('toBeSettled', function () {
    $promise = $this->value;
    
    if (!($promise instanceof PromiseInterface)) {
        throw new InvalidArgumentException('Value must be a Promise');
    }
    
    return $this->toBe($promise->isPending());
});

function waitForPromise(PromiseInterface $promise): mixed
{    
    return $promise->await();
}

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Resets all core singletons and clears test state.
 *
 * This function is the single source of truth for test setup. By calling it
 * in each test file's `beforeEach` hook, we ensure perfect test isolation.
 */
function resetEventLoop()
{
    EventLoop::reset();
    Async::reset();
    Promise::reset();
}
