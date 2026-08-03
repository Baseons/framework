<?php

namespace Baseons\Console;

class Console
{
    private static int $group = 0;
    private static array $attributes = [];

    public static function command(string $command, string|array|callable|null $action = null, bool $list = true)
    {
        ConsoleMemory::command($command, $action, $list);

        return new ConsoleOptions;
    }

    public static function namespace(string $value)
    {
        ConsoleMemory::setAttribute('namespace', trim($value, '\\'));
        self::$attributes[self::$group][] = 'namespace';

        return new self;
    }

    public static function prefix(string $value)
    {
        ConsoleMemory::setAttribute('prefix', trim($value));
        self::$attributes[self::$group][] = 'prefix';

        return new self;
    }

    public static function title(string $value)
    {
        ConsoleMemory::setAttribute('title', trim($value, '\\'));
        self::$attributes[self::$group][] = 'title';

        return new self;
    }

    public static function group(callable $routes)
    {
        $group = self::$group;

        self::$group++;

        if (is_callable($routes)) call_user_func($routes, $group);

        foreach (self::$attributes[$group] as $name) {
            ConsoleMemory::removeAttribute($name);
        }

        unset(self::$attributes[$group]);
    }

    public static function end()
    {
        BaseonsCommands::run();

        return (new ConsoleResolve)->run(ConsoleMemory::$commands);
    }
}
