<?php

namespace Baseons\Http;

use Baseons\Kernel;

class Redirect
{
    public static function destination(string $destination, int $status = 302)
    {
        header('Location: ' . $destination, true, $status);

        return Kernel::terminate();
    }

    public static function route(string $name, int $status = 302)
    {
        header('Location: ' . route($name), true, $status);

        return Kernel::terminate();
    }

    /**
     * Set a temporary value for the next request
     */
    public static function with(mixed $value = null)
    {
        request()->session()->with($value);

        return new self;
    }

    public function back(int $status = 302)
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER'], true, $status);

            return Kernel::terminate();
        } else {
            return response()->abort($status);
        }
    }
}
