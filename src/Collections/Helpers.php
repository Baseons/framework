<?php

use Baseons\Collections\Arr;
use Baseons\Collections\Config;
use Baseons\Collections\Env;
use Baseons\Collections\Hash;
use Baseons\Collections\Http\HTTP;
use Baseons\Collections\Lang;
use Baseons\Collections\Mime;
use Baseons\Collections\SSH;
use Baseons\Collections\Str;
use Baseons\Collections\Validator;
use Baseons\Exceptions\Debug;
use Baseons\Http\Csrf;
use Baseons\Http\Redirect;
use Baseons\Http\Request;
use Baseons\Http\Response;
use Baseons\Http\Route;
use Baseons\Kernel;
use Baseons\Mail\Mail;
use Baseons\Server\Controller;
use Baseons\Storage\Path;
use Baseons\Storage\Storage;
use Baseons\View\View;

if (!function_exists('view')) {
    function view(string $view, array|object $params = [], bool $echo = true, string|null $path = null)
    {
        return (new View)->load($view, $params, $echo, $path);
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirecionamentos
     */
    function redirect(string|null $destination = null, $status = 302)
    {
        if ($destination !== null) return (new Redirect)->destination($destination, $status);

        return new Redirect;
    }
}

if (!function_exists('request')) {
    /**
     * Funções request
     */
    function request()
    {
        return new Request;
    }
}

if (!function_exists('response')) {
    function response()
    {
        return new Response;
    }
}

if (!function_exists('old')) {
    /**
     * Returns the value defined in the last request
     */
    function old(string|null $key = null, mixed $default = null)
    {
        return request()->old($key, $default);
    }
}

if (!function_exists('validator')) {
    function validator(array|null $data, array|null $availables = null)
    {
        return new Validator($data, $availables);
    }
}

if (!function_exists('abort')) {
    function abort(int $status, string|null $message = null)
    {
        return (new Response)->abort($status, $message);
    }
}

if (!function_exists('env')) {
    function env(string|null $key = null, mixed $or = null)
    {
        return Env::value($key, $or);
    }
}

if (!function_exists('config')) {
    /**
     * @return Baseons\Collections\Config|mixed
     */
    function config(string|null $name = null, int|string|array|null $keys = null, mixed $default = null)
    {
        if ($name === null) return (new Config);

        return Config::get($name, $keys, $default);
    }
}

if (!function_exists('route')) {
    function route(string|null $name = null, string|array|null ...$params)
    {
        if ($name !== null) return Route::name($name, $params);

        return new Route;
    }
}

if (!function_exists('server')) {
    function server(string|null $name = null, string|array|null ...$params)
    {
        if ($name !== null) return Controller::route($name, $params);

        return new Controller;
    }
}

if (!function_exists('csrf')) {
    function csrf()
    {
        return new Csrf;
    }
}

if (!function_exists('dd')) {
    /**
     * @return string
     */
    function dd()
    {
        $backtrace = debug_backtrace();
        $line = $backtrace[0]['line'] ?? '';
        $file = $backtrace[0]['file'] ?? '';

        if (Kernel::type() === 'console') {
            foreach (func_get_args() as $arg) var_dump($arg);

            Kernel::terminate();
        }

        return (new Debug)->dd($line, $file, func_get_args());
    }
}

if (!function_exists('dump')) {
    /**
     * @return string
     */
    function dump()
    {
        $backtrace = debug_backtrace();
        $line = $backtrace[0]['line'] ?? '';
        $file = $backtrace[0]['file'] ?? '';

        if (Kernel::type() === 'console') {
            foreach (func_get_args() as $arg) var_dump($arg);

            return;
        }

        return (new Debug)->dump($line, $file, func_get_args());
    }
}

if (!function_exists('path')) {
    /**
     * @return string|Path
     */
    function path(string|null $path = null)
    {
        return $path === null ? new Path : Path::base($path);
    }
}

if (!function_exists('storage')) {
    function storage()
    {
        return new Storage;
    }
}

if (!function_exists('mail')) {
    function mail()
    {
        return new Mail();
    }
}

if (!function_exists('mime')) {
    function mime()
    {
        return new Mime();
    }
}

if (!function_exists('ssh')) {
    function ssh(string|null $connection = null)
    {
        return new SSH($connection);
    }
}

if (!function_exists('executeCallable')) {
    function executeCallable(string|array|callable $callable, array $args = [], string|null $namespace = null)
    {
        $callback = null;

        $args = array_values($args);

        $namespace = !empty($namespace) ? trim($namespace, '\\') . '\\' : '';

        if (is_string($callable)) {
            if (str_contains($callable, '::')) {
                $params = explode('::', $callable);
            } elseif (str_contains($callable, '@')) {
                $params = explode('@', $callable);
            }

            if (array_key_exists(0, $params) and array_key_exists(1, $params)) {
                $callable = [];
                $class = $namespace . $params[0];
                $callable[0] = new $class;

                $callable[1] = $params[1];
                $reflection = new ReflectionMethod($callable[0], $callable[1]);
                $callback = $callable;
            }
        } elseif (is_array($callable)) {
            if (count($callable) == 1) {
                return new $callable[0];
            } else {
                $callable[0] = new $callable[0];
                $reflection = new ReflectionMethod($callable[0], $callable[1]);
            }

            $callback = $callable;
        } elseif (is_callable($callable)) {
            $callback = $callable;
            $reflection = new ReflectionFunction($callback);
        }

        if (is_callable($callback)) {
            $parameters = $reflection->getParameters();
            $args_valid = [];

            foreach ($parameters as $key => $value) {
                $arg_name = $value->getName();

                if (array_key_exists($key, $args)) $args_valid[$arg_name] = $args[$key];
            }

            return call_user_func_array($callback, $args_valid);
        }

        return null;
    }
}

if (!function_exists('executeString')) {
    function executeString(string $code)
    {
        $code = "<?= {$code}; ?>";
        $tmp = tempnam(sys_get_temp_dir(), 'php_');

        file_put_contents($tmp, $code);
        ob_start();

        include $tmp;
        $output = ob_get_clean();

        unlink($tmp);

        return $output;
    }
}

if (!function_exists('str')) {
    function str()
    {
        return new Str();
    }
}

if (!function_exists('arr')) {
    function arr()
    {
        return new Arr();
    }
}

if (!function_exists('http')) {
    function http(string|null $url = null)
    {
        return new HTTP($url);
    }
}

if (!function_exists('lang')) {
    function lang(string|null $key = null)
    {
        if ($key === null) return new Lang;

        return (new Lang())->get($key);
    }
}


if (!function_exists('ping')) {
    /**
     * @param string Ex: http://localhost:8080 is acceptable
     * @param int $timeout timeout em milliseconds
     * @return int|null In success return microseconds
     */
    function ping(string $host, int $port = 80, int $timeout = 500)
    {
        try {
            $parse = parse_url($host);
            $host = $parse['host'] ?? $host;
            $port = $parse['port'] ?? $port;
            $start = microtime(true);

            $fp = @fsockopen(
                $host,
                $port,
                $errno,
                $errstr,
                $timeout / 1000
            );

            if (!$fp) return null;

            fclose($fp);

            return round((microtime(true) - $start) * 1000000);
        } catch (Throwable $th) {
            return null;
        }
    }
}
