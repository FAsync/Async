<?php

use Hibla\Async\Handlers\AsyncExecutionHandler;
use Hibla\Promise\Interfaces\PromiseInterface;

describe('AsyncExecutionHandler', function () {
    beforeEach(function () {
        $this->handler = new AsyncExecutionHandler();
    });

    it('converts a regular function to async function', function () {
        $asyncFunc = $this->handler->async(fn() => 'test result');
        
        expect($asyncFunc)->toBeCallable();
        
        $promise = $asyncFunc();
        expect($promise)->toBePromise();
        
        $result = waitForPromise($promise);
        expect($result)->toBe('test result');
    });

    it('handles function with arguments', function () {
        $asyncFunc = $this->handler->async(fn($a, $b) => $a + $b);
        
        $promise = $asyncFunc(5, 3);
        $result = waitForPromise($promise);
        
        expect($result)->toBe(8);
    });

    it('handles exceptions in async functions', function () {
        $asyncFunc = $this->handler->async(function () {
            throw new Exception('Test exception');
        });
        
        $promise = $asyncFunc();
        
        expect(fn() => waitForPromise($promise))
            ->toThrow(Exception::class, 'Test exception');
    });

    it('preserves function return types', function () {
        $asyncFunc = $this->handler->async(fn() => ['key' => 'value']);
        
        $promise = $asyncFunc();
        $result = waitForPromise($promise);
        
        expect($result)->toBe(['key' => 'value']);
    });

    it('handles null return values', function () {
        $asyncFunc = $this->handler->async(fn() => null);
        
        $promise = $asyncFunc();
        $result = waitForPromise($promise);
        
        expect($result)->toBeNull();
    });
});