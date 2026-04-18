<?php

namespace Baseons\Server;

use Baseons\Server\Http\HttpOptions;
use Baseons\Server\WebSocket\WebSocketOptions;

class Server
{
    private static int $group = 0;
    private static array $attributes = [];

    /**
     * Create websocket server swoole
     */
    public static function ws(int $port, string $class)
    {
        $class = trim($class, '\\');

        ServerMemory::server('websocket', [
            'protocol' => 'ws',
            'host' => config()->route('server.host', 'localhost'),
            'alias' => config()->route('server.alias', null) ?? config()->route('server.host', 'localhost'),
            'port' => $port,
            'workers' => 1,
            'namespace' => null,
            'class' => $class,
            'name' => null,
            'receive' => true,
            'connections' => null,
            'path' => ServerMemory::prefix(),
            'ssl' => [
                'active' => config()->route('server.ssl.active', false),
                'crt' => config()->route('server.ssl.crt'),
                'key' => config()->route('server.ssl.key')
            ]
        ]);

        return new WebSocketOptions;
    }

    /**
     * Create http server swoole
     */
    public static function http(int $port, string $class)
    {
        $class = trim($class, '\\');

        ServerMemory::server('http', [
            'protocol' => 'http',
            'host' => config()->route('server.host', 'localhost'),
            'alias' => config()->route('server.alias', null) ?? config()->route('server.host', 'localhost'),
            'port' => $port,
            'workers' => 5,
            'namespace' => null,
            'class' => $class,
            'name' => null,
            'path' => ServerMemory::prefix(),
            'ssl' => [
                'active' => config()->route('server.ssl.active', false),
                'crt' => config()->route('server.ssl.crt'),
                'key' => config()->route('server.ssl.key')
            ]
        ]);

        return new HttpOptions;
    }

    public static function ssl(bool $ative, string|null $crt = null, string|null $key = null)
    {
        ServerMemory::setAttribute('ssl', [
            'active' => $ative,
            'crt' => $crt  !== null ? $crt : config()->route('server.ssl.crt'),
            'key' => $key !== null ? $key : config()->route('server.ssl.key')
        ]);

        self::$attributes[self::$group][] = 'ssl';

        return new self;
    }

    public static function workers(int $value)
    {
        ServerMemory::setAttribute('workers', $value);
        self::$attributes[self::$group][] = 'workers';

        return new self;
    }

    public static function namespace(string $value)
    {
        ServerMemory::setAttribute('namespace', trim($value, '\\'));
        self::$attributes[self::$group][] = 'namespace';

        return new self;
    }

    public static function name(string $value)
    {
        ServerMemory::setAttribute('name', $value);
        self::$attributes[self::$group][] = 'name';

        return new self;
    }

    public static function host(string $value)
    {
        ServerMemory::setAttribute('host', $value);
        self::$attributes[self::$group][] = 'host';

        return new self;
    }

    public static function alias(string $value)
    {
        ServerMemory::setAttribute('alias', $value);
        self::$attributes[self::$group][] = 'alias';

        return new self;
    }

    public static function prefix(string $value)
    {
        ServerMemory::setAttribute('prefix', $value);
        self::$attributes[self::$group][] = 'prefix';

        return new self;
    }

    public static function group(callable $routes)
    {
        $group = self::$group;

        self::$group++;

        if (is_callable($routes)) call_user_func($routes, $group);

        foreach (self::$attributes[$group] as $name) ServerMemory::removeAttribute($name);

        unset(self::$attributes[$group]);
    }
}
