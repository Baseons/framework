<?php

namespace Baseons\Exceptions;

use Baseons\Collections\Log;
use ErrorException;
use Throwable;

class Exceptions
{
    public function handler(string|array|callable $debug, array $args = [], string|null $namespace = null)
    {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            // if (in_array($errno, [E_WARNING])) return false;
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        });

        try {
            return executeCallable($debug, $args, $namespace);
        } catch (Throwable $error) {
            while ($error->getPrevious()) $error = $error->getPrevious();

            Log::create('framework', $error);

            return (new Debug)->exceptions($error);
        }
    }
}
