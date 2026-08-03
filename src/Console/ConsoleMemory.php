<?php

namespace Baseons\Console;

class ConsoleMemory
{
    public static array $commands = [];

    private static array $attributes = [
        'namespace' => [],
        'title' => [],
        'prefix' => []
    ];

    public static function command(string $command, mixed $action, bool $list)
    {
        $namespace = null;
        $title = null;

        if (count(self::$attributes['namespace'])) $namespace = implode('\\', self::$attributes['namespace']);
        if (count(self::$attributes['title'])) $title = implode(' ', self::$attributes['title']);
        if (count(self::$attributes['prefix'])) $command = implode('', self::$attributes['prefix']) . $command;

        self::$commands[] = [
            'command' => $command,
            'action' => $action,
            'namespace' => $namespace,
            'description' => null,
            'title' => $title,
            'list' => $list
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
