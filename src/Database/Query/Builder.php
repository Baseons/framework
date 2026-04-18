<?php

namespace Baseons\Database\Query;

use InvalidArgumentException;

class Builder extends BuilderController
{
    /**
     * Standard env connection
     */
    protected string|null $connection = null;

    /**
     * Use a specific connection
     */
    public function connection(string|null $connection = null)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Add a "from" clause to the query
     */
    public function table(string $table, string|null $as = null)
    {
        $this->add('table', [
            'table' => $table,
            'as' => $as
        ], false);

        return $this;
    }

    /**
     * Add a raw "from" clause to the query
     */
    public function tableRaw(string $raw, array $bindparams = [])
    {
        $this->add('table_raw', [
            'raw' => $raw,
            'bindparams' => $bindparams
        ]);

        return $this;
    }

    public function resetSelect(string|array ...$columns)
    {
        unset($this->params['columns']);

        if (count($columns)) $this->select($columns);

        return $this;
    }

    /**
     * Add columns to the query
     */
    public function select(string|array ...$columns)
    {
        if (!count($columns)) throw new InvalidArgumentException('Undefined variable $columns');

        if (is_array($columns[0])) $columns = $columns[0];

        $this->add('columns', [
            'type' => 'column',
            'column' => $columns
        ]);

        return $this;
    }

    /**
     * Add a raw column clause to the query
     */
    public function selectRaw(string $raw, array $bindparams = [])
    {
        $this->add('columns', [
            'type' => 'column_raw',
            'raw' => $raw,
            'bindparams' => $bindparams
        ]);

        return $this;
    }

    /**
     * Get the "count" result of the query
     */
    public function selectCount(string|array $column, string|null $as = null)
    {
        if (is_array($column)) {
            foreach ($column as $value) $this->selectCount($value);

            return $this;
        }

        $this->add('columns', [
            'type' => 'column_count',
            'column' => $column,
            'as' => $as
        ]);

        return $this;
    }

    /**
     * Get the average of the values of a given column
     */
    public function selectAvg(string|array $column, string|null $as = null)
    {
        if (is_array($column)) {
            foreach ($column as $value) $this->selectAvg($value);

            return $this;
        }

        $this->add('columns', [
            'type' => 'column_avg',
            'column' => $column,
            'as' => $as
        ]);

        return $this;
    }

    /**
     * Get the sum of the values of a given column
     */
    public function selectSum(string|array $column, string|null $as = null)
    {
        if (is_array($column)) {
            foreach ($column as $value) $this->selectSum($value);

            return $this;
        }

        $this->add('columns', [
            'type' => 'column_sum',
            'column' => $column,
            'as' => $as
        ]);

        return $this;
    }

    /**
     * Get the minimum value of a given column
     */
    public function selectMin(string|array $column, string|null $as = null)
    {
        if (is_array($column)) {
            foreach ($column as $value) $this->selectMin($value);

            return $this;
        }

        $this->add('columns', [
            'type' => 'column_min',
            'column' => $column,
            'as' => $as
        ]);

        return $this;
    }

    /**
     * Get the maximum value of a given column
     */
    public function selectMax(string|array $column, string|null $as = null)
    {
        if (is_array($column)) {
            foreach ($column as $value) $this->selectMax($value);

            return $this;
        }

        $this->add('columns', [
            'type' => 'column_max',
            'column' => $column,
            'as' => $as
        ]);

        return $this;
    }

    /**
     * Get the year value of a given column
     */
    public function selectYear(string|array $column, string|null $as = null)
    {
        if (is_array($column)) {
            foreach ($column as $value) $this->selectYear($value);

            return $this;
        }

        $this->add('columns', [
            'type' => 'column_year',
            'column' => $column,
            'as' => $as
        ]);

        return $this;
    }

    /**
     * Get the month value of a given column
     */
    public function selectMonth(string|array $column, string|null $as = null)
    {
        if (is_array($column)) {
            foreach ($column as $value) $this->selectMonth($value);

            return $this;
        }

        $this->add('columns', [
            'type' => 'column_month',
            'column' => $column,
            'as' => $as
        ]);

        return $this;
    }

    /**
     * Get the day value of a given column
     */
    public function selectDay(string|array $column, string|null $as = null)
    {
        if (is_array($column)) {
            foreach ($column as $value) $this->selectDay($value);

            return $this;
        }

        $this->add('columns', [
            'type' => 'column_day',
            'column' => $column,
            'as' => $as
        ]);

        return $this;
    }

    /**
     * Get the hour value of a given column
     */
    public function selectHour(string|array $column, string|null $as = null)
    {
        if (is_array($column)) {
            foreach ($column as $value) $this->selectHour($value);

            return $this;
        }

        $this->add('columns', [
            'type' => 'column_hour',
            'column' => $column,
            'as' => $as
        ]);

        return $this;
    }

    /**
     * Get the minute value of a given column
     */
    public function selectMinute(string|array $column, string|null $as = null)
    {
        if (is_array($column)) {
            foreach ($column as $value) $this->selectMinute($value);

            return $this;
        }

        $this->add('columns', [
            'type' => 'column_minute',
            'column' => $column,
            'as' => $as
        ]);

        return $this;
    }

    /**
     * Get the second value of a given column
     */
    public function selectSecond(string|array $column, string|null $as = null)
    {
        if (is_array($column)) {
            foreach ($column as $value) $this->selectSecond($value);

            return $this;
        }

        $this->add('columns', [
            'type' => 'column_second',
            'column' => $column,
            'as' => $as
        ]);

        return $this;
    }

    /**
     * Add "where" clause to the query
     */
    public function where(string|callable $column, string|null $operator = null, string|null $value = null, string $boolean = 'AND')
    {
        if (is_callable($column)) {
            if ($operator) $boolean = 'AND';
        } elseif (func_num_args() === 2 and $value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->add('where', [
            'type' => 'where',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ]);

        return $this;
    }

    /**
     * Add a basic "or where" clause to the query
     */
    public function orWhere(string|callable $column, string|null $operator = null, string|null $value = null)
    {
        if (func_num_args() === 2 and $value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->add('where', [
            'type' => 'where',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => 'OR'
        ]);

        return $this;
    }

    /**
     * Add a "where column" clause to the query
     */
    public function whereColumn(string $first, string|null $operator = null, string|null $second = null, string $boolean = 'AND')
    {
        if (func_num_args() === 2 and $second === null) {
            $second = $operator;
            $operator = '=';
        }

        $this->add('where', [
            'type' => 'where_column',
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'boolean' => $boolean
        ]);

        return $this;
    }

    /**
     * Add a raw "where" clause to the query
     */
    public function whereRaw(string $raw, array $bindparams = [], string $boolean = 'AND')
    {
        $this->add('where', [
            'type' => 'where_raw',
            'operator' => false,
            'raw' => $raw,
            'boolean' => $boolean,
            'bindparams' => $bindparams
        ]);

        return $this;
    }

    /**
     * Add a "where not in" clause to the query
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND')
    {
        $this->add('where', [
            'type' => 'where_not_in',
            'operator' => false,
            'values' => $values,
            'boolean' => $boolean,
            'column' => $column
        ]);

        return $this;
    }

    /**
     * Add a "where exists" clause to the query
     */
    public function whereExists(Builder $builder, string $boolean = 'AND')
    {
        $this->add('where', [
            'type' => 'where_exists',
            'operator' => false,
            'builder' => $builder,
            'boolean' => $boolean
        ]);

        return $this;
    }

    /**
     * Add a "where exists" clause to the query
     */
    public function whereNotExists(Builder $builder, string $boolean = 'AND')
    {
        $this->add('where', [
            'type' => 'where_not_exists',
            'operator' => false,
            'builder' => $builder,
            'boolean' => $boolean
        ]);

        return $this;
    }

    /**
     * Add a "where in" clause to the query
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND')
    {
        $this->add('where', [
            'type' => 'where_in',
            'operator' => false,
            'values' => $values,
            'boolean' => $boolean,
            'column' => $column
        ]);

        return $this;
    }

    /**
     * Add a "where null" clause to the query
     */
    public function whereNull(string|array $column, string $boolean = 'AND')
    {
        if (is_string($column)) $column = explode(',', $column);

        $this->add('where', [
            'type' => 'where_null',
            'operator' => false,
            'boolean' => $boolean,
            'column' => $column
        ]);

        return $this;
    }

    /**
     * Add a "where not null" clause to the query
     */
    public function whereNotNull(string|array $column, string $boolean = 'AND')
    {
        if (is_string($column)) $column = explode(',', $column);

        $this->add('where', [
            'type' => 'where_not_null',
            'operator' => false,
            'boolean' => $boolean,
            'column' => $column
        ]);

        return $this;
    }

    /**
     * Add a "where year" statement to the query
     */
    public function whereYear(string $column, string $operator, string|null $value = null, string $boolean = 'AND')
    {
        if (func_num_args() == 2 and $value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->add('where', [
            'type' => 'where_year',
            'operator' => $operator,
            'boolean' => $boolean,
            'column' => $column,
            'year' => $value
        ]);

        return $this;
    }

    /**
     * Add a "where month" statement to the query
     */
    public function whereMonth(string $column, string $operator, string|null $value = null, string $boolean = 'AND')
    {
        if (func_num_args() == 2 and $value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->add('where', [
            'type' => 'where_month',
            'operator' => $operator,
            'boolean' => $boolean,
            'column' => $column,
            'month' => $value
        ]);

        return $this;
    }

    /**
     * Add a "where day" statement to the query
     */
    public function whereDay(string $column, string $operator, string|null $value = null, string $boolean = 'AND')
    {
        if (func_num_args() == 2 and $value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->add('where', [
            'type' => 'where_day',
            'operator' => $operator,
            'boolean' => $boolean,
            'column' => $column,
            'day' => $value
        ]);

        return $this;
    }

    /**
     * Add a "where date" statement to the query
     */
    public function whereDate(string $column, string $operator, string|null $value = null, string $boolean = 'AND')
    {
        if (func_num_args() == 2 and $value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->add('where', [
            'type' => 'where_date',
            'operator' => $operator,
            'boolean' => $boolean,
            'column' => $column,
            'date' => $value
        ]);

        return $this;
    }

    /**
     * Add a "where hour" statement to the query
     */
    public function whereHour(string $column, string $operator, string|null $value = null, string $boolean = 'AND')
    {
        if (func_num_args() == 2 and $value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->add('where', [
            'type' => 'where_hour',
            'operator' => $operator,
            'boolean' => $boolean,
            'column' => $column,
            'hour' => $value
        ]);

        return $this;
    }

    /**
     * Add a "where minute" statement to the query
     */
    public function whereMinute(string $column, string $operator, string|null $value = null, string $boolean = 'AND')
    {
        if (func_num_args() == 2 and $value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->add('where', [
            'type' => 'where_minute',
            'operator' => $operator,
            'boolean' => $boolean,
            'column' => $column,
            'minute' => $value
        ]);

        return $this;
    }

    /**
     * Add a "where second" statement to the query
     */
    public function whereSecond(string $column, string $operator, string|null $value = null, string $boolean = 'AND')
    {
        if (func_num_args() == 2 and $value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->add('where', [
            'type' => 'where_second',
            'operator' => $operator,
            'boolean' => $boolean,
            'column' => $column,
            'second' => $value
        ]);

        return $this;
    }

    /**
     * Add a "where time" statement to the query
     * @param string $time 00:00:00
     */
    public function whereTime(string $column, string $operator, string|null $value = null, string $boolean = 'AND')
    {
        if (func_num_args() == 2 and $value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->add('where', [
            'type' => 'where_time',
            'operator' => $operator,
            'boolean' => $boolean,
            'column' => $column,
            'time' => $value
        ]);

        return $this;
    }

    /**
     * Add a "where between" statement to the query
     */
    public function whereBetween(string $column, string $start, string $end, $boolean = 'AND')
    {
        $this->add('where', [
            'type' => 'where_between',
            'operator' => false,
            'boolean' => $boolean,
            'column' => $column,
            'start' => $start,
            'end' => $end
        ]);

        return $this;
    }

    /**
     * Add a "where not between" statement to the query
     */
    public function whereNotBetween(string $column, string $start, string $end, $boolean = 'AND')
    {
        $this->add('where', [
            'type' => 'where_not_between',
            'operator' => false,
            'boolean' => $boolean,
            'column' => $column,
            'start' => $start,
            'end' => $end
        ]);

        return $this;
    }

    /**
     * Add a "on" in join clause to the query
     */
    public function on(string $first, string $operator, string|null $second = null)
    {
        if ($this->last_action != 'join') throw new InvalidArgumentException('on is supported only in joins');

        if (func_num_args() == 2 and $second === null) {
            $second = $operator;
            $operator = '=';
        }

        $key = $this->lastKey('join');

        $this->params['join'][$key]['first'] = $first;
        $this->params['join'][$key]['second'] = $second;
        $this->params['join'][$key]['operator'] = $operator;

        return $this;
    }

    /**
     * Add a "join" clause to the query
     */
    public function join(string $table, string|callable $first, string|null $second = null, string $operator = '=')
    {
        $this->add('join', [
            'type' => 'join',
            'table' => $table,
            'first' => $first,
            'second' => $second,
            'operator' => $operator,
            'where' => []
        ]);

        if (is_callable($first)) executeCallable($first);

        $this->last_action = null;

        if (!is_string($this->params['join'][$this->lastKey('join')]['first'])) throw new InvalidArgumentException('add on a query join');

        return $this;
    }

    /**
     * Add a "left join" clause to the query
     */
    public function leftJoin(string $table, string|callable $first, string|null $second = null, string $operator = '=')
    {
        $this->add('join', [
            'type' => 'left_join',
            'table' => $table,
            'first' => $first,
            'second' => $second,
            'operator' => $operator,
            'where' => []
        ]);

        if (is_callable($first)) executeCallable($first);

        $this->last_action = null;

        if (!is_string($this->params['join'][$this->lastKey('join')]['first'])) throw new InvalidArgumentException('add on a query join');

        return $this;
    }

    /**
     * Add a "right join" clause to the query
     */
    public function rightJoin(string $table, string|callable $first, string|null $second = null, string $operator = '=')
    {
        $this->add('join', [
            'type' => 'right_join',
            'table' => $table,
            'first' => $first,
            'second' => $second,
            'operator' => $operator,
            'where' => []
        ]);

        if (is_callable($first)) executeCallable($first);

        $this->last_action = null;

        if (!is_string($this->params['join'][$this->lastKey('join')]['first'])) throw new InvalidArgumentException('add on a query join');

        return $this;
    }

    /**
     * Add a "cross join" clause to the query
     */
    public function crossJoin(string $table)
    {
        $this->add('join', [
            'type' => 'cross_join',
            'table' => $table,
            'operator' => false
        ]);

        return $this;
    }

    /**
     * Add a raw "join" clause to the query
     */
    public function joinRaw(string $raw, array $bindparams = [])
    {
        $this->add('join', [
            'type' => 'raw_join',
            'raw' => $raw,
            'bindparams' => $bindparams
        ]);

        return $this;
    }

    /**
     * Add a "having" clause to the query
     */
    public function having(string $having, array $bindparams = [])
    {
        $this->add('having', [
            'having' => $having,
            'bindparams' => $bindparams
        ], false);

        return $this;
    }

    /**
     * Add a "group by" clause to the query
     */
    public function groupBy(string ...$column)
    {
        if (count($column) == 0) throw new InvalidArgumentException('Undefined variable $column');

        $this->add('group', [
            'columns' => $column
        ], false);

        return $this;
    }

    /**
     * Add a "distinct" clause to the query
     */
    public function distinct()
    {
        $this->add('distinct', true, false);
        return $this;
    }

    /**
     * Add a "limit" clause to the query
     */
    public function limit(int $limit, int|null $page = null)
    {
        $this->add('limit', [
            'limit' => $limit,
            'page' => $page
        ], false);

        return $this;
    }

    /**
     * Add a "order by desc" clause to the query
     */
    public function orderByDesc(string ...$column)
    {
        if (count($column) == 0) throw new InvalidArgumentException('Undefined variable $column');

        $this->add('order', [
            'type' => 'desc',
            'column' => $column
        ]);

        return $this;
    }

    /**
     * Add a "order by asc" clause to the query
     */
    public function orderByAsc(string ...$column)
    {
        if (count($column) == 0) throw new InvalidArgumentException('Undefined variable $column');

        $this->add('order', [
            'type' => 'asc',
            'column' => $column
        ]);

        return $this;
    }

    /**
     * Add a "order by rand()" clause to the query
     */
    public function orderByRand()
    {
        $this->add('order', [
            'type' => 'rand',
            'column' => false
        ]);

        return $this;
    }

    /**
     * Add a raw "order by" clause to the query
     */
    public function orderByRaw(string $raw, array $bindparams = [])
    {
        $this->add('order', [
            'type' => 'raw',
            'raw' => $raw,
            'bindparams' => $bindparams
        ]);

        return $this;
    }

    /**
     * Add raw to the end of the query
     */
    public function raw(string $raw, array $bindparams = [])
    {
        $this->add('raw', [
            'raw' => $raw,
            'bindparams' => $bindparams
        ]);

        return $this;
    }

    /**
     * Returns all parameters passed in the query
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Returns the assembled query
     * @var string $type select|delete|update|insert
     */
    public function getQuery(string $type = 'select')
    {
        return $this->executeProcessor($type, $this->connection);
    }

    /**
     * Execute the select query
     * @return array|object|false 'fetch'
     */
    public function first(string|array ...$columns)
    {
        if (count($columns)) $this->select($columns);

        $build = $this->executeProcessor('select', $this->connection);

        return (new RunQuery)->select($build['query'], $build['bindparams'], $this->connection, false);
    }

    /**
     * Execute the select query
     * @return array|object 'fetchAll'
     */
    public function get(string|array ...$columns)
    {
        if (count($columns)) $this->select($columns);

        $build = $this->executeProcessor('select', $this->connection);

        return (new RunQuery)->select($build['query'], $build['bindparams'], $this->connection);
    }

    public function update(array $values)
    {
        $this->add('update', [
            'values' => $values
        ], false);

        $build = $this->executeProcessor('update', $this->connection);

        return (new RunQuery)->update($build['query'], $build['bindparams'], $this->connection);
    }

    /**
     * Execute the insert query
     * @return int rowCount
     */
    public function insert(array $values)
    {
        $this->add('insert', [
            'type' => 'insert',
            'values' => $values
        ], false);

        $build = $this->executeProcessor('insert', $this->connection);

        return (new RunQuery)->insert($build['query'], $build['bindparams'], $this->connection);
    }

    /**
     * Execute the insert query
     * @return int|string|false lastInsertId
     */
    public function insertGetId(array $values)
    {
        $this->add('insert', [
            'type' => 'insert',
            'values' => $values
        ], false);

        $build = $this->executeProcessor('insert', $this->connection);

        return (new RunQuery)->insert($build['query'], $build['bindparams'], $this->connection, true);
    }

    /**
     * Execute the insert query
     * @return int rowCount
     */
    public function insertIgnore(array $values)
    {
        $this->add('ignore', true, false);

        $this->add('insert', [
            'type' => 'insert',
            'values' => $values
        ], false);

        $build = $this->executeProcessor('insert', $this->connection);

        return (new RunQuery)->insert($build['query'], $build['bindparams'], $this->connection);
    }

    /**
     * Execute the insert query
     * @return int|string|false lastInsertId
     */
    public function insertIgnoreGetId(array $values)
    {
        $this->add('ignore', true, false);

        $this->add('insert', [
            'type' => 'insert',
            'values' => $values
        ], false);

        $build = $this->executeProcessor('insert', $this->connection);

        return (new RunQuery)->insert($build['query'], $build['bindparams'], $this->connection, true);
    }

    /**
     * Execute the insert query using
     * @return int|string|false lastInsertId
     */
    public function insertSelect(Builder $builder, string|array ...$columns)
    {
        if (!count($columns)) throw new InvalidArgumentException('Undefined variable $columns');

        if (is_array($columns[0])) $columns = $columns[0];

        $this->add('insert', [
            'type' => 'insert_select',
            'columns' => $columns,
            'builder' => $builder
        ], false);

        $build = $this->executeProcessor('insert', $this->connection);

        return (new RunQuery)->insert($build['query'], $build['bindparams'], $this->connection);
    }

    /**
     * Execute the delete query
     * @return int 'rowCount'
     */
    public function delete()
    {
        $build = $this->executeProcessor('delete', $this->connection);

        return (new RunQuery)->delete($build['query'], $build['bindparams'], $this->connection);
    }

    /**
     * @return array|object
     */
    public function explain()
    {
        $this->add('explain', true, false);

        $build = $this->executeProcessor('select', $this->connection);

        return (new RunQuery)->select($build['query'], $build['bindparams'], $this->connection);
    }

    /**
     * Execute the select query
     * @return int 'rowCount'
     */
    public function count()
    {
        $build = $this->executeProcessor('select', $this->connection);

        return (new RunQuery)->select($build['query'], $build['bindparams'], $this->connection, count: true);
    }
}
