<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

use Fasync\Async\Async;
use Fasync\EventLoop\EventLoop;
use Fasync\Promise\Promise;

pest()->extend(Tests\TestCase::class)->in('Feature');
pest()->extend(Tests\TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

// Your existing custom expectation is preserved.
expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

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

