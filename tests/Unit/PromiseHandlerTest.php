<?php

use Hibla\Async\Handlers\PromiseHandler;
use Hibla\Promise\Interfaces\PromiseInterface;

describe('PromiseHandler', function () {
    beforeEach(function () {
        $this->handler = new PromiseHandler();
    });

    it('creates resolved promise', function () {
        $promise = $this->handler->resolve('test value');

        expect($promise)->toBeInstanceOf(PromiseInterface::class);
        expect($promise->isResolved())->toBe(true);
        expect($promise->await())->toBe('test value');
    });

    it('creates rejected promise', function () {
        $promise = $this->handler->reject('test error');

        expect($promise)->toBePromise();
        expect($promise->isRejected())->toBe(true);

        expect(fn() => $promise->await())
            ->toThrow(Exception::class);
    });

    it('creates empty promise', function () {
        $promise = $this->handler->createEmpty();

        expect($promise)->toBeInstanceOf(PromiseInterface::class);
        expect($promise->isPending())->toBe(true);
    });

    it('resolves with different data types', function () {
        $testCases = [
            'string' => 'hello',
            'integer' => 42,
            'array' => ['key' => 'value'],
            'object' => (object)['prop' => 'value'],
            'null' => null,
            'boolean' => true,
        ];

        foreach (array_values($testCases) as $value) {
            $promise = $this->handler->resolve($value);
            expect($promise->await())->toBe($value);
        }
    });
});
