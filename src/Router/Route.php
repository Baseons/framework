<?php

namespace Baseons\Router;

use Baseons\Kernel;

class Route
{
    private static string|null $config = null;
    private static int $key = 0;
    private static array $group = [];
    private static array $attributes = [
        'name' => [],
        'middleware' => [],
        'prefix' => [],
        'domain' => [],
        'namespace' => [],
        'class' => []
    ];

    /**
     * Set route configuration
     */
    public static function config(string $config)
    {
        self::$config = $config;
    }

    // /**
    //  * @return \Baseons\Router\RouteOptions
    //  */
    // public static function single(string|array|callable $action)
    // {
    //     self::add('', $action, ['GET'], 'single');

    //     return new RouteOptions;
    // }

    /**
     * @return \Baseons\Router\RouteOptions
     */
    public static function get(string $route, string|array|callable $action)
    {
        self::add($route, $action, ['GET'], 'get');
        return new RouteOptions;
    }

    /**
     * @return \Baseons\Router\RouteOptions
     */
    public static function post(string $route, string|array|callable $action)
    {
        self::add($route, $action, ['POST'], 'post');
        return new RouteOptions;
    }

    /**
     * @return \Baseons\Router\RouteOptions
     */
    public static function delete(string $route, string|array|callable $action)
    {
        self::add($route, $action, ['DELETE'], 'delete');
        return new RouteOptions;
    }

    /**
     * @return \Baseons\Router\RouteOptions
     */
    public static function put(string $route, string|array|callable $action)
    {
        self::add($route, $action, ['PUT'], 'put');
        return new RouteOptions;
    }

    /**
     * @return \Baseons\Router\RouteOptions
     */
    public static function patch(string $route, string|array|callable $action)
    {
        self::add($route, $action, ['PATCH'], 'patch');
        return new RouteOptions;
    }

    /**
     * @return \Baseons\Router\RouteOptions
     */
    public static function copy(string $route, string|array|callable $action)
    {
        self::add($route, $action, ['COPY'], 'copy');
        return new RouteOptions;
    }

    /**
     * @return \Baseons\Router\RouteOptions
     */
    public static function options(string $route, string|array|callable $action)
    {
        self::add($route, $action, ['OPTIONS'], 'options');
        return new RouteOptions;
    }

    /**
     * @return \Baseons\Router\RouteOptions
     */
    public static function lock(string $route, string|array|callable $action)
    {
        self::add($route, $action, ['LOCK'], 'lock');
        return new RouteOptions;
    }

    /**
     * @return \Baseons\Router\RouteOptions
     */
    public static function unlock(string $route, string|array|callable $action)
    {
        self::add($route, $action, ['UNLOCK'], 'unlock');
        return new RouteOptions;
    }

    /**
     * @return \Baseons\Router\RouteOptions
     */
    public static function match(string|array $methods, string $route, string|array|callable $action)
    {
        if (is_string($methods)) $methods = [$methods];

        $methods = array_map(function ($method) {
            return strtoupper($method);
        }, $methods);

        self::add($route, $action, $methods, 'match');
        return new RouteOptions;
    }

    /**
     * Redirecionar para uma url
     */
    public static function redirect(string $route, string $destination, int $status = 302)
    {
        self::add($route, ['destination' => $destination, 'status' => $status], ['GET'], 'redirect');
        return new RouteOptions;
    }

    /**
     * Return file
     *
     * @param callable(string $content, array $params)|null $callback when using the callback and modifying the file, the cache is automatically updated
     * @param bool $cache Cache callback result
     * @return \Baseons\Router\RouteOptions
     */
    public static function file(string $route, string $path, callable|null $callback = null, bool $cache = true)
    {
        self::add($route, [$path, $callback, $cache], ['GET'], 'file');
        return new RouteOptions;
    }

    /**
     * Renderizar um view diretamente
     * @return \Baseons\Router\RouteOptions
     */
    public static function view(string $route, string $view, array $params = [])
    {
        self::add($route, [$view, $params], ['GET'], 'view');
        return new RouteOptions;
    }

    public static function name(string $value)
    {
        self::setAttribute('name', $value);
        self::$group[self::$key][] = 'name';

        return new self;
    }

    public static function middleware(string|array $value)
    {
        self::setAttribute('middleware', $value);
        self::$group[self::$key][] = 'middleware';

        return new self;
    }

    public static function prefix(string $value)
    {
        self::setAttribute('prefix', $value);
        self::$group[self::$key][] = 'prefix';

        return new self;
    }

    public static function domain(string|array $value)
    {
        self::setAttribute('domain', $value);
        self::$group[self::$key][] = 'domain';

        return new self;
    }

    public static function namespace(string $value)
    {
        self::setAttribute('namespace', trim($value, '\\'));
        self::$group[self::$key][] = 'namespace';

        return new self;
    }

    public static function class(string $value)
    {
        self::setAttribute('class', $value);
        self::$group[self::$key][] = 'class';

        return new self;
    }

    public static function group(callable $routes)
    {
        $key = self::$key;

        self::$key++;

        if (is_callable($routes)) call_user_func($routes, $key);

        foreach (self::$group[$key] as $name) self::removeAttribute($name);

        unset(self::$group[$key]);
    }

    private static function add(string $route, mixed $action, array $methods, string $type)
    {
        $route = trim($route, '/');
        $name = null;
        $prefix = null;
        $namespace = null;
        $domain = [];

        if (count(self::$attributes['namespace'])) $namespace = implode('\\', self::$attributes['namespace']);
        if (count(self::$attributes['name'])) $name = implode('.', self::$attributes['name']);
        if (count(self::$attributes['class']) and is_string($action)) {
            $class = end(self::$attributes['class']);

            if (class_exists($class)) $namespace = null;

            $action = $class . '@' . $action;
        }

        if (count(self::$attributes['prefix'])) {
            $prefix = implode('/', self::$attributes['prefix']);
            $route = $prefix . '/' . $route;
        }

        if (count(self::$attributes['domain'])) {
            foreach (self::$attributes['domain'] as $value) {
                if (is_string($value)) {
                    $domain[] = $value;
                } elseif (is_array($value)) {
                    foreach ($value as $e) {
                        if (!empty($e) && is_string($e)) $domain[] = $e;
                    }
                }
            }
        }

        $routes = Kernel::getMemory('route.routes', []);

        $routes[] = [
            'config' => self::$config,
            'route' => $route,
            'action' => $action,
            'methods' => $methods,
            'type' => $type,
            'name' => $name,
            'middleware' => self::$attributes['middleware'],
            'domain' => $domain,
            'namespace' => $namespace
        ];

        Kernel::setMemory('route.routes', $routes);
    }

    private static function setAttribute(string $name, mixed $value)
    {
        self::$attributes[$name][] = $value;
    }

    private static function removeAttribute(string $name)
    {
        if (!count(self::$attributes[$name])) return;

        $key = array_key_last(self::$attributes[$name]);

        if ($key !== null) unset(self::$attributes[$name][$key]);
    }

    /**
     * Encerrar leitura do router
     */
    public static function finish()
    {
        self::$config = null;
        self::$key = 0;
        self::$group = [];
        self::$attributes = [
            'name' => [],
            'middleware' => [],
            'prefix' => [],
            'domain' => [],
            'namespace' => [],
            'class' => []
        ];
    }
}
