<?php

namespace Baseons\Server;

class ServerMemory
{
    public static array $servers = [
        'websocket' => [],
        'htttp' => []
    ];

    protected static array $attributes = [
        'namespace' => [],
        'name' => [],
        'host' => [],
        'alias' => [],
        'ssl' => [],
        'prefix' => [],
        'workers' => []
    ];

    public static function server(string $type, array $params)
    {
        if (count(self::$attributes['namespace'])) $params['namespace'] = implode('\\', self::$attributes['namespace']);
        if (count(self::$attributes['name'])) $params['name'] = implode('.', self::$attributes['name']);
        if (count(self::$attributes['host'])) $params['host'] = end(self::$attributes['host']);
        if (count(self::$attributes['alias'])) $params['alias'] = end(self::$attributes['alias']);
        if (count(self::$attributes['ssl'])) $params['ssl'] = end(self::$attributes['ssl']);
        if (count(self::$attributes['workers'])) $params['workers'] = end(self::$attributes['workers']);

        self::$servers[$type][] = $params;
    }

    public static function prefix()
    {
        if (count(self::$attributes['prefix'])) {
            $prefix =  array_map(function ($value) {
                return trim($value, '/');
            }, self::$attributes['prefix']);

            return implode('/', $prefix);
        }

        return null;
    }

    public static function setAttribute(string $name, mixed $value)
    {
        self::$attributes[$name][] = $value;
    }

    public static function removeAttribute(string $name)
    {
        if (!count(self::$attributes[$name])) return;

        $key = array_key_last(self::$attributes[$name]);

        if ($key !== null) unset(self::$attributes[$name][$key]);
    }

    public static function reset()
    {
        self::$servers = [
            'websocket' => [],
            'htttp' => []
        ];

        self::$attributes = [
            'namespace' => [],
            'name' => [],
            'host' => [],
            'ssl' => [],
            'prefix' => [],
            'workers' => []
        ];
    }
}
