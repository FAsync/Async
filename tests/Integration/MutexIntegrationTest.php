<?php

use Hibla\Async\Mutex;
use function Hibla\async;
use function Hibla\await;
use function Hibla\delay;
use function Hibla\Promise\all;
use function Hibla\Promise\concurrent;
use function Hibla\Promise\batch;

beforeEach(function () {
    $this->mutex = new Mutex();
    $this->sharedCounter = 0;
    $this->sharedLog = [];
});

describe('Basic Mutex Functionality', function () {
    it('starts in correct initial state and handles acquire/release', function () {
        // Test initial state
        expect($this->mutex->isLocked())->toBeFalse();
        expect($this->mutex->getQueueLength())->toBe(0);
        expect($this->mutex->isQueueEmpty())->toBeTrue();

        // Test acquire and release
        $lockPromise = $this->mutex->acquire();
        expect($this->mutex->isLocked())->toBeTrue();

        $acquiredMutex = await($lockPromise);
        expect($acquiredMutex)->toBe($this->mutex);

        $acquiredMutex->release();
        expect($this->mutex->isLocked())->toBeFalse();
    });
});

describe('Concurrent Access Protection', function () {
    it('protects shared resources from race conditions', function () {
        $tasks = [];
        $expectedResults = [];

        for ($i = 1; $i <= 5; $i++) {
            $expectedResults[] = "Task-$i completed";
            $tasks[] = async(function() use ($i) {
                $lock = await($this->mutex->acquire());

                $oldValue = $this->sharedCounter;
                await(delay(0.01));
                $this->sharedCounter++;
                $this->sharedLog[] = "Task-$i: $oldValue -> {$this->sharedCounter}";

                $lock->release();
                return "Task-$i completed";
            });
        }

        // Wait for all tasks
        $results = [];
        foreach ($tasks as $task) {
            $results[] = await($task);
        }

        expect($this->sharedCounter)->toBe(5);
        expect($this->sharedLog)->toHaveCount(5);
        expect($results)->toBe($expectedResults);

        // Verify sequential execution
        for ($i = 0; $i < 5; $i++) {
            expect($this->sharedLog[$i])->toContain("$i -> " . ($i + 1));
        }
    });
});

describe('Promise::all() Integration', function () {
    it('integrates correctly with Promise::all()', function () {
        $tasks = [];
        
        for ($i = 1; $i <= 4; $i++) {
            $tasks[] = async(function() use ($i) {
                $lock = await($this->mutex->acquire());
                
                $oldValue = $this->sharedCounter;
                await(delay(0.02));
                $this->sharedCounter++;
                $this->sharedLog[] = "AllTask-$i: $oldValue -> {$this->sharedCounter}";
                
                $lock->release();
                return "AllTask-$i result: {$this->sharedCounter}";
            });
        }

        $results = await(all($tasks));

        expect($this->sharedCounter)->toBe(4);
        expect($results)->toHaveCount(4);
        expect($this->sharedLog)->toHaveCount(4);

        foreach ($results as $i => $result) {
            expect($result)->toContain("AllTask-" . ($i + 1));
        }
    });
});

describe('Promise::concurrent() Integration', function () {
    it('works with concurrent promise execution while limiting concurrency', function () {
        $tasks = [];
        
        for ($i = 1; $i <= 6; $i++) {
            $tasks[] = function() use ($i) {
                $lock = await($this->mutex->acquire());
                
                $oldValue = $this->sharedCounter;
                await(delay(0.01));
                $this->sharedCounter++;
                $this->sharedLog[] = "ConcTask-$i: $oldValue -> {$this->sharedCounter}";
                
                $lock->release();
                return "ConcTask-$i completed";
            };
        }

        $results = await(concurrent($tasks, 3)); // Limit to 3 concurrent

        expect($this->sharedCounter)->toBe(6);
        expect($results)->toHaveCount(6);
        expect($this->sharedLog)->toHaveCount(6);

        // Verify sequential access despite concurrency limit
        for ($i = 0; $i < 6; $i++) {
            expect($this->sharedLog[$i])->toContain("$i -> " . ($i + 1));
        }
    });
});

describe('Promise::batch() Integration', function () {
    it('processes batches correctly with mutex protection', function () {
        $tasks = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $tasks[] = function() use ($i) {
                $lock = await($this->mutex->acquire());
                
                $oldValue = $this->sharedCounter;
                await(delay(0.01));
                $this->sharedCounter++;
                $this->sharedLog[] = "BatchTask-$i: $oldValue -> {$this->sharedCounter}";
                
                $lock->release();
                return "BatchTask-$i done";
            };
        }

        $results = await(batch($tasks, 2, 2)); // Batches of 2, max 2 concurrent

        expect($this->sharedCounter)->toBe(5);
        expect($results)->toHaveCount(5);
        expect($this->sharedLog)->toHaveCount(5);
    });
});

describe('Multiple Mutexes', function () {
    it('allows independent protection of different shared resources', function () {
        $resource1 = 0;
        $resource2 = 0;
        $mutex1 = new Mutex();
        $mutex2 = new Mutex();

        $tasks = [];
        for ($i = 1; $i <= 3; $i++) {
            $tasks[] = async(function() use ($i, $mutex1, $mutex2, &$resource1, &$resource2) {
                // Access resource1
                $lock1 = await($mutex1->acquire());
                $resource1 += $i;
                $lock1->release();

                await(delay(0.01));

                // Access resource2
                $lock2 = await($mutex2->acquire());
                $resource2 += $i * 2;
                $lock2->release();

                return "MultiTask $i completed";
            });
        }

        $results = await(all($tasks));

        expect($resource1)->toBe(6); // 1+2+3
        expect($resource2)->toBe(12); // 2+4+6
        expect($results)->toHaveCount(3);
    });
});

describe('Mutex Queueing', function () {
    it('properly queues and processes waiting acquire requests', function () {
        // First acquire - should succeed immediately
        $firstLock = await($this->mutex->acquire());
        expect($this->mutex->isLocked())->toBeTrue();
        expect($this->mutex->getQueueLength())->toBe(0);

        // Second and third acquire - should be queued
        $secondPromise = $this->mutex->acquire();
        $thirdPromise = $this->mutex->acquire();
        expect($this->mutex->getQueueLength())->toBe(2);

        // Release first lock - should pass to second waiter
        $firstLock->release();
        expect($this->mutex->isLocked())->toBeTrue(); // Still locked by second
        expect($this->mutex->getQueueLength())->toBe(1);

        // Get second lock and release
        $secondLock = await($secondPromise);
        expect($secondLock)->toBe($this->mutex);
        $secondLock->release();
        expect($this->mutex->getQueueLength())->toBe(0);

        // Get third lock and release
        $thirdLock = await($thirdPromise);
        $thirdLock->release();
        expect($this->mutex->isLocked())->toBeFalse();
        expect($this->mutex->isQueueEmpty())->toBeTrue();
    });
});