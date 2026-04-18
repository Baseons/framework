<?php

namespace Baseons\Database\Query\Scheme;

use Baseons\Database\DB;
use InvalidArgumentException;
use PDO;

class Constraint
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
     * @return bool
     */
    public function has(string $table, string $name)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $query = DB::query('SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?', [$this->config['database'], $table, $name], $this->config['name'])->fetch(PDO::FETCH_ASSOC);
            if (!empty($query)) return true;
        } else {
            $this->driverError($this->config['driver']);
        }

        return false;
    }

    /**
     * @return array|null
     */
    public function getSchema(string $table, string $name)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $query = DB::query('SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?', [$this->config['database'], $table, $name], $this->config['name'])->fetch(PDO::FETCH_ASSOC);
            if (!empty($query)) return $query;
        } else {
            $this->driverError($this->config['driver']);
        }

        return null;
    }

    /**
     * @return array
     */
    public function getNamesByType(string $table, string $type)
    {
        $names = [];

        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $query = DB::query('SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = ?', [$this->config['database'], $table, $type], $this->config['name'])->fetchAll(PDO::FETCH_ASSOC);
            if (count($query)) foreach ($query as $value) $names[] = $value['constraint_name'] ?? $value['CONSTRAINT_NAME'];
        } else {
            $this->driverError($this->config['driver']);
        }

        return $names;
    }

    /**
     * @return array
     */
    public function getNamesByColumn(string $table, string $column)
    {
        $names = [];

        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $query = DB::query('SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND CONSTRAINT_NAME IS NOT NULL', [$this->config['database'], $table, $column], $this->config['name'])->fetchAll(PDO::FETCH_ASSOC);

            if (count($query)) foreach ($query as $value) $names[] = $value['constraint_name'] ?? $value['CONSTRAINT_NAME'];
        } else {
            $this->driverError($this->config['driver']);
        }

        return $names;
    }

    /**
     * @return array|null
     */
    public function getNames(string $table)
    {
        $names = [];

        // $query = DB::query('SELECT conname FROM pg_constraint WHERE confrelid = ?::regclass;', [$table], $this->config['name'])->fetchAll(PDO::FETCH_ASSOC);

        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $query = DB::query('SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$this->config['database'], $table], $this->config['name'])->fetchAll(PDO::FETCH_ASSOC);

            if (count($query)) foreach ($query as $value) $names[] = $value['constraint_name'] ?? $value['CONSTRAINT_NAME'];
        } else {
            $this->driverError($this->config['driver']);
        }

        if (count($names)) return $names;

        return null;
    }

    /**
     * Drop constraint
     * @return bool
     */
    public function drop(string $table, string $name)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            DB::query(sprintf('ALTER TABLE %s DROP CONSTRAINT %s', $this->quotes($table), $this->quotes($name)), connection: $this->config['name'])->fetch(PDO::FETCH_OBJ);
        } else {
            $this->driverError($this->config['driver']);
        }

        return !$this->has($table, $name);
    }

    /**
     * @return bool
     */
    public function create(string $table, string $name, string $type, string $value)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            DB::query(sprintf('ALTER TABLE %s ADD CONSTRAINT %s %s %s', $this->quotes($table), $this->quotes($name), $type, $value), connection: $this->config['name']);
        } else {
            $this->driverError($this->config['driver']);
        }

        return $this->has($table, $name);
    }

    /**
     * @return bool */
    public function change(string $table, string $name, string $type, string $value)
    {
        $old = $this->getSchema($table, $name);

        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            if ($this->has($table, $name)) $this->drop($table, $name);
            DB::query(sprintf('ALTER TABLE %s ADD CONSTRAINT %s %s %s', $this->quotes($table), $this->quotes($name), $type, $value), connection: $this->config['name']);
        } else {
            $this->driverError($this->config['driver']);
        }

        $new = $this->getSchema($table, $name);

        if (empty($new)) return false;

        $difference = array_diff($new, $old);

        if (!count($difference)) return false;

        return true;
    }

    /**
     * Set column id - Primary key
     *
     *
     */
    public function setId(string $table, string $column)
    {
        $atual = $this->getPrimaryKey($table);

        if ($atual == $column) return false;

        $exists = DB::scheme($this->config['name'])->column()->has($table, $column);

        if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
            if ($atual) {
                DB::scheme($this->config['name'])->column()->change($table, $atual, 'BIGINT NOT NULL');

                $this->dropPrimaryKey($table);
            }

            if ($exists) DB::scheme($this->config['name'])->column()->change($table, $column, 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');
            else DB::scheme($this->config['name'])->column()->create($table, $column, 'BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');
        } elseif ($this->config['driver'] === 'pgsql') {
            if ($atual) {
                $this->dropPrimaryKey($table);

                DB::scheme($this->config['name'])->column()->change($table, $atual, 'TYPE BIGINT');
                DB::scheme($this->config['name'])->column()->change($table, $atual, 'SET NOT NULL');
            }

            if ($exists) DB::scheme($this->config['name'])->column()->change($table, $column, 'TYPE BIGSERIAL');
            else  DB::scheme($this->config['name'])->column()->create($table, $column, 'BIGSERIAL PRIMARY KEY');
        }

        return $this->getPrimaryKey($table) == $column;
    }

    /**
     * Define primary key of the table
     * @return bool
     */
    public function setPrimaryKey(string $table, string $column)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            DB::query(sprintf('ALTER TABLE %s ADD PRIMARY KEY %s(%s)', $this->quotes($table), 'PRIMARY', $this->quotes($column)), connection: $this->config['name']);
        } else {
            $this->driverError($this->config['driver']);
        }

        return $this->getPrimaryKey($table) == $column;
    }

    /**
     * Get PrimaryKey from the table
     * @return string|null
     */
    public function getPrimaryKey(string $table)
    {
        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $query = DB::query("SELECT column_name FROM information_schema.key_column_usage WHERE table_name = ? AND TABLE_SCHEMA = ? AND constraint_name = (SELECT constraint_name FROM information_schema.table_constraints WHERE table_name = ? AND constraint_type = 'PRIMARY KEY' AND TABLE_SCHEMA = ?)", [$table, $this->config['database'], $table, $this->config['database']],  $this->config['name'])->fetch(PDO::FETCH_ASSOC);

            if (!empty($query)) return $query['COLUMN_NAME'] ?? $query['column_name'];
        } else {
            $this->driverError($this->config['driver']);
        }

        return null;
    }

    /**
     * Drop PrimaryKey from the table
     * @return bool
     */
    public function dropPrimaryKey(string $table)
    {
        if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
            DB::query(sprintf('ALTER TABLE %s DROP PRIMARY KEY', $this->quotes($table)), [],  $this->config['name'])->fetch(PDO::FETCH_ASSOC);
        } else if ($this->config['driver'] == 'pgsql') {
            $names = $this->getNamesByType($table, 'PRIMARY KEY');

            foreach ($names as $name) $this->drop($table, $name);
        }

        return $this->getPrimaryKey($table) === null;
    }

    /**
     * @return bool
     */
    public function setPrimarySequence(string $table, int|null $sequence = null)
    {
        $primary = $this->getPrimaryKey($table);

        if (!$primary) return false;

        if ($sequence === null) {
            $sequence = DB::table($table)->selectMax($primary, 'sequence')->first()->sequence ?? 0;
            $sequence++;
        }

        if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
            DB::query(sprintf('ALTER TABLE `%s` AUTO_INCREMENT = %d', $table, $sequence), connection: $this->config['name']);

            return true;
        } else if ($this->config['driver'] == 'pgsql') {
            $schema = DB::scheme($this->config['name'])->column()->getSchema($table, $primary);

            if (!empty($schema['column_default']) and preg_match("/nextval\\('([^']+)'::regclass\\)/", $schema['column_default'], $matches)) {
                $query = DB::query(sprintf('SELECT setval(?, ?, false)'), [$matches[1], $sequence], $this->config['name'])->fetch(PDO::FETCH_ASSOC);

                return $query['setval'] == $sequence;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasIndex(string $table, string $name)
    {
        if (in_array($this->config['driver'], ['mysql',  'mariadb'])) {
            $query = DB::query('SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?', [$this->config['database'], $table, $name], $this->config['name'])->fetch(PDO::FETCH_ASSOC);

            if (!empty($query)) return true;
        } else if ($this->config['driver'] == 'pgsql') {
            $query = DB::query('SELECT indexname FROM pg_indexes WHERE schemaname = ? AND tablename = ? AND indexname = ?', [$this->config['database'], $table, $name], $this->config['name'])->fetch(PDO::FETCH_ASSOC);

            if (!empty($query)) return true;
        } else {
            $this->driverError($this->config['driver']);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function dropIndex(string $table, string $name)
    {
        if (!$this->hasIndex($table, $name)) return false;

        if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
            DB::query(sprintf('DROP INDEX %s ON %s', $this->quotes($name), $this->quotes($table)), [], $this->config['name'])->fetch(PDO::FETCH_ASSOC);
        } else if ($this->config['driver'] == 'pgsql') {
            DB::query(sprintf('DROP INDEX IF EXISTS %s CASCADE', $this->quotes($name)), [], $this->config['name'])->fetch(PDO::FETCH_ASSOC);
        } else {
            $this->driverError($this->config['driver']);
        }

        return !$this->hasIndex($table, $name);
    }

    /**
     * BTREE | RTREE | FULLTEXT | HASH
     * @return bool
     */
    public function addIndex(string $table, string|array $column, string $name, string $type = 'BTREE')
    {
        if (is_string($column)) $column = [$column];

        if (in_array($this->config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            foreach ($column as $key => $value) $column[$key] = $this->quotes($value);

            $column = implode(',', $column);

            // if ($type == 'BTREE') {
            //     $query = sprintf('CREATE INDEX %s ON %s(%s) USING BTREE', $this->quotes($name), $this->quotes($table), $column);
            // } elseif ($type == 'FULLTEXT') {
            //     $query = sprintf('CREATE FULLTEXT INDEX %s ON %s(%s)', $this->quotes($name), $this->quotes($table), $column);
            // } else if ($type == 'HASH') {
            //     $query = sprintf('CREATE INDEX %s ON %s(%s) USING HASH', $this->quotes($name), $this->quotes($table), $column);
            // } else if ($type == 'RTREE') {
            //     // $query = sprintf('CREATE INDEX %s ON %s(%s) USING RTREE', $this->quotes($name), $this->quotes($table), $column);

            //     if ($this->config['driver'] == 'pgsql') {
            //         // Substituir RTREE por GiST no PostgreSQL
            //         $query = sprintf('CREATE INDEX %s ON %s USING GiST(%s)', $this->quotes($name), $this->quotes($table), $column);
            //     } else {
            //         $query = sprintf('CREATE INDEX %s ON %s(%s) USING RTREE', $this->quotes($name), $this->quotes($table), $column);
            //     }
            // }

            switch ($type) {
                case 'BTREE':
                    if ($this->config['driver'] == 'pgsql') {
                        $query = sprintf('CREATE INDEX %s ON %s(%s)', $this->quotes($name), $this->quotes($table), $column);
                    } else {
                        $query = sprintf('CREATE INDEX %s ON %s(%s) USING BTREE', $this->quotes($name), $this->quotes($table), $column);
                    }

                    break;
                case 'FULLTEXT':
                    if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
                        $query = sprintf('CREATE FULLTEXT INDEX %s ON %s(%s)', $this->quotes($name), $this->quotes($table), $column);
                    } else {
                        throw new InvalidArgumentException('FULLTEXT indexes are not supported in PostgreSQL.');
                    }

                    break;
                case 'HASH':
                    if ($this->config['driver'] == 'pgsql') {
                        $query = sprintf('CREATE INDEX %s ON %s USING HASH(%s)', $this->quotes($name), $this->quotes($table), $column);
                    } else {
                        $query = sprintf('CREATE INDEX %s ON %s(%s) USING HASH', $this->quotes($name), $this->quotes($table), $column);
                    }

                    break;
                case 'RTREE':
                    if ($this->config['driver'] == 'pgsql') {
                        $query = sprintf('CREATE INDEX %s ON %s USING GiST(%s)', $this->quotes($name), $this->quotes($table), $column);
                    } else {
                        $query = sprintf('CREATE INDEX %s ON %s(%s) USING RTREE', $this->quotes($name), $this->quotes($table), $column);
                    }

                    break;
                default:
                    throw new InvalidArgumentException('Unsupported index type: ' . $type);
            }

            DB::query($query, [], $this->config['name'])->fetch(PDO::FETCH_ASSOC);
        } else {
            $this->driverError($this->config['driver']);
        }

        return $this->hasIndex($table, $name);
    }

    /**
     * @return array
     */
    public function getIndexs(string $table)
    {
        $data = [];

        if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
            $result = DB::query('SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE INDEX_NAME != ? AND TABLE_SCHEMA = ? AND TABLE_NAME = ? AND NULLABLE = ?', ['PRIMARY', $this->config['database'], $table, 'YES'], $this->config['name'])->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as $value) $data[] = $value['INDEX_NAME'];
        } else if ($this->config['driver'] == 'pgsql') {
            $result = DB::query('SELECT indexname FROM pg_indexes WHERE schemaname = ? AND tablename = ?', [$this->config['database'], $table], $this->config['name'])->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as $value)  $data[] = $value['indexname'];
        } else {
            $this->driverError($this->config['driver']);
        }

        return $data;
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
