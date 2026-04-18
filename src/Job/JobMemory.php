<?php

namespace Baseons\Job;

class JobMemory
{
    public static array $jobs = [];

    private static array $attributes = [
        'name' => [],
        'namespace' => [],
        'timeout' => [],
        'unique' => []
    ];

    public static function job(mixed $action, bool $valid)
    {
        $name = null;
        $namespace = null;
        $timeout = false;
        $unique = true;

        if (count(self::$attributes['name'])) $name = implode('.', self::$attributes['name']);
        if (count(self::$attributes['namespace'])) $namespace = implode('\\', self::$attributes['namespace']);
        if (count(self::$attributes['timeout'])) $timeout = end(self::$attributes['timeout']);
        if (count(self::$attributes['unique'])) $unique = end(self::$attributes['unique']);

        self::$jobs[] = [
            'action' => $action,
            'valid' => $valid,
            'name' => $name,
            'namespace' => $namespace,
            'description' => null,
            'timeout' => $timeout,
            'unique' => $unique
        ];
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
}
