<?php

use Hibla\Async\Mutex;
use function Hibla\async;
use function Hibla\await;
use function Hibla\delay;
use function Hibla\Promise\all;
use function Hibla\Promise\race;
use function Hibla\Promise\concurrent;
use function Hibla\Promise\allSettled;
use function Hibla\Promise\batch;
use function Hibla\Promise\timeout;

beforeEach(function () {
    $this->sharedCounter = 0;
    $this->sharedLog = [];
    $this->mutex = new Mutex();
    
    $this->protectedWork = function(string $taskName) {
        return function() use ($taskName) {
            $lock = await($this->mutex->acquire());
            
            $oldValue = $this->sharedCounter;
            await(delay(0.02));
            $this->sharedCounter++;
            $this->sharedLog[] = "$taskName: $oldValue -> {$this->sharedCounter}";
            
            $lock->release();
            return "$taskName completed (result: {$this->sharedCounter})";
        };
    };
});

describe('Promise::all() with Mutex', function () {
    it('protects shared resources across all promises', function () {
        $tasks = [];
        for ($i = 1; $i <= 5; $i++) {
            $tasks[] = async(($this->protectedWork)("Task-$i"));
        }

        $results = await(all($tasks));

        expect($this->sharedCounter)->toBe(5);
        expect(count($results))->toBe(5);
        expect(count($this->sharedLog))->toBe(5);

        // Verify sequential execution
        for ($i = 0; $i < 5; $i++) {
            expect($this->sharedLog[$i])->toContain("$i -> " . ($i + 1));
        }
    });
});

describe('Promise::race() with Mutex', function () {
    it('protects shared resources even in race conditions', function () {
        $raceTasks = [
            async(function() {
                await(delay(0.1));
                return await(async(($this->protectedWork)("Slow-Task")));
            }),
            async(function() {
                await(delay(0.05));
                return await(async(($this->protectedWork)("Fast-Task")));
            }),
            async(function() {
                await(delay(0.07));
                return await(async(($this->protectedWork)("Medium-Task")));
            }),
        ];

        $winner = await(race($raceTasks));

        expect($winner)->toContain('Fast-Task completed');
        // All tasks should still complete due to how the mutex works
        expect($this->sharedCounter)->toBeGreaterThan(0);
        expect(count($this->sharedLog))->toBeGreaterThan(0);
    });
});

describe('Promise::concurrent() with Mutex', function () {
    it('limits concurrency while protecting shared resources', function () {
        $tasks = [];
        for ($i = 1; $i <= 8; $i++) {
            $tasks[] = ($this->protectedWork)("Concurrent-$i");
        }

        $results = await(concurrent($tasks, 3));

        expect($this->sharedCounter)->toBe(8);
        expect(count($results))->toBe(8);
        expect(count($this->sharedLog))->toBe(8);

        // Verify sequential access despite concurrency
        for ($i = 0; $i < 8; $i++) {
            expect($this->sharedLog[$i])->toContain("$i -> " . ($i + 1));
        }
    });
});

describe('Promise::batch() with Mutex', function () {
    it('processes batches while protecting shared resources', function () {
        $tasks = [];
        for ($i = 1; $i <= 6; $i++) {
            $tasks[] = ($this->protectedWork)("Batch-$i");
        }

        $results = await(batch($tasks, 3, 2));

        expect($this->sharedCounter)->toBe(6);
        expect(count($results))->toBe(6);
        expect(count($this->sharedLog))->toBe(6);
    });
});

describe('Promise::allSettled() with Mutex', function () {
    it('handles mixed success/failure with mutex protection', function () {
        $tasks = [
            async(($this->protectedWork)("Success-1")),
            async(function() {
                $lock = await($this->mutex->acquire());
                $this->sharedCounter++;
                $this->sharedLog[] = "Failure task: counter = {$this->sharedCounter}";
                $lock->release();
                throw new Exception("Intentional failure");
            }),
            async(($this->protectedWork)("Success-2")),
        ];

        $results = await(allSettled($tasks));

        expect(count($results))->toBe(3);
        expect($results[0]['status'])->toBe('fulfilled');
        expect($results[1]['status'])->toBe('rejected');
        expect($results[2]['status'])->toBe('fulfilled');
        expect($this->sharedCounter)->toBe(3);
    });
});

describe('Multiple Mutexes', function () {
    it('allows independent protection of different resources', function () {
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

                return "Task $i completed";
            });
        }

        await(all($tasks));

        expect($resource1)->toBe(6); // 1+2+3
        expect($resource2)->toBe(12); // 2+4+6
    });
});

describe('Timeout with Mutex', function () {
    it('handles timeouts properly with mutex operations', function () {
        $timeoutCounter = 0;

        expect(function() use (&$timeoutCounter) {
            $timeoutTask = async(function() use (&$timeoutCounter) {
                $lock = await($this->mutex->acquire());
                await(delay(1.0)); // Long operation
                $timeoutCounter++;
                $lock->release();
                return "Should not complete";
            });

            await(timeout($timeoutTask, 0.1)); // 100ms timeout
        })->toThrow(Exception::class);

        // The counter might be incremented depending on timing
        expect($timeoutCounter)->toBeLessThanOrEqual(1);
    });
});