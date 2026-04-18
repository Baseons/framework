<?php

namespace Baseons\Database\Query\Scheme;

use Baseons\Database\DB;
use InvalidArgumentException;
use PDO;

class Table
{
    private array $config = [];

    private array $pgsql_tables = [
        'pg_class',
        'pg_attribute',
        'pg_index',
        'pg_type',
        'pg_namespace',
        'pg_roles',
        'pg_tables',
        'pg_matviews',
        'pg_stat_activity',
        'pg_stat_all_tables',
        'pg_locks',

        # PostGIS
        'spatial_ref_sys',
        'geometry_columns',
        'geography_columns',
        'raster_columns',
        'raster_overviews',

        # PostGIS Topology
        'topology.topology',
        'topology.layer'
    ];

    public function __construct(array $config)
    {
        $this->config = $config;

        if ($this->config['driver'] == 'pgsql' and !empty($config['search_path'])) $this->config['database'] = $config['search_path'];
    }

    /**
     * @return bool
     */
    public function rename(string $table, string $to)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            DB::query(sprintf('ALTER TABLE %s RENAME TO %s', $table, $to), connection: $this->config['name']);
        } else {
            $this->driverError($this->config['driver']);
        }

        return $this->has($to);
    }

    /**
     * @return bool
     */
    public function has(string $table)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $query = DB::query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND TABLE_TYPE = 'BASE TABLE'", [$this->config['database'], $table], $this->config['name'])->fetch(PDO::FETCH_ASSOC);

            if (!empty($query)) return true;
        } else {
            $this->driverError($this->config['driver']);
        }

        return false;
    }

    /**
     * @return array|null
     */
    public function getSchema(string $table)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $query = DB::query("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND TABLE_TYPE = 'BASE TABLE'", [$this->config['database'], $table], $this->config['name'])->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($query)) return $query;
        } else {
            $this->driverError($this->config['driver']);
        }

        return null;
    }

    /**
     * @return array
     */
    public function getNames()
    {
        $names = [];

        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $query = DB::query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE'", [$this->config['database']], $this->config['name'])->fetchAll(PDO::FETCH_ASSOC);

            if (count($query)) foreach ($query as $value) $names[] = $value['TABLE_NAME'] ?? $value['table_name'];
        } else {
            $this->driverError($this->config['driver']);
        }

        if (count($names)) return $names;

        return $names;
    }

    /**
     * @return bool
     */
    public function drop(string $table)
    {
        if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
            DB::query(sprintf('DROP TABLE %s', $this->quotes($table)), connection: $this->config['name']);
        } elseif ($this->config['driver'] == 'pgsql') {
            DB::query(sprintf('DROP TABLE %s CASCADE', $this->quotes($table)), connection: $this->config['name']);
        } else {
            $this->driverError($this->config['driver']);
        }

        return !$this->has($table);
    }

    /**
     * @return bool
     */
    public function dropAll()
    {
        $tables = $this->getNames();

        if ($this->config['driver'] == 'pgsql') $tables = array_diff($tables, $this->pgsql_tables);

        if (!count($tables)) return true;

        if (in_array($this->config['driver'], ['mysql', 'mariadb'])) DB::query('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $key => $table) if ($this->drop($table)) unset($tables[$key]);

        if (in_array($this->config['driver'], ['mysql', 'mariadb'])) DB::query('SET FOREIGN_KEY_CHECKS = 1');

        return !count($tables);
    }

    /**
     * @return bool
     */
    public function dropIfExists(string $table)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            DB::query(sprintf('DROP TABLE IF EXISTS %s', $this->quotes($table)), connection: $this->config['name']);
        } else {
            $this->driverError($this->config['driver']);
        }

        return !$this->has($table);
    }

    /**
     * @return bool
     */
    public function create(string $table, array $content, string|null $definitions = null)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $content = implode(',', $content);
            $definitions = $definitions ?? '';

            DB::query(sprintf('CREATE TABLE %s (%s) %s', $this->quotes($table), $content, $definitions), connection: $this->config['name']);
        } else {
            $this->driverError($this->config['driver']);
        }

        return $this->has($table);
    }

    private function quotes(string $string)
    {
        $string = preg_replace('/\b(?!as\b)(\w+)\b/i', $this->config['quotes'] . '$1' . $this->config['quotes'], $string);
        $string = preg_replace('/(' . preg_quote($this->config['quotes']) . ')\s/', '$1 ', $string);

        return $string;
    }

    private function driverError(string $driver)
    {
        throw new InvalidArgumentException('Driver not found for ' . $driver);
    }
}
