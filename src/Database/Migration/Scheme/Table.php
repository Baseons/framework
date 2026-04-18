<?php

namespace Baseons\Database\Migration\Scheme;

use InvalidArgumentException;

class Table
{
    public function raw(string $column, string $value)
    {
        Memory::addColumn($column, $value);

        return new TableOptions(Memory::$config['driver']);
    }

    public function id(string $name = 'id')
    {
        if (Memory::$id !== null) {
            throw new InvalidArgumentException('Table ' . Memory::$table . ' must have only one primary key');
        }

        Memory::$id = $name;
    }

    public function autoIncrement(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            $type = 'BIGINT AUTO_INCREMENT UNIQUE';
        } elseif (Memory::$config['driver'] == 'pgsql') {
            $type = 'BIGSERIAL';
        } else {
            return $this->typeError('autoIncrement');
        }

        Memory::addColumn($name, $type);

        new TableOptions(Memory::$config['driver'])->unique()->nullable(false);
    }

    public function varchar(string $name, int $size = 255)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $type = sprintf('varchar(%s)', $size);
        } else {
            return $this->typeError('varchar');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function text(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $type = 'text';
        } else {
            return $this->typeError('text');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function json(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $type = 'json';
        } else {
            return $this->typeError('json');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function int(string $name, int|null $size = null)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $type = sprintf($size !== null ? 'INT(%s)' : 'INT', $size);
        } else {
            return $this->typeError('int');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function bigInt(string $name, int|null $size = null)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $type = sprintf($size !== null ? 'BIGINT(%s)' : 'BIGINT', $size);
        } else {
            return $this->typeError('bigint');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function double(string $name, int $m = 10, int $d = 2)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            $type = sprintf('DOUBLE(%s,%s)', $m, $d);
        } elseif (Memory::$config['driver'] == 'pgsql') {
            $type = 'DOUBLE PRECISION NULL';
        } else {
            return $this->typeError('double');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function decimal(string $name, int $m = 10, int $d = 2)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $type = sprintf('DECIMAL(%s,%s)', $m, $d);
        } else {
            return $this->typeError('decimal');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function float(string $name, int $m = 10, int $d = 2)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            $type = sprintf('FLOAT(%s,%s)', $m, $d);
        } elseif (Memory::$config['driver'] == 'pgsql') {
            $type = 'REAL NULL';
        } else {
            return $this->typeError('float');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    /**
     * In Postgre install the PostGIS extension: CREATE EXTENSION postgis;
     */
    public function point(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            $type = 'POINT';
        } elseif (Memory::$config['driver'] == 'pgsql') {
            $type = 'GEOMETRY(POINT)';
        } else {
            return $this->typeError('point');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    /**
     * In Postgre install the PostGIS extension: CREATE EXTENSION postgis;
     */
    public function multipoint(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            $type = 'MULTIPOINT';
        } elseif (Memory::$config['driver'] == 'pgsql') {
            $type = 'GEOMETRY(MULTIPOINT)';
        } else {
            return $this->typeError('multipoint');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    /**
     * In Postgre install the PostGIS extension: CREATE EXTENSION postgis;
     */
    public function linestring(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            $type = 'LINESTRING';
        } elseif (Memory::$config['driver'] == 'pgsql') {
            $type = 'GEOMETRY(LINESTRING)';
        } else {
            return $this->typeError('linestring');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    /**
     * In Postgre install the PostGIS extension: CREATE EXTENSION postgis;
     */
    public function multilinestring(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            $type = 'MULTILINESTRING';
        } elseif (Memory::$config['driver'] == 'pgsql') {
            $type = 'GEOMETRY(MULTILINESTRING)';
        } else {
            return $this->typeError('multilinestring');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }


    /**
     * In Postgre install the PostGIS extension: CREATE EXTENSION postgis;
     */
    public function polygon(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            $type = 'POLYGON';
        } elseif (Memory::$config['driver'] == 'pgsql') {
            $type = 'GEOMETRY(POLYGON)';
        } else {
            return $this->typeError('polygon');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    /**
     * In Postgre install the PostGIS extension: CREATE EXTENSION postgis;
     */
    public function multipolygon(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            $type = 'MULTIPOLYGON';
        } elseif (Memory::$config['driver'] == 'pgsql') {
            $type = 'GEOMETRY(MULTIPOLYGON)';
        } else {
            return $this->typeError('multipolygon');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    /**
     * In Postgre install the PostGIS extension: CREATE EXTENSION postgis;
     */
    public function geometrycollection(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            $type = 'GEOMETRYCOLLECTION';
        } elseif (Memory::$config['driver'] == 'pgsql') {
            $type = 'GEOMETRY(GEOMETRYCOLLECTION)';
        } else {
            return $this->typeError('geometrycollection');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function boolean(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $type = 'BOOLEAN';
        } else {
            return $this->typeError('boolean');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function timestamp(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $type = 'timestamp';
        } else {
            return $this->typeError('timestamp');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function date(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $type = 'DATE';
        } else {
            return $this->typeError('date');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function datetime(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            $type = 'DATETIME';
        } elseif (Memory::$config['driver'] == 'pgsql') {
            $type = 'TIMESTAMPTZ';
        } else {
            return $this->typeError('datetime');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function year(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            $type = 'YEAR';
        } else if (Memory::$config['driver'] == 'pgsql') {
            $type = 'INT';
        } else {
            return $this->typeError('year');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function time(string $name)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $type = 'TIME';
        } else {
            return $this->typeError('time');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function set(string $name, array $values)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {

            foreach ($values as $key => $value) $values[$key] = "'{$value}'";

            $type = sprintf('SET(%s)', implode(',', $values));
        } else {
            return $this->typeError('set');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function enum(string $name, array $values)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {

            foreach ($values as $key => $value) $values[$key] = "'{$value}'";

            $type = sprintf('ENUM(%s)', implode(',', $values));
        } else {
            return $this->typeError('enum');
        }

        Memory::addColumn($name, $type);

        return new TableOptions(Memory::$config['driver']);
    }

    public function foreign(string $column, string $reference_table, string $reference_column)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            Memory::$foreigns[] = [
                'column' => $column,
                'reference_table' => $reference_table,
                'reference_column' => $reference_column,
                'name' => null,
                'on_delete' => null,
                'on_update' => null
            ];
        } else {
            return $this->typeError('foreign');
        }

        return new ForeignOptions(Memory::$config['driver']);
    }

    /**
     * Columns createdAt and updatedAt
     */
    public function dates()
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $this->timestamp('created_at')->default('CURRENT_TIMESTAMP', true)->nullable(false);
            $this->timestamp('update_at')->default('CURRENT_TIMESTAMP', true)->onUpdate('CURRENT_TIMESTAMP', true)->nullable(false);
        } else {
            return $this->typeError('dates');
        }
    }

    /**
     * BTREE | RTREE | FULLTEXT | HASH
     */
    public function index(string|array $column, string|null $name = null, string $type = 'BTREE')
    {
        if ($name === null) $name = 'IDX_' . $type .  '_' . Memory::$table . '_' . implode('_',  is_string($column) ? [$column]  : $column);

        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            Memory::$indexs[$name] = [
                'name' => $name,
                'type' => $type,
                'column' => $column
            ];
        } else {
            return $this->typeError('index');
        }
    }

    public function rename(string $column, string $to)
    {
        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            Memory::$renames[$column] = $to;
        } else {
            return $this->typeError('rename');
        }
    }

    public function dropPrimaryKey(bool $drop = true)
    {
        Memory::$dropPrimaryKey = $drop;
    }

    public function dropConstrant(string|array $name)
    {
        if (is_string($name)) $name = [$name];

        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            Memory::$dropConstraints = array_merge($name, Memory::$dropConstraints);
        } else {
            return $this->typeError('dropConstrant');
        }
    }

    public function dropColumn(string|array $column)
    {
        if (is_string($column)) $column = [$column];

        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            Memory::$dropColumns = array_merge($column, Memory::$dropColumns);
        } else {
            return $this->typeError('dropColumns');
        }
    }

    public function dropIndex(string|array $name)
    {
        if (is_string($name)) $name = [$name];

        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            Memory::$dropIndexs = array_merge($name, Memory::$dropIndexs);
        } else {
            return $this->typeError('dropIndexs');
        }
    }

    private function typeError(string $type)
    {
        throw new InvalidArgumentException('Driver does not support the type ' . $type);
    }
}
