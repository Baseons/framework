<?php

namespace Baseons;

use Baseons\Console\Console;
use Baseons\Exceptions\Exceptions;
use Baseons\Router\RouteController;

class Kernel
{
    /**
     * Type of execution http or console
     */
    private static string|null $type = null;

    /**
     * Framework memories
     */
    private static array $memories = [];

    /**
     * Callbacks to be executed at the end of the script
     */
    private static array $terminators = [];

    /**
     * Initial configs
     */
    private static array $configs = [];

    /**
     * Start framework
     */
    public static function run()
    {
        if (!defined('BASEONS_START')) define('BASEONS_START', microtime(true));

        ini_set('display_errors', 1);
        ini_set('display_startup_erros', 1);
        error_reporting(E_ALL);

        // define('DIRECTORY_BASEONS', DIRECTORY_BASE . DIRECTORY_SEPARATOR . 'core');
        define('DIRECTORY_BASEONS', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src');

        self::$configs['session'] = config()->session(default: []);
        self::$configs['app'] = config()->app(default: []);

        if (empty(self::$configs['app']['ini'])) self::$configs['app']['ini'] = [];
        if (empty(self::$configs['app']['timezone'])) self::$configs['app']['timezone'] = 'America/Los_Angeles';
        if (empty(self::$configs['app']['helpers'])) self::$configs['app']['helpers'] = [];

        (new Exceptions)->handler(function () {
            date_default_timezone_set(self::$configs['app']['timezone']);

            // ini settings
            $ini = array_merge([
                'date.timezone' => self::$configs['app']['timezone'],
                'session.name' => self::$configs['session']['name'] ?? 'session',
                'session.save_path' => self::$configs['session']['path'] ?? null,
                'session.cookie_secure' => self::$configs['session']['secure'] ?? true,
                'session.cookie_httponly' => self::$configs['session']['httponly'] ?? true,
                'session.cookie_samesite' => self::$configs['session']['samesite'] ?? 'Strict',
                'session.gc_maxlifetime' => self::$configs['session']['lifetime'] ?? 86400,
                'session.cookie_lifetime' => self::$configs['session']['lifetime'] ?? 86400,
                'session.cache_expire' => self::$configs['session']['lifetime'] ?? 86400,
                'session.cookie_domain' => self::$configs['session']['domain'] ?? ''
            ],  self::$configs['app']['ini']);

            foreach ($ini as $option => $value) ini_set($option, $value);
        });

        return new self;
    }

    /**
     * Execute http
     */
    public static function http()
    {
        self::$type = 'http';

        function filterInput(string|array|null $value)
        {
            if (is_null($value)) return null;

            if (is_string($value)) {
                if ($value === '') return null;
                return $value;
            }

            return array_map(function ($e) {
                if ($e === '') return null;

                if (is_array($e)) return filterInput($e);

                return $e;
            }, $value);
        }

        $_POST = filterInput($_POST ?? []);
        $_GET = filterInput($_GET ?? []);

        response()->header('server', 'Baseons Framework');

        (new Exceptions)->handler(function () {
            // start ob
            ob_start();
            session_start();

            $request_with = arr()->get($_SESSION, 'request_with.view');
            if ($request_with !== null and $request_with === false) arr()->set($_SESSION, 'request_with.view', true);

            // load helpers
            foreach (self::$configs['app']['helpers'] as $helper) require_once $helper;

            // load routers
            $route = new RouteController();
            $route->load();

            // execute route
            $route->execute();
        });
    }

    /**
     * Execute console
     */
    public static function console()
    {
        self::$type = 'console';

        (new Exceptions)->handler(function () {
            // load helpers
            foreach (self::$configs['app']['helpers'] as $helper) require_once $helper;

            // load console commands
            foreach (config()->route('console', []) as $console) require_once $console;

            Console::end();
        });
    }

    /**
     * Type of execution http or console
     */
    public static function type()
    {
        return self::$type;
    }

    /**
     * Set memory
     *
     * @return mixed
     */
    public static function setMemory(int|string|array $keys, mixed $value)
    {
        return arr()->set(self::$memories, $keys, $value);
    }

    /**
     * Get memory
     *
     * @return mixed|null
     */
    public static function getMemory(int|string|array $keys, mixed $default = null)
    {
        return arr()->get(self::$memories, $keys, $default);
    }

    /**
     * Remove from memory
     *
     * @return bool
     */
    public static function unsetMemory(int|string|array $keys)
    {
        return arr()->unset(self::$memories, $keys);
    }

    /**
     * Add or remove callback when script ends
     */
    public static function onTerminate(string|int $key, string|array|callable|null $callback)
    {
        if ($callback === null) {
            unset(self::$terminators[$key]);

            return;
        }

        self::$terminators[$key] = $callback;
    }

    /**
     * Finish execution
     */
    public static function terminate()
    {
        if (!defined('BASEONS_STOP')) define('BASEONS_STOP', microtime(true));
        if (!defined('BASEONS_MS')) define('BASEONS_MS', number_format((BASEONS_STOP - BASEONS_START) * 1000, 6, '.', '')); // ms

        foreach (self::$terminators as $callback) executeCallable($callback);

        if (session_status() === PHP_SESSION_ACTIVE and arr()->get($_SESSION, 'request_with.view', false)) arr()->unset($_SESSION, 'request_with');

        while (ob_get_level()) ob_end_flush();

        exit;
    }
}
