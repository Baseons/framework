<?php

namespace Baseons\Database;

use ErrorException;
use InvalidArgumentException;
use PDO;
use Throwable;

class Connection
{
    public static array $drivers = ['mysql', 'pgsql', 'mariadb'];

    private static string|null $default = null;
    private static array $instances = [];
    private static array $configs = [];

    /**
     * @return PDO
     */
    public static function instance(string|null $connection = null)
    {
        $instance = null;

        if (is_null($connection)) $connection = self::$default;
        if (is_string($connection) and array_key_exists($connection, self::$instances)) $instance = $connection;

        $config = self::config($connection);

        if (array_key_exists($config['name'], self::$instances)) $instance = $config['name'];

        if ($instance) {
            try {
                self::$instances[$instance]->query('SELECT 1');
            } catch (Throwable $th) {
                unset(self::$instances[$instance]);

                return self::instance($connection);
            }

            return  self::$instances[$instance];
        }

        $driver = $config['driver'];
        $host = $config['host'];
        $port = $config['port'];
        $dbname = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        $options = null;

        if (array_key_exists('options', $config) and is_array($config['options'])) $options = $config['options'];

        self::$instances[$config['name']] = PDO::connect("$driver:host=$host;port=$port;dbname=$dbname", $username, $password, $options);

        if ($driver == 'pgsql' and !empty($config['search_path'])) self::instance($config['name'])->exec("SET search_path TO {$config['search_path']}");

        return self::$instances[$config['name']];
    }

    /**
     * @return array
     */
    public static function config(string|null $connection = null)
    {
        if (is_null($connection)) $connection = self::$default ?? config()->database('default', null);
        if (is_null($connection)) throw new ErrorException('default connection not found');

        if (array_key_exists($connection, self::$configs)) return self::$configs[$connection];

        $config = config()->database('connections.' . $connection, []);

        $requireds = ['driver', 'host', 'port', 'database', 'username', 'password'];

        foreach ($requireds as $required) if (empty($config[$required])) {
            throw new InvalidArgumentException(sprintf('%s connection required', $required));
        }

        if (!in_array($config['driver'], self::$drivers)) throw new ErrorException('unsupported database connection driver');

        $config['quotes'] = $config['driver'] == 'pgsql' ? '"' : '`';
        $config['name'] = $connection;

        self::$configs[$connection] = $config;

        return $config;
    }

    public static function setDefault(string|null $connection)
    {
        self::$default = $connection;

        return self::config();
    }

    public static function close(string|null $connection = null)
    {
        if ($connection !== null) {
            if (array_key_exists($connection, self::$instances)) {
                self::$instances[$connection] = null;
                unset(self::$instances[$connection]);
            }

            return;
        }

        foreach (self::$instances as $key => $instance) {
            self::$instances[$key] = null;
            unset(self::$instances[$key]);
        }

        self::$instances = [];
    }

    public function __destruct()
    {
        self::close();
    }
}
