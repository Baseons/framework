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

        if (!empty($session['lifetime']) and $session['lifetime'] > date('dmYHis')) return $session['token'];

        $token = md5(bin2hex(random_bytes(10)));
        $lifetime = Kernel::getMemory('route.config.csrf.lifetime', 1800);

        request()->session()->set('framework.csrf', [
            'token' => $token,
            'lifetime' => date('dmYHis', strtotime('+' . $lifetime . ' seconds'))
        ]);

        return $token;
    }

    public static function check()
    {
        $token = self::token();
        $header = request()->header('X-CSRF-TOKEN') ?? request()->header('X-Csrf-Token');
        $input = request()->post('_token');

        if ($token == $header or $token == $input) return true;

        return false;
    }
}
