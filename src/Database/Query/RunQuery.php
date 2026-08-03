<?php

namespace Baseons\Database\Query;

use Baseons\Database\Connection;
use PDO;

class RunQuery
{
    public function select(string $query, array $bindparams, string|null $connection, bool $all = true, $count = false, int|string|null $mode = null)
    {
        $instance = Connection::instance($connection);
        $query = $instance->prepare($query);
        $mode = $mode !== null ? $mode : PDO::FETCH_DEFAULT;

        if (count($bindparams) > 0) {
            $count_bindparams = 1;

            foreach ($bindparams as $value) {
                $query->bindValue($count_bindparams, $value);
                $count_bindparams++;
            }
        }

        $query->execute();

        if ($count == true) return $query->rowCount();

        if ($all == true) {
            return $query->fetchAll($mode);
        } else {
            return $query->fetch($mode);
        }
    }

    public function insert(string $query, array $bindparams, string|null $connection, $get_id = false)
    {
        $instance = Connection::instance($connection);
        $query = $instance->prepare($query);

        if (count($bindparams) > 0) {
            $count_bindparams = 1;

            foreach ($bindparams as $value) {
                $query->bindValue($count_bindparams, $value);
                $count_bindparams++;
            }
        }

        $query->execute();

        if ($get_id) return $instance->lastInsertId();

        return $query->rowCount();
    }

    public function update(string $query, array $bindparams, string|null $connection)
    {
        $instance = Connection::instance($connection);
        $query = $instance->prepare($query);

        if (count($bindparams) > 0) {
            $count_bindparams = 1;

            foreach ($bindparams as $value) {
                $query->bindValue($count_bindparams, $value);
                $count_bindparams++;
            }
        }

        $query->execute();

        return $query->rowCount();
    }

    public function delete(string $query, array $bindparams, string|null $connection)
    {
        $instance = Connection::instance($connection);
        $query = $instance->prepare($query);

        if (count($bindparams) > 0) {
            $count_bindparams = 1;

            foreach ($bindparams as $value) {
                $query->bindValue($count_bindparams, $value);
                $count_bindparams++;
            }
        }

        $query->execute();

        return $query->rowCount();
    }
}
