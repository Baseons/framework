<?php

namespace Baseons\Collections;

class Middleware
{
    public function abort(int $status = 403, string|null $message = null)
    {
        return response()->abort($status, $message);
    }

    public function redirect(string|null $destination = null, $status = 302)
    {
        return redirect($destination, $status);
    }

    public function response()
    {
        return response();
    }

    public function request()
    {
        return request();
    }
}
