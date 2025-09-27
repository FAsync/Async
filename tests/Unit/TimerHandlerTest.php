<?php

use Hibla\Async\Handlers\TimerHandler;

describe('TimerHandler', function () {
    beforeEach(function () {
        $this->handler = new TimerHandler();
    });

    it('creates delay promise', function () {
        $promise = $this->handler->delay(0.01); // 10ms
        
        expect($promise)->toBeCancellablePromise();
        expect($promise->isPending())->toBe(true);
    });

    it('resolves after delay', function () {
        $start = microtime(true);
        $promise = $this->handler->delay(0.05); // 50ms
        
        $result = waitForPromise($promise);
        $elapsed = microtime(true) - $start;
        
        expect($result)->toBeNull();
        expect($elapsed)->toBeGreaterThanOrEqual(0.04); // Allow some margin
    });

    it('can be cancelled', function () {
        $promise = $this->handler->delay(0.1);
        
        expect($promise->isCancelled())->toBeFalse();
        
        $promise->cancel();
        
        expect($promise->isCancelled())->toBeTrue();
    });

    it('handles fractional seconds', function () {
        $start = microtime(true);
        $promise = $this->handler->delay(0.001); // 1ms
        
        waitForPromise($promise);
        $elapsed = microtime(true) - $start;
        
        expect($elapsed)->toBeLessThan(0.05);
    });
});