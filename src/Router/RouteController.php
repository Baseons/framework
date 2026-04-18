<?php

namespace Baseons\Router;

use Baseons\Collections\Mime;
use Baseons\Kernel;
use UnexpectedValueException;

class RouteController
{
    private array $configs = [];
    private array $route = [];

    private array $names = [];
    private array $params = [];

    private array|null $current = null;
    private bool $method_valid = false;

    public function load()
    {
        $this->configs = config()->route('http', []);

        foreach ($this->configs as $name => $config) {
            Route::config($name);

            require_once $config['path'];

            Route::finish();
        }

        $this->route = Kernel::getMemory('route', []);

        if (!count($this->route)) return;

        $url = trim(filter_var(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), FILTER_SANITIZE_URL), '/');;
        $method = request()->method();
        $domain = request()->domain();

        foreach ($this->route['routes'] as $key => $route) {
            if (!empty($this->configs[$route['config']]['middleware'])) {
                array_unshift($this->route['routes'][$key]['middleware'], $this->configs[$route['config']]['middleware']);
            }

            if (!empty($this->configs[$route['config']]['prefix'])) {
                $this->route['routes'][$key]['route'] = trim($this->configs[$route['config']]['prefix'] . '/' . $this->route['routes'][$key]['route'], '/');
            }

            if (!empty($route['name'])) $this->names[$route['name']] = trim(preg_replace('/{(.*?)}/', '{?}', $route['route']), '/');

            $params = $this->params($this->route['routes'][$key]['route'], $url);

            if ($params === false) continue;

            $this->params = $params;

            if (in_array($method, $route['methods'])) {
                $this->method_valid = true;

                if (count($route['domain'])) {
                    if (in_array($domain, $route['domain'])) {
                        $this->current = $this->route['routes'][$key];
                    }
                } else {
                    $this->current = $this->route['routes'][$key];
                }
            } else if ($this->current === null) {
                $this->current = $this->route['routes'][$key];
                $this->method_valid = false;
            }
        }

        Kernel::setMemory('route', $this->route);
        Kernel::setMemory('route.current', $this->current);
        Kernel::setMemory('route.params', $this->params);
        Kernel::setMemory('route.names', $this->names);
        Kernel::setMemory('route.config', $this->current ? $config = $this->configs[$this->current['config']] : null);
    }

    public function execute()
    {
        if ($this->current and $this->method_valid) {
            foreach ($this->current['middleware'] as $middleware) {
                if (is_string($middleware)) $middleware = '\App\Middlewares\\' . $middleware;

                executeCallable($middleware);
            }

            if ($this->configs[$this->current['config']]['csrf']['active'] and request()->method('POST') and !csrf()->check()) return response()->abort(401);

            if ($this->current['type'] == 'redirect') return redirect($this->current['action']['destination'], $this->current['action']['status']);

            if ($this->current['type'] == 'view') return response()->view($this->current['action'][0], $this->current['action'][1]);

            if ($this->current['type'] == 'file') {
                $path = $this->current['action'][0];
                $callback = $this->current['action'][1];
                $cache = $this->current['action'][2];

                if (count($this->params)) $path = str_replace(array_map(function ($value) {
                    return '{' . $value . '}';
                }, array_keys($this->params)), $this->params, $path);

                if (!file_exists($path) or !is_file($path)) return response()->abort(404, 'File not found');

                $mime = Mime::mimeByExtension($path);
                if ($mime) response()->contentType($mime);

                $path_json_files = path()->storage('cache/jsons/files.json');
                $json_files = storage()->getJson($path_json_files, []);

                if ($cache and array_key_exists($path, $json_files) and $json_files[$path]['filemtime'] == filemtime($path) and file_exists($json_files[$path]['file'])) return response()->terminate(file_get_contents($json_files[$path]['file']));

                if ($callback) {
                    $content = executeCallable($callback, [file_get_contents($path), $this->params]);
                    if (!is_string($content)) throw new UnexpectedValueException('Invalid callback response, must be a string type');
                } else $content = file_get_contents($path);

                if ($cache) {
                    if (!empty($json_files[$path])) storage()->delete($json_files[$path]['file']);

                    $json_files[$path] = [
                        'file' => path()->storage('cache/files/' .  strtolower(bin2hex(random_bytes(12))) . '.' . pathinfo($path, PATHINFO_EXTENSION)),
                        'filemtime' => filemtime($path)
                    ];

                    storage()->makeFile($json_files[$path]['file'], $content);
                    storage()->makeJson($path_json_files, $json_files);
                }

                return response()->terminate($content);
            }

            $result = executeCallable($this->current['action'], $this->params, $this->current['namespace']);

            if (is_string($result) || is_numeric($result)) {
                return response()->text($result);
            } else if (!is_callable($result) and (is_array($result) or is_object($result))) {
                return response()->json($result);
            }

            Kernel::terminate();
        } elseif ($this->current) {

            if (!$this->method_valid && request()->method() !== 'OPTIONS') return response()->abort(405);

            response()->header('Access-Control-Allow-Headers', '*');
            response()->header('Access-Control-Allow-Credentials', 'true');
            response()->header('Access-Control-Allow-Origin', request()->origin());
            response()->header('Access-Control-Allow-Methods', implode(', ', $this->current['methods']));
            response()->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, Session-Token, Token, Session, Code');

            return response()->text('ok');
        }

        return response()->abort(404);
    }

    private function params(string $route, string $url)
    {
        $route = trim($route, '/');
        $check = $route;
        $params = [];

        if (preg_match('/{(.*?)}/', $route)) {
            $array_route = explode('/', $route);
            $array_url = explode('/', $url);

            foreach ($array_route as $key => $value) {
                if (preg_match('/{(.*?)}/', $value, $math)) {
                    $param = str_replace(['?}', '{', '}'], '', $math[0]);

                    if (isset($array_url[$key])) {
                        $params[$param] = $array_url[$key];
                        $check = str_replace($math[0], $array_url[$key], $check);
                    } elseif (substr($value, -2) == '?}') {
                        $params[$param] = null;
                        $check = str_replace("/$math[0]", '', $check);
                    }
                }
            }
        }

        if ($check == $url) return array_map(function ($value) {
            return $value == '' ? null : $value;
        }, $params);

        return false;
    }
}
