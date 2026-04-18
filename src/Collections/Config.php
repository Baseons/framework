<?php

namespace Baseons\Collections;

class Config
{
    private static array $configs = [];

    // public static function __callStatic($name, $arguments)
    // {
    //     self::get($name,...$arguments);
    // }

    public static function set(string $name, int|string|array|null $keys, mixed $value)
    {
        if (!array_key_exists($name, self::$configs) && self::get($name) === null) self::$configs[$name] = [];

        arr()->set(self::$configs[$name], $keys, $value);
    }

    public static function app(string|array|null $key = null, mixed $default = null)
    {
        return self::get('app', $key, $default);
    }

    public static function database(string|array|null $key = null, mixed $default = null)
    {
        return self::get('database', $key, $default);
    }

    public static function mail(string|array|null $key = null, mixed $default = null)
    {
        return self::get('mail', $key, $default);
    }

    public static function route(string|array|null $key = null, mixed $default = null)
    {
        return self::get('route', $key, $default);
    }

    public static function session(string|array|null $key = null, mixed $default = null)
    {
        return self::get('session', $key, $default);
    }

    public static function ssh(string|array|null $key = null, mixed $default = null)
    {
        return self::get('ssh', $key, $default);
    }

    /**
     * @return mixed
     */
    public static function get(string $name, int|string|array|null $keys = null, mixed $default = null)
    {
        if (!array_key_exists($name, self::$configs)) {
            if (!file_exists(path("config/$name.php"))) return $default;
            self::$configs[$name] = require path("config/$name.php");
        }

        if ($keys === null) return self::$configs[$name];

        return arr()->get(self::$configs[$name], $keys, $default);
    }
}
