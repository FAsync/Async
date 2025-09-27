<?php

use Hibla\Async\Mutex;
use function Hibla\async;
use function Hibla\await;
use function Hibla\delay;

beforeEach(function () {
    $this->mutex = new Mutex();
    $this->sharedCounter = 0;
    $this->sharedLog = [];
});

describe('Basic Mutex Operations', function () {
    it('starts in unlocked state', function () {
        expect($this->mutex->isLocked())->toBeFalse();
        expect($this->mutex->getQueueLength())->toBe(0);
        expect($this->mutex->isQueueEmpty())->toBeTrue();
    });

    it('can acquire and release lock', function () {
        $lockPromise = $this->mutex->acquire();
        expect($this->mutex->isLocked())->toBeTrue();

        $acquiredMutex = await($lockPromise);
        expect($acquiredMutex)->toBe($this->mutex);

        $acquiredMutex->release();
        expect($this->mutex->isLocked())->toBeFalse();
    });

    it('queues multiple acquire attempts', function () {
        // First acquire - should succeed immediately
        $firstLock = await($this->mutex->acquire());
        expect($this->mutex->isLocked())->toBeTrue();

        // Second acquire - should be queued
        $secondLockPromise = $this->mutex->acquire();
        expect($this->mutex->getQueueLength())->toBe(1);

        // Third acquire - should also be queued
        $thirdLockPromise = $this->mutex->acquire();
        expect($this->mutex->getQueueLength())->toBe(2);

        // Release first lock
        $firstLock->release();
        expect($this->mutex->isLocked())->toBeTrue(); // Still locked by second waiter
        expect($this->mutex->getQueueLength())->toBe(1);

        // Second lock should now be available
        $secondLock = await($secondLockPromise);
        $secondLock->release();
        expect($this->mutex->getQueueLength())->toBe(0);

        // Third lock should now be available
        $thirdLock = await($thirdLockPromise);
        $thirdLock->release();
        expect($this->mutex->isLocked())->toBeFalse();
    });
});

describe('Concurrent Access Protection', function () {
    it('protects shared resource from race conditions', function () {
        $tasks = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $tasks[] = async(function() use ($i) {
                $lock = await($this->mutex->acquire());
                
                $oldValue = $this->sharedCounter;
                await(delay(0.01)); // Small delay to simulate work
                $this->sharedCounter++;
                $this->sharedLog[] = "Task-$i: $oldValue -> {$this->sharedCounter}";
                
                $lock->release();
                return "Task-$i completed";
            });
        }

        // Wait for all tasks
        foreach ($tasks as $task) {
            await($task);
        }

        expect($this->sharedCounter)->toBe(5);
        expect(count($this->sharedLog))->toBe(5);
        
        // Verify sequential execution (no overlapping increments)
        foreach ($this->sharedLog as $index => $entry) {
            expect($entry)->toContain("$index -> " . ($index + 1));
        }
    });

    it('handles quick succession acquire/release', function () {
        for ($i = 1; $i <= 10; $i++) {
            $lock = await($this->mutex->acquire());
            expect($this->mutex->isLocked())->toBeTrue();
            $lock->release();
        }

        expect($this->mutex->isLocked())->toBeFalse();
        expect($this->mutex->isQueueEmpty())->toBeTrue();
    });
});

describe('Mutex State Inspection', function () {
    it('correctly reports lock state', function () {
        expect($this->mutex->isLocked())->toBeFalse();
        
        $lock = await($this->mutex->acquire());
        expect($this->mutex->isLocked())->toBeTrue();
        
        $lock->release();
        expect($this->mutex->isLocked())->toBeFalse();
    });

    it('correctly reports queue length', function () {
        $firstLock = await($this->mutex->acquire());
        expect($this->mutex->getQueueLength())->toBe(0);

        $secondPromise = $this->mutex->acquire();
        expect($this->mutex->getQueueLength())->toBe(1);

        $thirdPromise = $this->mutex->acquire();
        expect($this->mutex->getQueueLength())->toBe(2);

        $firstLock->release();
        expect($this->mutex->getQueueLength())->toBe(1);

        $secondLock = await($secondPromise);
        $secondLock->release();
        expect($this->mutex->getQueueLength())->toBe(0);

        $thirdLock = await($thirdPromise);
        $thirdLock->release();
        expect($this->mutex->isQueueEmpty())->toBeTrue();
    });
});