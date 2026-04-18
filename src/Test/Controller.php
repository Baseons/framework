<?php

namespace Baseons\Test;

use Baseons\Collections\Log;
use Baseons\Router\RouteController;
use ReflectionClass;
use Throwable;

class Controller
{
    public static array $results = [];

    public function __construct()
    {
        $route = new RouteController();
        $route->load();
    }

    public static function test()
    {
        $files = storage()->getFiles('tests', true);
        $test = new Test();

        $test_methods = array_map(function ($value) {
            return $value->name;
        }, (array)(new ReflectionClass($test))->getMethods());

        foreach ($files as $file) {
            require_once $file;

            $path = pathinfo($file, PATHINFO_FILENAME);

            if (class_exists($path)) {
                $class = new $path();
                $reflection = new ReflectionClass($path);

                foreach ($reflection->getMethods() as $method) {
                    if (in_array($method->getName(), $test_methods)) continue;

                    $line = $method->getStartLine();
                    $method = $method->getName();

                    try {
                        self::$results[$file][$method] = [
                            'file' => $file,
                            'line' => $line,
                            'method' => $method,
                            'asserts' => []
                        ];

                        ($class)->$method();
                    } catch (Throwable $th) {
                        Log::create('test', $th);

                        self::$results[$file][$method] = [
                            'file' => $th->getFile(),
                            'line' => $th->getLine(),
                            'method' => $method,
                            'asserts' => [false]
                        ];
                    }
                }
            }
        }

        return self::$results;
    }
}
