<?php

namespace Baseons\Http;

use Baseons\Kernel;

class Csrf
{
    public static function unset()
    {
        request()->session()->unset('framework.csrf');
    }

    public static function token()
    {
        $session = request()->session('framework.csrf');

        if (!empty($session['token']) && !empty($session['expires_at']) && time() < $session['expires_at']) return $session['token'];

        $token = bin2hex(random_bytes(32));
        $lifetime = (int)Kernel::getMemory('route.config.csrf.lifetime', 1800);
        $expiresAt = time() + max($lifetime, 1);

        request()->session()->set('framework.csrf', [
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    public static function check()
    {
        $session = request()->session('framework.csrf');

        if (empty($session['token']) || empty($session['expires_at']) || time() >= $session['expires_at']) return false;

        $header = request()->header('x-csrf-token');
        $input = request()->post('_token');
        $value = $header ?: $input;

        if (empty($value)) return false;

        return hash_equals($session['token'], $value);
    }
}
