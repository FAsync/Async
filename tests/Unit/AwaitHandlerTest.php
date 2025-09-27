<?php

use Hibla\Async\Handlers\AsyncExecutionHandler;
use Hibla\Async\Handlers\AwaitHandler;
use Hibla\Async\Handlers\FiberContextHandler;
use Hibla\EventLoop\Loop;
use Hibla\Promise\Interfaces\PromiseInterface;
use Hibla\Promise\Promise;
use PHPUnit\Event\Event;

describe('AwaitHandler', function () {
    beforeEach(function () {
        $this->contextHandler = new FiberContextHandler();
        $this->awaitHandler = new AwaitHandler($this->contextHandler);
    });

    it('awaits resolved promise outside fiber context', function () {
        $promise = new Promise();
        $promise->resolve('test value');

        $result = $this->awaitHandler->await($promise);
        expect($result)->toBe('test value');
    });

    it('awaits rejected promise outside fiber context', function () {
        $promise = new Promise();
        $promise->reject(new Exception('test error'));

        expect(fn() => $this->awaitHandler->await($promise))
            ->toThrow(Exception::class, 'test error');
    });

    it('awaits promise inside fiber context using AsyncExecutionHandler', function () {
        $asyncHandler = new AsyncExecutionHandler();

        $asyncFunction = $asyncHandler->async(function () {
            $contextHandler = new FiberContextHandler();
            $awaitHandler = new AwaitHandler($contextHandler);

            $promise = new Promise();

            $promise->resolve('fiber result');

            return $awaitHandler->await($promise);
        });

        $resultPromise = $asyncFunction();
        Loop::run();

        $result = $resultPromise->await();
        expect($result)->toBe('fiber result');
    });

    it('handles string rejection reasons', function () {
        $promise = new Promise();
        $promise->reject('string error');

        expect(fn() => $this->awaitHandler->await($promise))
            ->toThrow(Exception::class, 'string error');
    });

    it('handles object rejection reasons with toString', function () {
        $errorObj = new class {
            public function __toString(): string
            {
                return 'object error';
            }
        };

        $promise = new Promise();
        $promise->reject($errorObj);

        expect(fn() => $this->awaitHandler->await($promise))
            ->toThrow(Exception::class, 'object error');
    });
});
