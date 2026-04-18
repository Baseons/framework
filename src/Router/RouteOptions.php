<?php

namespace Baseons\Router;

use Baseons\Kernel;

class RouteOptions
{
    public function name(string $value)
    {
        $routes = Kernel::getMemory('route.routes');
        $key = array_key_last($routes);
        $routes[$key]['name'] = empty($routes[$key]['name']) ? $value : $routes[$key]['name'] .= '.' . $value;

        Kernel::setMemory('route.routes', $routes);

        return $this;
    }

    public function middleware(string|array $value)
    {
        $routes = Kernel::getMemory('route.routes');
        $key = array_key_last($routes);

        $routes[$key]['middleware'][] = $value;

        Kernel::setMemory('route.routes', $routes);

        return $this;
    }

    public function domain(string $value)
    {
        $routes = Kernel::getMemory('route.routes');
        $key = array_key_last($routes);

        $routes[$key]['domain'][] = $value;

        Kernel::setMemory('route.routes', $routes);

        return $this;
    }
}
