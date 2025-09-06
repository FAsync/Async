<?php

use Fasync\Async\Async;
use Fasync\Async\Timer;
use Fasync\Promise\Interfaces\CancellablePromiseInterface;
use Fasync\Promise\Interfaces\PromiseInterface;

if (! function_exists('in_fiber')) {
    /**
     * Check if the current execution context is within a PHP Fiber.
     *
     * This is essential for determining if async operations can be performed
     * safely or if they need to be wrapped in a fiber context first.
     *
     * @return bool True if executing within a fiber, false otherwise
     */
    function in_fiber(): bool
    {
        return Async::inFiber();
    }
}

if (! function_exists('async')) {
    /**
     * Convert a regular function into an async function that returns a Promise.
     *
     * The returned function will execute the original function within a fiber
     * context, enabling it to use async operations like await. This is the
     * primary method for creating async functions from synchronous code.
     *
     * @param  callable  $asyncFunction  The function to convert to async
     * @return PromiseInterface<mixed> An async version that returns a Promise
     *
     * @example
     * $asyncFunc = async(function($data) {
     *     $result = await(http_get('https://api.example.com'));
     *     return $result;
     * });
     */
    function async(callable $asyncFunction): PromiseInterface
    {
        return Async::async($asyncFunction)();
    }
}

if (! function_exists('await')) {
    /**
     * Suspends the current fiber until the promise is fulfilled or rejected.
     *
     * This method is the heart of the await pattern. It pauses the fiber's
     * execution, allowing the event loop to run other tasks. When the promise
     * settles, the fiber is resumed.
     *
     * @template TValue The expected type of the resolved value from the promise.
     *
     * @param  PromiseInterface<TValue>  $promise  The promise to await.
     * @return TValue The resolved value of the promise.
     *
     * @throws Exception If the promise is rejected, this method throws the rejection reason.
     */
    function await(PromiseInterface $promise): mixed
    {
        return Async::await($promise);
    }
}

if (! function_exists('delay')) {
    /**
     * Create a promise that resolves after a specified time delay.
     *
     * This creates a timer-based promise that will resolve with null after
     * the specified delay. Useful for creating pauses in async execution
     * without blocking the event loop.
     *
     * @param  float  $seconds  Number of seconds to delay
     * @return CancellablePromiseInterface<null> A promise that resolves after the delay
     *
     * @example
     * await(delay(2.5)); // Wait 2.5 seconds
     */
    function delay(float $seconds): CancellablePromiseInterface
    {
        return Timer::delay($seconds);
    }
}