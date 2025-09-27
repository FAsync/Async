<?php

use Hibla\Async\Handlers\PromiseCollectionHandler;
use Hibla\Promise\Promise;
use Hibla\Async\Exceptions\TimeoutException;
use Hibla\EventLoop\Loop;

describe('PromiseCollectionHandler', function () {
    beforeEach(function () {
        $this->handler = new PromiseCollectionHandler();
    });

    it('resolves all promises', function () {
        $promises = [
            fn() => (new Promise())->resolve('result1'),
            fn() => (new Promise())->resolve('result2'),
        ];
        
        $promise = $this->handler->all($promises);
        $results = waitForPromise($promise);
        
        expect($results)->toBe(['result1', 'result2']);
    });

    it('rejects if any promise rejects', function () {
        $promises = [
            fn() => (new Promise())->resolve('success'),
            fn() => (new Promise())->reject(new Exception('failure')),
        ];
        
        $promise = $this->handler->all($promises);
        
        expect(fn() => waitForPromise($promise))
            ->toThrow(Exception::class, 'failure');
    });

    it('handles empty promise array', function () {
        $promise = $this->handler->all([]);
        $results = waitForPromise($promise);
        
        expect($results)->toBe([]);
    });

    it('settles all promises', function () {
        $promises = [
            fn() => (new Promise())->resolve('success'),
            fn() => (new Promise())->reject(new Exception('failure')),
        ];
        
        $promise = $this->handler->allSettled($promises);
        $results = waitForPromise($promise);
        
        expect($results)->toHaveCount(2);
        expect($results[0]['status'])->toBe('fulfilled');
        expect($results[1]['status'])->toBe('rejected');
    });

    it('races promises', function () {
        $slowPromise = fn() => new Promise(function ($resolve) {
            \Hibla\EventLoop\EventLoop::getInstance()->addTimer(0.1, fn() => $resolve('slow'));
        });
        
        $fastPromise = fn() => (new Promise())->resolve('fast');
        
        $promise = $this->handler->race([$slowPromise, $fastPromise]);
        $result = waitForPromise($promise);
        
        expect($result)->toBe('fast');
    });

    it('handles timeout', function () {
        $slowPromise = new Promise(function ($resolve) {
            Loop::addTimer(0.2, fn() => $resolve('slow'));
        });
        
        $promise = $this->handler->timeout($slowPromise, 0.05);
        
        expect(fn() => waitForPromise($promise, 0.3))
            ->toThrow(TimeoutException::class);
    });

    it('resolves any promise', function () {
        $promises = [
            fn() => (new Promise())->reject(new Exception('fail1')),
            fn() => (new Promise())->resolve('success'),
            fn() => (new Promise())->reject(new Exception('fail2')),
        ];
        
        $promise = $this->handler->any($promises);
        $result = waitForPromise($promise);
        
        expect($result)->toBe('success');
    });

    it('rejects when all promises reject', function () {
        $promises = [
            fn() => (new Promise())->reject(new Exception('fail1')),
            fn() => (new Promise())->reject(new Exception('fail2')),
        ];
        
        $promise = $this->handler->any($promises);
        
        expect(fn() => waitForPromise($promise))
            ->toThrow(Exception::class, 'All promises rejected');
    });

    it('validates timeout parameter', function () {
        $promise = new Promise();
        
        expect(fn() => $this->handler->timeout($promise, 0)->await())
            ->toThrow(InvalidArgumentException::class, 'Timeout must be greater than zero');
            
        expect(fn() => $this->handler->timeout($promise, -1)->await())
            ->toThrow(InvalidArgumentException::class, 'Timeout must be greater than zero');
    });
});