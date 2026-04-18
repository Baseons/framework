<?php

namespace Baseons\Server;

use Baseons\Collections\Hash;
use Baseons\Shell\Shell;
use InvalidArgumentException;

class Controller
{
    private static bool $loaded = false;
    private static array $servers = [];
    private static array $names = [];

    public static function route(string $name, string|array|null ...$params)
    {
        self::load();

        if (!empty($params[0])) if (is_array($params[0])) $params = $params[0];

        if (array_key_exists($name, self::$names)) {
            $route = self::$names[$name];

            if (count($params)) {
                if (preg_match_all('/\{\?\}/', $route, $matches)) {
                    if (!empty($matches[0])) {
                        foreach ($matches[0] as $key => $value) {
                            if ($position = strpos($route, $value)) {
                                $route = substr_replace($route, !empty($params[$key]) ? $params[$key] : '', $position, strlen($value));
                            }
                        }
                    }
                };
            }

            return trim(str_replace(['/{?}', '{?}'], '', $route), '/');
        }

        return null;
    }

    public static function list(string|null $type = null)
    {
        self::load();

        if (is_null($type)) return self::$servers;

        $type = strtolower($type);

        if ($type == 'ws') $type = 'websocket';

        if (!array_key_exists($type, self::$servers)) return null;

        return self::$servers[$type];
    }

    public static function online(string $name)
    {
        self::load();

        foreach (self::$servers as $type => $servers) foreach ($servers as $server) {
            if ($server['name'] == $name or ($name !== null and $server['key'] == $name)) return ping($server['host'], $server['port']) ? true : false;
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function start(string|null $name = null)
    {
        self::load();

        $started = [];

        foreach (self::$servers as $type => $servers) foreach ($servers as $server) {
            if ($name === null or $name === $server['id'] or (!empty($server['name']) and $name and $name == $server['name'])) {
                if (!ping($server['host'], $server['port'])) {
                    $command = 'cd ' . path()->base() . '&& php base server:run ' . $server['id'] . ' > /dev/null 2>&1 &';

                    Shell::exec($command, name: $server['name'], description: 'Baseons Framework Server');

                    usleep(500000);

                    if (ping($server['host'], $server['port'])) $started[] = $server;
                }

                if ($name !== null) break;
            }
        }

        if (count($started)) return $name === null ? $started : $started[0];

        return false;
    }

    /**
     * @return array|false
     */
    public static function stop(string|null $name = null)
    {
        self::load();

        $app_key = env('APP_KEY');

        if (empty($app_key)) return false;

        $stopped = [];

        foreach (self::$servers as $type => $servers) foreach ($servers as $server) {
            if ($name === null or $name === $server['id'] or (!empty($server['name']) and $name and $name == $server['name'])) {
                if (ping($server['host'], $server['port'])) {
                    $url = $server['ssl']['active'] ? 'https://' : 'http://';
                    $url .= $server['alias'] . ':' . $server['port'] . '/callback';

                    $response = http($url)->header('auth-token', $app_key)->post([
                        'baseons' => 'stop'
                    ]);

                    if ($response->body() === 'ok') $stopped[] = $server;
                }

                if ($name !== null) break;
            }
        }

        if (count($stopped)) return $name === null ? $stopped : $stopped[0];

        return false;
    }

    /**
     * @return int|false
     */
    public static function connections(string $name)
    {
        self::load();

        $app_key = env('APP_KEY');

        if (empty($app_key)) return false;

        foreach (self::$servers as $type => $servers) foreach ($servers as $server) {
            if (!empty($server['name']) and $name == $server['name']) {
                if (ping($server['host'], $server['port'])) {
                    $url = $server['ssl']['active'] ? 'https://' : 'http://';
                    $url .= $server['alias'] . ':' . $server['port'] . '/callback';

                    $response = http($url)->header('auth-token', $app_key)->post([
                        'baseons' => 'connections'
                    ]);

                    if (is_numeric($response->body())) return (int)$response->body();
                }
            }
        }

        return false;
    }

    /**
     * @return array|false
     */
    public static function restart(string|null $name = null, callable|null $callback = null)
    {
        self::load();

        $result = [
            'started' => [],
            'restarted' => [],
            'failed' => []
        ];

        foreach (self::$servers as $type => $servers) foreach ($servers as $server) {
            if ($name === null or $name === $server['id'] or (!empty($server['name']) and $name and $name == $server['name'])) {
                $online = ping($server['host'], $server['port']);
                $status = 'restarted';

                if ($online and self::stop($server['id'])) {
                    usleep(500000);

                    if (self::start($server['id'])) $result['restarted'][] = $server;
                    else $status = 'failed';
                } elseif (!$online and self::start($server['id'])) {
                    $result['started'][] = $server;
                        $status = 'started';
                } else {
                    $result['failed'][] = $server;
                    $status = 'failed';
                }

                if ($callback) executeCallable($callback, [$server, $status, $type]);

                if ($name !== null) break;
            }
        }

        return (count($result['restarted']) or count($result['started']) or count($result['failed'])) ? $result : false;
    }

    /**
     * Send request callback
     *
     * @return \Baseons\Collections\Http\Response|false
     */
    public static function request(string $name, array $data = [], string $method = 'POST', array $headers = [])
    {
        self::load();
        $app_key = env('APP_KEY');

        if (empty($app_key)) return false;

        $request = http();
        $headers['auth-token'] = $app_key;
        $methods = ['get', 'post', 'put', 'delete', 'patch', 'copy', 'options', 'lock', 'unlock'];
        $method = strtolower($method);

        if (!in_array($method, $methods)) throw new InvalidArgumentException(sprintf('Invalid request method %s', $method));

        foreach ($headers as $key => $value) $request->header($key, $value);

        foreach (self::$servers as $type => $servers) foreach ($servers as $server) {
            if ($server['name'] == $name) {
                $url = $server['ssl']['active'] ? 'https://' : 'http://';
                $url .= $server['alias'] . ':' . $server['port'] . '/callback';
                $request->url($url);

                return $request->$method($data);
            }
        }

        return false;
    }

    private static function load(string|null $request_path = null)
    {
        if (self::$loaded) return false;

        $config = config()->route('server');

        if (empty($config['path']) or !count($config['path'])) return false;

        foreach ($config['path'] as $path) require_once $path;

        self::$servers = ServerMemory::$servers;

        foreach (self::$servers as $type => $servers) {
            foreach ($servers as $key => $params) {
                self::$servers[$type][$key]['valid'] = true;
                self::$servers[$type][$key]['params'] = [];
                self::$servers[$type][$key]['route'] = '';

                if ($params['protocol'] == 'ws') {
                    self::$servers[$type][$key]['route'] = self::$servers[$type][$key]['ssl']['active'] ? 'wss://' : 'ws://';
                }

                if (!empty($params['alias'])) self::$servers[$type][$key]['route'] .= $params['alias'];
                elseif (!empty($params['host'])) self::$servers[$type][$key]['route'] .= $params['host'];

                if (!empty($params['port'])) self::$servers[$type][$key]['route'] .= ':' . $params['port'];

                if (!empty($params['path'])) {
                    self::$servers[$type][$key]['route'] .= '/' . trim(preg_replace('/{(.*?)}/', '{?}', $params['path']), '/');

                    $check = self::handlePath($params['path'], $request_path);

                    self::$servers[$type][$key]['valid'] = $check['valid'];
                    self::$servers[$type][$key]['params'] = $check['params'];
                }

                self::$servers[$type][$key]['id'] = Hash::createTokenByString($type . self::$servers[$type][$key]['route'], special: false);

                if (!empty($params['name'])) self::$names[$params['name']] = self::$servers[$type][$key]['route'];
            }
        }

        self::$loaded = true;

        return true;
    }

    private static function handlePath(string $path, string|null $request_path = null)
    {
        if (is_null($request_path)) $request_path = '';

        $path = trim($path, '/');
        $request_path = trim($request_path, '/');
        $check = $path;
        $params = [];

        if (preg_match('/{(.*?)}/', $path)) {
            $array_route = explode('/', $path);
            $array_url = explode('/', $request_path);

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

        return [
            'valid' => $check == $request_path,
            'params' =>  array_map(function ($value) {
                return empty($value) ? null : $value;
            }, $params)
        ];
    }
}
