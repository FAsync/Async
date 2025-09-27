<?php

use Hibla\Async\Handlers\FiberContextHandler;

describe('FiberContextHandler', function () {
    beforeEach(function () {
        $this->handler = new FiberContextHandler();
    });

    it('detects when not in fiber context', function () {
        expect($this->handler->inFiber())->toBeFalse();
    });

    it('throws exception when validating outside fiber context', function () {
        expect(fn() => $this->handler->validateFiberContext())
            ->toThrow(RuntimeException::class, 'Operation can only be used inside a Fiber context');
    });

    it('throws exception with custom message', function () {
        $customMessage = 'Custom fiber context required';
        
        expect(fn() => $this->handler->validateFiberContext($customMessage))
            ->toThrow(RuntimeException::class, $customMessage);
    });

    it('detects when inside fiber context', function () {
        $result = null;
        
        $fiber = new Fiber(function () use (&$result) {
            $handler = new FiberContextHandler();
            $result = $handler->inFiber();
        });
        
        $fiber->start();
        
        expect($result)->toBeTrue();
    });

    it('validates successfully inside fiber context', function () {
        $validated = false;
        
        $fiber = new Fiber(function () use (&$validated) {
            $handler = new FiberContextHandler();
            $handler->validateFiberContext();
            $validated = true;
        });
        
        $fiber->start();
        
        expect($validated)->toBeTrue();
    });
});