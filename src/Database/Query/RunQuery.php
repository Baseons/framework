<?php

namespace Baseons\Database\Query;

use Baseons\Database\Connection;
use PDO;
use PDOStatement;

class RunQuery
{    
    private function executeStatement(string $query, array $bindparams, ?string $connection): PDOStatement
    {
        $stmt = Connection::instance($connection)->prepare($query);        
        $stmt->execute($bindparams);

        return $stmt;
    }

    public function select(string $query, array $bindparams, string|null $connection, bool $all = true, $count = false, int|string|null $mode = null)
    {
        $stmt = $this->executeStatement($query, $bindparams, $connection);

        if ($count) return $stmt->rowCount();

        $mode = $mode ?? PDO::FETCH_DEFAULT;

        return $all ? $stmt->fetchAll($mode) : $stmt->fetch($mode);
    }

    public function insert(string $query, array $bindparams, string|null $connection, $get_id = false)
    {
        $stmt = $this->executeStatement($query, $bindparams, $connection);
       
        if ($get_id) return Connection::instance($connection)->lastInsertId();

        return $stmt->rowCount();
    }

    public function update(string $query, array $bindparams, string|null $connection)
    {
        return $this->executeStatement($query, $bindparams, $connection)->rowCount();
    }

    public function delete(string $query, array $bindparams, string|null $connection)
    {
        return $this->executeStatement($query, $bindparams, $connection)->rowCount();
    }
}
