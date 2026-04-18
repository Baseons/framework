<?php

namespace Baseons\Database;

use Baseons\Database\Connection;
use Baseons\Database\Query\Builder;
use Baseons\Database\Query\Scheme;

class DB
{
    /**
     * Connection instance
     */
    public static function connection(string|null $connection = null)
    {
        return Connection::instance($connection);
    }

    /**
     * Raw query
     */
    public static function query(string $query, array $bindparams = [], string|null $connection = null)
    {
        $instance = Connection::instance($connection);
        $query = $instance->prepare($query);

        if (count($bindparams) > 0) {
            $count = 1;

            foreach ($bindparams as $value) {
                $query->bindValue($count, $value);
                $count++;
            }
        }

        $query->execute();

        return $query;
    }

    public static function raw(string $value)
    {
        return fn () => $value;
    }

    /**
     * Query table
     */
    public static function table(string $table, null|string $as = null)
    {
        return (new Builder)->table($table, $as);
    }

    /**
     * Database scheme
     */
    public static function scheme(string|null $connection = null)
    {
        return new Scheme($connection);
    }
}
