<?php

namespace Baseons\Database\Migration\Scheme;

class TableOptions
{
    public function comment(string $value)
    {
        $key = array_key_last(Memory::$columns);

        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            Memory::$columns[$key]['options']['COMMENT'] = $value;
        }

        return $this;
    }

    public function nullable(bool $value = true)
    {
        $key = array_key_last(Memory::$columns);

        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            Memory::$columns[$key]['options']['NULLABLE'] = $value;
        } else if (Memory::$config['driver'] == 'pgsql') {
            Memory::$columns[$key]['options']['NULLABLE'] = $value;
        }

        return $this;
    }

    public function default(string $value, bool $raw = false)
    {
        $key = array_key_last(Memory::$columns);

        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            if (!$raw) $value = "'$value'";

            Memory::$columns[$key]['options']['DEFAULT'] = $value;
        }

        return $this;
    }

    public function onUpdate(string $value, bool $raw = false)
    {
        $key = array_key_last(Memory::$columns);

        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            if (!$raw) $value = "'$value'";

            Memory::$columns[$key]['options']['ONUPDATE'] = $value;
        }

        return $this;
    }

    public function unique(string|null $name = null)
    {
        $key = array_key_last(Memory::$columns);

        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            $column = Memory::$columns[$key]['name'];

            if ($name === null) $name = 'UNIQUE_' . Memory::$table . '_' . $column;

            Memory::addConstraint($name, 'UNIQUE', sprintf('(%s)', $this->quotes($column)));
        }

        return $this;
    }

    /**
     * BTREE | RTREE | FULLTEXT | HASH
     */
    public function index(string|null $name = null, string $type = 'BTREE')
    {
        $key = array_key_last(Memory::$columns);
        $column = Memory::$columns[$key]['name'];

        if ($name === null) $name = 'IDX_' . $type .  '_' . Memory::$table . '_' . $column;

        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) Memory::$indexs[$name] = [
            'name' => $name,
            'type' => $type,
            'column' => $column
        ];

        return $this;
    }

    /**
     * Valid only when editing table
     */
    public function first()
    {
        $key = array_key_last(Memory::$columns);

        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            Memory::$columns[$key]['options']['POSITION'] = 'FIRST';
        }

        return $this;
    }

    /**
     * Valid only when editing table
     */
    public function after(string $column)
    {
        $key = array_key_last(Memory::$columns);

        if (in_array(Memory::$config['driver'], ['mysql', 'mariadb'])) {
            Memory::$columns[$key]['options']['POSITION'] = sprintf('AFTER %s', $this->quotes($column));
        }

        return $this;
    }

    private function quotes(string $string)
    {
        $string = preg_replace('/\b(?!as\b)(\w+)\b/i', Memory::$config['quotes'] . '$1' . Memory::$config['quotes'], $string);
        $string = preg_replace('/(' . preg_quote(Memory::$config['quotes']) . ')\s/', '$1 ', $string);

        return $string;
    }
}
