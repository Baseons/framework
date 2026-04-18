<?php

namespace Baseons\Database\Query\Scheme;

use Baseons\Database\DB;
use InvalidArgumentException;
use PDO;

class Column
{
    private array $config = [];

    public function __construct(array $config)
    {
        $this->config = $config;

        if ($this->config['driver'] == 'pgsql' and !empty($config['search_path'])) {
            $this->config['database'] = $config['search_path'];
        }
    }

    /**
     * @return array|null
     */
    public function getNames(string $table)
    {
        $data = [];

        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $query = DB::query('SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ? ORDER BY ORDINAL_POSITION ASC', [$table, $this->config['database']], $this->config['name'])->fetchAll(PDO::FETCH_ASSOC);

            if (count($query)) foreach ($query as $value) $data[] = $value['column_name'] ?? $value['COLUMN_NAME'];
        } else {
            $this->driverError($this->config['driver']);
        }

        if (count($data)) return $data;

        return null;
    }

    /**
     * @return bool
     */
    public function has(string $table, string $column)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $query = DB::query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$this->config['database'], $table, $column], $this->config['name'])->fetch(PDO::FETCH_ASSOC);
            if (!empty($query)) return true;
        } else {
            $this->driverError($this->config['driver']);
        }

        return false;
    }

    /**
     * @return array|null
     */
    public function getSchema(string $table, string $column)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $query = DB::query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$this->config['database'], $table, $column], $this->config['name'])->fetch(PDO::FETCH_ASSOC);
            if (!empty($query)) return $query;
        } else {
            $this->driverError($this->config['driver']);
        }

        return null;
    }

    /**
     * Create column
     * @return bool
     */
    public function create(string $table, string $column, string $type)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            DB::query(sprintf('ALTER TABLE %s ADD COLUMN %s %s', $this->quotes($table),  $this->quotes($column), $type), connection: $this->config['name']);
        } else {
            $this->driverError($this->config['driver']);
        }

        return $this->has($table, $column);
    }

    /**
     * Change column - return array diference
     * @return bool
     */
    public function change(string $table, string $column, string $type)
    {
        if (!$this->has($table, $column));

        $old = $this->getSchema($table, $column);

        unset($old['ORDINAL_POSITION']);

        if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
            DB::query(sprintf('ALTER TABLE %s CHANGE %s %s %s', $this->quotes($table), $this->quotes($column), $this->quotes($column), $type), connection: $this->config['name']);
        } else if ($this->config['driver'] == 'pgsql') {
            DB::query(sprintf('ALTER TABLE %s ALTER COLUMN %s %s', $this->quotes($table), $this->quotes($column), $type), connection: $this->config['name']);
        } else {
            $this->driverError($this->config['driver']);
        }

        $new = $this->getSchema($table, $column);

        unset($new['ORDINAL_POSITION']);

        if (empty($new)) return false;

        $difference = array_diff($new, $old);

        if (!count($difference)) return false;

        return true;
    }

    /**
     * Drop column
     * @return bool
     */
    public function drop(string $table, string $column)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            DB::query(sprintf('ALTER TABLE %s DROP COLUMN %s', $this->quotes($table), $this->quotes($column)), connection: $this->config['name']);
        } else {
            $this->driverError($this->config['driver']);
        }

        return !$this->has($table, $column);
    }

    /**
     * Rename column
     * @return bool
     */
    public function rename(string $table, string $column, string $to)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            DB::query(sprintf('ALTER TABLE %s RENAME COLUMN %s to %s', $this->quotes($table), $this->quotes($column), $this->quotes($to)), connection: $this->config['name']);
        } else {
            $this->driverError($this->config['driver']);
        }

        return $this->has($table, $to);
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
