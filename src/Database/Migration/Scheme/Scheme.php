<?php

namespace Baseons\Database\Migration\Scheme;

use Baseons\Database\DB;

class Scheme
{
    public static function table(string $name, callable $table)
    {
        Memory::$builds[] = [
            'type' => 'table',
            'table' => $name,
            'callable' => $table
        ];
    }

    public static function renameTable(string $table, string $to)
    {
        Memory::$builds[] = [
            'type' => 'rename_table',
            'table' => $table,
            'to' => $to
        ];
    }

    public static function dropTable(string|array $table)
    {
        if (is_string($table)) $table = [$table];

        Memory::$builds[] = [
            'type' => 'drop_table',
            'table' => $table
        ];
    }

    public static function hasConstraint(string $table, string $name)
    {
        return DB::scheme(Memory::$connection)->constraint()->has($table, $name);
    }

    public static function hasTable(string $table)
    {
        return DB::scheme(Memory::$connection)->table()->has($table);
    }

    public static function getTables()
    {
        return DB::scheme(Memory::$connection)->table()->getNames();
    }

    public static function hasColumn(string $table, string $column)
    {
        return DB::scheme(Memory::$connection)->column()->has($table, $column);
    }
}
