<?php

namespace Baseons\Http;

use Baseons\Kernel;

class Route
{
    /**
     * @return string|null
     */
    public static function name(string $name, string|array|null ...$params)
    {
        if (!empty($params[0])) if (is_array($params[0])) $params = $params[0];

        $names = Kernel::getMemory('route.names',[]);

        if (array_key_exists($name, $names)) {
            $route = $names[$name];

            if (count($params) and preg_match_all('/\{\?\}/', $route, $matches)) {
                if (!empty($matches[0])) foreach ($matches[0] as $key => $value) {
                    if ($position = strpos($route, $value)) $route = substr_replace($route, !empty($params[$key]) ? $params[$key] : '', $position, strlen($value));
                }
            };

            $route = str_replace(['/{?}', '{?}'], '', $route);

            return request()->url($route);
        }

        return null;
    }

    /**
     * @return string|array|null
     */
    public static function params(string|null $param = null)
    {
        $params = Kernel::getMemory('route.params', []);

        if ($param == null) return $params;

        if (array_key_exists($param, $params)) return $params[$param];

        return null;
    }

    /**
     * @return array|null
     */
    public static function current()
    {
        $current = Kernel::getMemory('route.current');

        if (is_array($current) and array_key_exists('action', $current)) unset($current['action']);

        return $current;
    }
}
