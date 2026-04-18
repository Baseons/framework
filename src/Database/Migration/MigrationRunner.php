<?php

namespace Baseons\Database\Migration;

use Baseons\Database\Connection;
use Baseons\Database\DB;
use Baseons\Database\Migration\Scheme\Columns;
use Baseons\Database\Migration\Scheme\Table;
use Baseons\Database\Migration\Scheme\Memory;
use Baseons\Database\Query\Scheme;
use Throwable;

class MigrationRunner
{
    private array $errors = [];
    private array $infos = [];

    private string $name;
    private string $file;

    private string|null $connection;
    private array|null $config = null;

    private $migration;
    private Memory|null $memory = null;
    private Scheme|null $scheme = null;
    private Columns|null $column = null;

    public function run(string $migration, bool $force = false, string $type = 'up')
    {
        Memory::reset(true);

        if (!in_array($type, ['up', 'down'])) {
            $this->addError('method ' . $type . ' migration does not exist');

            return;
        }

        $this->name = str_replace('.php', '', basename($migration));
        $this->file = path('database/migrations/' . $this->name . '.php');

        if (!file_exists($this->file)) {
            $this->addError('migration not fount');

            return;
        }

        try {
            $this->migration = require $this->file;
        } catch (Throwable) {
            $this->addError('failed to load class');

            return;
        }

        if (!method_exists($this->migration, 'up')) {
            $this->addError('the up method does not exist');

            return;
        }

        if (!method_exists($this->migration, 'down')) {
            $this->addError('the down method does not exist');

            return;
        }

        if ($type === 'up') $this->migration->up();
        if ($type === 'down') $this->migration->down();

        $this->config = Connection::config($this->migration->connection);
        $this->connection = $this->config['name'];
        $this->scheme = DB::scheme($this->connection);

        $this->migrationTable();

        $check = DB::table('migrations')->connection($this->connection)->select(['id', 'count'])->where('migration', $this->name)->first();

        if (!empty($check->id) and !$force) return;

        foreach (Memory::$builds as $build_definitions) {
            Memory::reset();

            try {
                if ($build_definitions['type'] == 'table') {
                    $this->memory = new Memory($this->connection, $this->config, $build_definitions['table']);

                    executeCallable($build_definitions['callable'], [new Table]);

                    $this->column = new Columns($this->config, $build_definitions['table']);
                    $this->memory::compileForeigns();

                    $this->exec();
                } elseif ($build_definitions['type'] == 'rename_table') {
                    $this->memory = new Memory($this->connection, $this->config, $build_definitions['table']);

                    $renamed = $this->scheme->table()->rename($build_definitions['table'], $build_definitions['to']);

                    if ($renamed) $this->addInfo("table {$build_definitions['table']} renamed to {$build_definitions['to']}");
                    else $this->addError('failed to rename table');
                } elseif ($build_definitions['type'] == 'drop_table') {
                    foreach ($build_definitions['table'] as $table) {
                        $this->memory = new Memory($this->connection, $this->config, $table);

                        if ($this->scheme->table()->has($table)) {
                            $drop = $this->scheme->table()->drop($table);

                            if ($drop) $this->addInfo("table $table droped");
                            else $this->addError("failed to drop table $table");
                        } else {
                            $this->addError("table $table does not exist");
                        }
                    }
                }
            } catch (Throwable $error) {
                $this->addError($error);

                return;
            }
        }

        if ($this->scheme->table()->has('migrations') and !empty($check->id)) {
            DB::table('migrations')->connection($this->memory::$connection)->where('id', $check->id)->update([
                'count' => $check->count + 1
            ]);
        } else {
            DB::table('migrations')->connection($this->memory::$connection)->insert([
                'count' => 1,
                'migration' => $this->name
            ]);
        }
    }

    private function migrationTable()
    {
        if (!$this->scheme->table()->has('migrations')) {
            if ($this->config['driver'] == 'pgsql') {
                $columns = [
                    $this->quotes('id') . ' BIGSERIAL NOT NULL',
                    $this->quotes('migration') . ' varchar(255) DEFAULT NULL UNIQUE',
                    $this->quotes('count') . ' INT',
                    $this->quotes('created_at') . ' timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
                    'PRIMARY KEY (' . $this->quotes('id') . ')'
                ];
            } else {
                $columns = [
                    $this->quotes('id') . ' BIGINT NOT NULL AUTO_INCREMENT',
                    $this->quotes('migration') . ' varchar(255) DEFAULT NULL UNIQUE',
                    $this->quotes('count') . ' INT',
                    $this->quotes('created_at') . ' timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
                    'PRIMARY KEY (' . $this->quotes('id') . ')'
                ];
            }

            $this->scheme->table()->create('migrations', $columns);
        }
    }

    private function exec()
    {
        if ($this->memory::$table === null) return;

        // create or edit
        if (!$this->scheme->table()->has($this->memory::$table)) {
            $this->createTable();
        } else {
            $this->editTable();
        }

        // constraints
        $this->runConstraints();
    }

    private function createTable()
    {
        $columns = [];
        $others = [];

        foreach ($this->memory::getColumns() as $value) {
            $querys = $this->column->create($value['name'], $value['type'], $value['options']);

            $columns = array_merge($columns, $querys['columns']);
            $others = array_merge($others, $querys['others']);
        }

        $create = $this->scheme->table()->create($this->memory::$table, $columns);

        if ($create) {
            $this->addInfo("table {$this->memory::$table} created");

            foreach ($others as $query) DB::connection($this->connection)->query($query)->execute();
        } else {
            $this->addInfo("failed to create table {$this->memory::$table}");
        }
    }

    private function editTable()
    {
        if ($this->memory::$dropPrimaryKey) {
            $primary = $this->scheme->constraint()->getPrimaryKey($this->memory::$table);

            if ($primary) {
                if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
                    $this->scheme->column()->change($this->memory::$table, $primary, 'BIGINT NOT NULL');
                } elseif ($this->config['driver'] === 'pgsql') {
                    $this->scheme->column()->change($this->memory::$table, $primary, 'TYPE BIGINT');
                    $this->scheme->column()->change($this->memory::$table, $primary, 'SET NOT NULL');
                }

                $drop = $this->scheme->constraint()->dropPrimaryKey($this->memory::$table);

                if ($drop) $this->addInfo('primary key droped');
                else $this->addError('failed to drop primary key');
            } else {
                $this->addError('primary key not defined');
            }
        }

        // drop coluns
        foreach ($this->memory::$dropColumns as $column) {
            if ($this->scheme->column()->has($this->memory::$table, $column)) {
                try {
                    $drop = $this->scheme->column()->drop($this->memory::$table, $column);
                } catch (Throwable $error) {
                    $drop = false;

                    $this->addError($error);
                }

                if ($drop) $this->addInfo("column $column droped");
                else $this->addError("failed to drop column $column");
            } else {
                $this->addError("column $column does not exist");
            }
        }

        // change or create coluns
        foreach ($this->memory->getColumns() as $column) {
            if (in_array($column['name'], $this->memory::$dropColumns)) continue;

            if ($this->scheme->column()->has($this->memory::$table, $column['name'])) {
                $querys = $this->column->edit($column['name'], $column['type'], $column['options'], false);

                foreach ($querys['columns'] as $type) {
                    $change = $this->scheme->column()->change($this->memory::$table, $column['name'], $type);

                    if ($change) $this->addInfo("column {$column['name']} changed");

                    foreach ($querys['others'] as $query) {
                        DB::connection($this->connection)->query($query)->execute();
                    }
                }
            } else {
                $querys = $this->column->create($column['name'], $column['type'], $column['options'], false);

                foreach ($querys['columns'] as $type) {
                    $create = $this->scheme->column()->create($this->memory::$table, $column['name'], $type);

                    if ($create) {
                        $this->addInfo("column {$column['name']} added");

                        foreach ($querys['others'] as $query) {
                            DB::connection($this->connection)->query($query)->execute();
                        }
                    };
                }
            }
        }

        // rename columns
        foreach ($this->memory::$renames as $column => $to) {
            if ($this->scheme->column()->has($this->memory::$table, $column) and !$this->scheme->column()->has($this->memory::$table, $to)) {
                $renamed = $this->scheme->column()->rename($this->memory::$table, $column, $to);

                if ($renamed) $this->addInfo("column $column renamed");
            }
        }
    }

    private function runConstraints()
    {
        // drop constraints
        foreach ($this->memory::$dropConstraints as $constraint) {
            if ($this->scheme->constraint()->has($this->memory::$table, $constraint)) {
                $drop = $this->scheme->constraint()->drop($this->memory::$table, $constraint);

                if ($drop) $this->addInfo("constraint $constraint droped");
            }
        }

        // drop indexs
        foreach ($this->memory::$dropIndexs as $index) {
            $drop = $this->scheme->constraint()->dropIndex($this->memory::$table, $index);

            if ($drop) $this->addInfo("index $index droped");
        }

        // id
        if ($this->memory::$id !== null) {
            if (!in_array($this->memory::$id, $this->memory::$dropColumns)) {
                $set = $this->scheme->constraint()->setId($this->memory::$table, $this->memory::$id);

                if ($set) $this->addInfo("primary key {$this->memory::$id} defined");
            }
        }

        // set constraints
        foreach ($this->memory::$constraints as $constraints) {
            if (in_array($constraints['name'], $this->memory::$dropConstraints)) continue;

            if (!$this->scheme->constraint()->has($this->memory::$table, $constraints['name'])) {
                $create = $this->scheme->constraint()->create($this->memory::$table, $constraints['name'], $constraints['type'], $constraints['value']);

                if ($create) $this->addInfo("constraint {$constraints['name']} added");
            } else {
                $this->scheme->constraint()->change($this->memory::$table, $constraints['name'], $constraints['type'], $constraints['value']);
            }
        }

        // set indexes
        foreach ($this->memory::$indexs as $index) {
            if (in_array($index['name'], $this->memory::$dropIndexs)) continue;

            $has = $this->scheme->constraint()->hasIndex($this->memory::$table, $index['name']);

            if ($has) $this->scheme->constraint()->dropIndex($this->memory::$table, $index['name']);

            $create = $this->scheme->constraint()->addIndex($this->memory::$table, $index['column'], $index['name'], $index['type']);

            if ($create) $this->addInfo("index {$index['name']} added");
        }
    }

    private function addError(string|Throwable $message)
    {
        if (!is_string($message)) $message = $message->getFile() . ':' . $message->getLine() . ' - ' . $message->getMessage();

        $this->errors[] = [
            'migration' => $this->name,
            'message' => $message,
            'table' => $this->memory::$table
        ];
    }

    private function addInfo(string $message)
    {
        $this->infos[] = [
            'migration' => $this->name,
            'message' => $message,
            'table' => $this->memory::$table
        ];
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getInfos()
    {
        return $this->infos;
    }

    private function quotes(string $string)
    {
        $string = preg_replace('/\b(?!as\b)(\w+)\b/i', $this->config['quotes'] . '$1' . $this->config['quotes'], $string);
        $string = preg_replace('/(' . preg_quote($this->config['quotes']) . ')\s/', '$1 ', $string);

        return $string;
    }
}
