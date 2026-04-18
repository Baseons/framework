<?php

namespace Baseons\Database\Migration\Scheme;

class ForeignOptions
{
    /**
     * CASCADE - SET NULL - SET DEFAULT - RESTRICT - NO ACTION
     */
    public function onDelete(string $value = 'NO ACTION')
    {
        $key = array_key_last(Memory::$foreigns);

        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            Memory::$foreigns[$key]['on_delete'] = ' ON DELETE ' . $value;
        }

        return $this;
    }

    /**
     * CASCADE - SET NULL - SET DEFAULT - RESTRICT -NO ACTION
     */
    public function onUpdate(string $value = 'NO ACTION')
    {
        $key = array_key_last(Memory::$foreigns);

        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            Memory::$foreigns[$key]['on_update'] = ' ON UPDATE ' . $value;
        }

        return $this;
    }

    public function name(string $value)
    {
        $key = array_key_last(Memory::$foreigns);

        if (in_array(Memory::$config['driver'], ['mysql', 'pgsql', 'mariadb'])) {
            Memory::$foreigns[$key]['name'] = $value;
        }

        return $this;
    }
}
