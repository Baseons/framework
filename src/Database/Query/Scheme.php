<?php

namespace Baseons\Database\Query;

use Baseons\Database\Connection;
use Baseons\Database\Query\Scheme\Column;
use Baseons\Database\Query\Scheme\Constraint;
use Baseons\Database\Query\Scheme\Table;

class Scheme
{
    private array $config = [];

    public function __construct(string|null $connection = null)
    {
        $this->connection($connection);

        return $this;
    }

    public function connection(string|null $connection = null)
    {
        $this->config = Connection::config($connection);

        return $this;
    }

    public function table()
    {
        return new Table($this->config);
    }

    public function column()
    {
        return new Column($this->config);
    }

    public function constraint()
    {
        return new Constraint($this->config);
    }
}
