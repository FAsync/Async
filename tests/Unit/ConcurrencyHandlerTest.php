<?php

use Hibla\Async\Handlers\ConcurrencyHandler;
use Hibla\Async\Handlers\AsyncExecutionHandler;

describe('ConcurrencyHandler', function () {
    beforeEach(function () {
        $this->executionHandler = new AsyncExecutionHandler();
        $this->handler = new ConcurrencyHandler($this->executionHandler);
    });

    it('runs tasks concurrently', function () {
        $tasks = [
            fn() => 'result1',
            fn() => 'result2',
            fn() => 'result3',
        ];
        
        $promise = $this->handler->concurrent($tasks, 2);
        $results = waitForPromise($promise);
        
        expect($results)->toBe(['result1', 'result2', 'result3']);
    });

    it('respects concurrency limit', function () {
        $counter = 0;
        $maxConcurrent = 0;
        
        $tasks = array_fill(0, 5, function () use (&$counter, &$maxConcurrent) {
            $counter++;
            $maxConcurrent = max($maxConcurrent, $counter);
            usleep(10000); // 10ms
            $counter--;
            return 'done';
        });
        
        $promise = $this->handler->concurrent($tasks, 2);
        waitForPromise($promise);
        
        expect($maxConcurrent)->toBeLessThanOrEqual(2);
    });

    it('handles empty task array', function () {
        $promise = $this->handler->concurrent([]);
        $results = waitForPromise($promise);
        
        expect($results)->toBe([]);
    });

    it('preserves array keys', function () {
        $tasks = [
            'task1' => fn() => 'result1',
            'task2' => fn() => 'result2',
        ];
        
        $promise = $this->handler->concurrent($tasks);
        $results = waitForPromise($promise);
        
        expect($results)->toBe([
            'task1' => 'result1',
            'task2' => 'result2',
        ]);
    });

    it('handles task exceptions', function () {
        $tasks = [
            fn() => 'success',
            fn() => throw new Exception('task failed'),
        ];
        
        $promise = $this->handler->concurrent($tasks);
        
        expect(fn() => waitForPromise($promise))
            ->toThrow(Exception::class, 'task failed');
    });

    it('runs batch processing', function () {
        $tasks = array_fill(0, 5, fn() => 'result');
        
        $promise = $this->handler->batch($tasks, 2);
        $results = waitForPromise($promise);
        
        expect($results)->toHaveCount(5);
        expect(array_unique($results))->toBe(['result']);
    });

    it('handles concurrent settled operations', function () {
        $tasks = [
            fn() => 'success',
            fn() => throw new Exception('failure'),
            fn() => 'another success',
        ];
        
        $promise = $this->handler->concurrentSettled($tasks);
        $results = waitForPromise($promise);
        
        expect($results)->toHaveCount(3);
        expect($results[0]['status'])->toBe('fulfilled');
        expect($results[0]['value'])->toBe('success');
        expect($results[1]['status'])->toBe('rejected');
        expect($results[2]['status'])->toBe('fulfilled');
    });

    it('validates concurrency parameter', function () {
        expect(fn() => $this->handler->concurrent([], 0)->await())
            ->toThrow(InvalidArgumentException::class, 'Concurrency limit must be greater than 0');
            
        expect(fn() => $this->handler->concurrent([], -1)->await())
            ->toThrow(InvalidArgumentException::class, 'Concurrency limit must be greater than 0');
    });
});