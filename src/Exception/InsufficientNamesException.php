<?php

namespace D4rk0snet\CoralOrder\Exception;

use Throwable;

class InsufficientNamesException extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}