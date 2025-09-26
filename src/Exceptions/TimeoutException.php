<?php 

namespace Hibla\Async\Exceptions;

class TimeoutException extends \Exception
{
    public function __construct(float $timeout)
    {
        parent::__construct("Operation timed out after {$timeout} seconds");
    }
}
