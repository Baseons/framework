<?php

namespace Baseons\Database\Query;

use InvalidArgumentException;

class BuilderProcessor
{
    private array $config = [];
    private array $params = [];
    private array $bindparams = [];

    private string $table = '';
    private string $table_raw = '';
    private string $columns = '';
    private string $where = '';
    private string $limit = '';
    private string $order = '';
    private string $having = '';
    private string $group = '';
    private string $join = '';
    private string $insert = '';
    private string $update = '';
    private string $raw = '';

    private array $operators = [
        '=',
        '<',
        '>',
        '<=',
        '>=',
        '<>',
        '!=',
        '<=>',
        'like',
        'like binary',
        'not like',
        'ilike',
        '&',
        '|',
        '^',
        '<<',
        '>>',
        '&~',
        'is',
        'is not',
        'rlike',
        'not rlike',
        'regexp',
        'not regexp',
        '~',
        '~*',
        '!~',
        '!~*',
        'similar to',
        'not similar to',
        'not ilike',
        '~~*',
        '!~~*'
    ];

    private array $geospatial = [
        'POINT',
        'LINESTRING',
        'POLYGON',
        'MULTIPOINT',
        'MULTILINESTRING',
        'MULTIPOLYGON',
        'GEOMETRYCOLLECTION'
    ];

    public function __construct(array $config, array $params)
    {
        $this->config = $config;
        $this->params = $params;
    }

    private function table(array $params)
    {
        $this->table = empty($params['as']) ? $this->quotes($params['table']) : sprintf('%s AS %s', $this->quotes($params['table']), $this->quotes($params['as']));
    }

    private function tableRaw(array $params)
    {
        $table_raw = '';

        foreach ($params as $value) {
            $table_raw = " {$value['raw']}";

            if (count($value['bindparams']) > 0) {
                foreach ($value['bindparams'] as $bind)  $this->bindparams[] = $bind;
            }
        }

        $this->table_raw = trim($table_raw);
    }

    private function columns(array  $params)
    {
        $columns = [];

        foreach ($params as $value) {
            if ($value['type'] == 'column_raw') $this->addBindparams($value['bindparams']);
            else if ($value['type'] !== 'column' and empty($value['as'])) {
                $explode = preg_split('/\s+as\s+/i', $value['column'], 2);

                $value['column'] = $explode[0];
                $value['as'] = $explode[1] ?? null;
            }

            if (!empty($value['as'])) $value['as'] = $this->quotes($value['as']);

            $column = match ($value['type']) {
                'column' => array_map(function ($x) {
                    $explode = preg_split('/\s+as\s+/i', $x, 2);
                    $explode[0] = $this->quotes($explode[0]);

                    if (array_key_exists(1, $explode)) $explode[1] = $this->quotes($explode[1]);

                    return implode(' AS ', $explode);
                }, $value['column']),

                'column_raw' => $value['raw'],

                'column_count', 'column_avg', 'column_sum', 'column_min', 'column_max' => sprintf('%s(%s)%s', strtoupper(str_replace('column_', '', $value['type'])), $this->quotes($value['column']), $value['as'] ? ' AS ' . $value['as'] : ''),

                'column_year', 'column_month', 'column_day', 'column_hour', 'column_minute', 'column_second' => sprintf($this->config['driver'] == 'pgsql' ? 'EXTRACT(%s FROM %s)::int%s' : '%s(%s)%s', strtoupper(str_replace('column_', '', $value['type'])), $this->quotes($value['column']), $value['as'] ? ' AS ' . $value['as'] : ''),

                default => null
            };

            if (is_string($column)) $columns[] = $column;
            elseif (is_array($column)) $columns = array_merge($columns, $column);
        }

        $this->columns = count($columns) ? implode(', ', $columns) : '*';
    }

    private function where(array $params)
    {
        $data = [
            'where' => [],
            'bindparams' => []
        ];

        foreach ($params as $param) {
            if ($this->config['driver'] == 'pgsql' and !empty($param['operator']) and strtoupper($param['operator']) == 'LIKE') $param['operator'] = 'ILIKE';

            if (in_array($param['type'], ['where_group_start', 'where_group_end'])) {
                $data['where'] = $this->addWhere($data['where'], ($param['type'] == 'where_group_start') ? '(' : ')', $param['boolean']);

                continue;
            }

            $type = $param['type'];
            $where = '';

            if (!in_array(strtolower($param['operator']), $this->operators) and $param['operator'] != false) throw new InvalidArgumentException("Invalid operator({$param['operator']})");

            // WHERE
            if ($type == 'where') {
                $where = sprintf('%s %s ?', $this->quotes($param['column']), $param['operator']);

                $data['bindparams'][] = $param['value'];
            }

            // WHERE COLUMN
            elseif ($type == 'where_column') $where = sprintf('%s %s %s', $this->quotes($param['first']), $param['operator'], $this->quotes($param['second']));

            // WHERE BETWEEN
            elseif ($type == 'where_between') {
                $where = sprintf('%s BETWEEN ? AND ?', $this->quotes($param['column']));

                $data['bindparams'][] = $param['start'];
                $data['bindparams'][] = $param['end'];
            }

            // WHERE NOT BETWEEN
            elseif ($type == 'where_not_between') {
                $where = sprintf('%s NOT BETWEEN ? AND ?', $this->quotes($param['column']));

                $data['bindparams'][] = $param['start'];
                $data['bindparams'][] = $param['end'];
            }

            // WHERE NOT IN
            elseif ($type == 'where_not_in') {
                $binds = [];

                foreach ($param['values'] as $value) {
                    $data['bindparams'][] = $value;
                    $binds[] = '?';
                }

                $binds = implode(',', $binds);
                $where = sprintf('%s NOT IN (%s)', $this->quotes($param['column']), $binds);
            }

            // WHERE IN
            elseif ($type == 'where_in') {
                $binds = [];

                foreach ($param['values'] as $value) {
                    $data['bindparams'][] = $value;
                    $binds[] = '?';
                }

                $binds = implode(',', $binds);
                $where = sprintf('%s IN (%s)', $this->quotes($param['column']), $binds);
            }

            // WHERE EXISTS
            elseif ($type == 'where_exists') {
                $query = $param['builder']->getQuery();
                $where = sprintf('EXISTS (%s)', $query['query']);

                if (count($query['bindparams'])) $data['bindparams'] = array_merge($data['bindparams'], $query['bindparams']);
            }

            // WHERE NOT EXISTS
            elseif ($type == 'where_not_exists') {
                $select = $param['builder']->getQuery();
                $where = sprintf('NOT EXISTS (%s)', $select['query']);

                if (count($select['bindparams'])) $data['bindparams'] = array_merge($data['bindparams'], $select['bindparams']);
            }

            // WHERE NULL
            elseif ($type == 'where_null') {
                foreach ($param['column'] as $column) {
                    $data['where'] = $this->addWhere($data['where'], "{$this->quotes($column)} IS NULL", $param['boolean']);
                }

                continue;
            }

            // WHERE NOT NULL
            elseif ($type == 'where_not_null') {
                foreach ($param['column'] as $column) {
                    $data['where'] = $this->addWhere($data['where'], "{$this->quotes($column)} IS NOT NULL", $param['boolean']);
                }

                continue;
            }

            // WHERE YEAR
            elseif ($type == 'where_year') {
                if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
                    $where = sprintf('YEAR(%s) %s ?', $this->quotes($param['column']), $param['operator']);
                } elseif ($this->config['driver'] == 'pgsql') {
                    $where = sprintf('EXTRACT(YEAR FROM %s) %s ?', $this->quotes($param['column']), $param['operator']);
                }

                $data['bindparams'][] = $param['year'];
            }

            // WHERE MONTH
            elseif ($type == 'where_month') {
                if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
                    $where = sprintf('MONTH(%s) %s ?', $this->quotes($param['column']), $param['operator']);
                } elseif ($this->config['driver'] == 'pgsql') {
                    $where = sprintf('EXTRACT(MONTH FROM %s) %s ?', $this->quotes($param['column']), $param['operator']);
                }

                $data['bindparams'][] = $param['month'];
            }

            // WHERE DAY
            elseif ($type == 'where_day') {
                if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
                    $where = sprintf('DAY(%s) %s ?', $this->quotes($param['column']), $param['operator']);
                } elseif ($this->config['driver'] == 'pgsql') {
                    $where = sprintf('EXTRACT(DAY FROM %s) %s ?', $this->quotes($param['column']), $param['operator']);
                }

                $data['bindparams'][] = $param['day'];
            }

            // WHERE DATE
            elseif ($type == 'where_date') {
                if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
                    $where = sprintf('DATE(%s) %s ?', $this->quotes($param['column']), $param['operator']);
                } elseif ($this->config['driver'] == 'pgsql') {
                    $where = sprintf('%s::date %s ?', $this->quotes($param['column']), $param['operator']);
                }

                $data['bindparams'][] = date('Y-m-d', strtotime($param['date']));
            }

            // WHERE HOUR
            elseif ($type == 'where_hour') {
                if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
                    $where = sprintf('HOUR(%s) %s ?', $this->quotes($param['column']), $param['operator']);
                } elseif ($this->config['driver'] == 'pgsql') {
                    $where = sprintf('EXTRACT(HOUR FROM %s) %s ?', $this->quotes($param['column']), $param['operator']);
                }

                $data['bindparams'][] = $param['hour'];
            }

            // WHERE MINUTE
            elseif ($type == 'where_minute') {
                if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
                    $where = sprintf('MINUTE(%s) %s ?', $this->quotes($param['column']), $param['operator']);
                } elseif ($this->config['driver'] == 'pgsql') {
                    $where = sprintf('EXTRACT(MINUTE FROM %s) %s ?', $this->quotes($param['column']), $param['operator']);
                }

                $data['bindparams'][] = $param['minute'];
            }

            // WHERE SECOND
            elseif ($type == 'where_second') {
                if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
                    $where = sprintf('SECOND(%s) %s ?', $this->quotes($param['column']), $param['operator']);
                } elseif ($this->config['driver'] == 'pgsql') {
                    $where = sprintf('EXTRACT(SECOND FROM %s) %s ?', $this->quotes($param['column']), $param['operator']);
                }

                $data['bindparams'][] = $param['second'];
            }

            // WHERE TIME
            elseif ($type == 'where_time') {
                if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
                    $where = sprintf('TIME(%s) %s ?', $this->quotes($param['column']), $param['operator']);
                } elseif ($this->config['driver'] == 'pgsql') {
                    $where = sprintf('%s::time %s ?', $this->quotes($param['column']), $param['operator']);
                }

                $data['bindparams'][] = $param['time'];
            }

            // WHERE RAW
            elseif ($type == 'where_raw') {
                if (count($param['bindparams'])) $data['bindparams'] = array_merge($data['bindparams'], $param['bindparams']);

                $where = $param['raw'];
            }

            $data['where'] = $this->addWhere($data['where'], $where, $param['boolean']);
        }

        if (!empty($data['where'])) {
            $data['where'] = implode(' ', $data['where']);
            $data['where'] = preg_replace('/\(\s+/', '(', $data['where']);
            $data['where'] = preg_replace('/\s+\)/', ')', $data['where']);
        } else {
            $data['where'] = '';
        }

        return $data;
    }

    private function addWhere(array $wheres, string $where, string|null $boolean = null)
    {
        if ($boolean and (empty($wheres) or end($wheres) == '(')) $boolean = null;
        if ($boolean) $wheres[] = strtoupper($boolean);

        $wheres[] = $where;

        return $wheres;
    }

    private function addBindparams(mixed $value)
    {
        if (is_array($value)) $this->bindparams = array_merge($this->bindparams, $value);
        else $this->bindparams[] = $value;
    }

    private function join(array $params)
    {
        foreach ($params as $join) {
            if (array_key_exists('operator', $join) and !in_array(strtolower($join['operator']), $this->operators) and $join['operator'] != false) {
                throw new InvalidArgumentException("Invalid operator({$join['operator']})");
            }

            $where = '';

            if (array_key_exists('table', $join) and is_string($join['table'])) {
                $explode = preg_split('/\s+as\s+/i', $join['table'], 2);
                $join['table'] = array_key_exists(1, $explode) ? sprintf('%s AS %s', $explode[0], $explode[1]) : $this->quotes($explode[0]);
            }

            if (!empty($join['where'])) {
                $where_data = $this->where($join['where']);

                $where = ' ' . $join['where'][0]['boolean'] . ' ' . $where_data['where'];
                $this->addBindparams($where_data['bindparams']);
            }

            // JOIN
            if ($join['type'] == 'join') {
                $this->addJoin(sprintf('INNER JOIN %s ON %s %s %s%s', $join['table'], $this->quotes($join['first']), $join['operator'], $this->quotes($join['second']), $where));
            }

            // LEFT JOIN
            elseif ($join['type'] == 'left_join') {
                $this->addJoin(sprintf('LEFT JOIN %s ON %s %s %s%s', $join['table'], $this->quotes($join['first']), $join['operator'], $this->quotes($join['second']), $where));
            }

            // RIGHT JOIN
            elseif ($join['type'] == 'right_join') {
                $this->addJoin(sprintf('RIGHT JOIN %s ON %s %s %s%s', $join['table'], $this->quotes($join['first']), $join['operator'], $this->quotes($join['second']), $where));
            }

            // CROSS JOIN
            elseif ($join['type'] == 'cross_join') {
                $this->addJoin("CROSS JOIN {$join['table']}");
            }

            // RAW JOIN
            elseif ($join['type'] == 'raw_join') {
                $this->addJoin($join['raw']);

                $this->addBindparams($join['bindparams']);
            }
        }
    }

    private function addJoin(string $join)
    {
        if ($this->join == '') {
            $this->join = "$join";
        } else {
            $this->join = "{$this->join} $join";
        }

        return;
    }

    private function having(array $params)
    {
        $this->having = 'HAVING ' . $params['having'];

        if (count($params['bindparams']) > 0) {
            foreach ($params['bindparams'] as $value) $this->bindparams[] = $value;
        }

        return;
    }

    private function limit(array $params)
    {
        $page = $params['page'];
        $limit = $params['limit'];

        if ($this->config['driver'] == 'pgsql') {
            if ($page == null) {
                $this->limit = "LIMIT $limit";
            } else {
                $page = ($page - 1) * $limit;
                $this->limit = "LIMIT $limit OFFSET $page";
            }
        } else {
            if ($page == null) {
                $this->limit = "LIMIT $limit";
            } else {
                $page = ($page - 1) * $limit;
                $this->limit = "LIMIT $page,$limit";
            }
        }

        return;
    }

    private function order(array $params)
    {
        $orders = [];
        $rand = false;

        foreach ($params as $param) {
            $type = strtoupper($param['type']);

            if ($type == 'RAND') {
                $rand = true;

                continue;
            } elseif ($type == 'RAW') {
                $orders[] = $param['raw'];
                $this->addBindparams($param['bindparams']);

                continue;
            }

            $orders = array_merge($orders, array_map(function ($value) use ($type) {
                return $this->quotes($value) . ' ' . $type;
            }, $param['column']));
        }

        if ($rand) $orders[] = $this->config['driver'] == 'pgsql' ? 'RANDOM()' : 'RAND()';

        $this->order = 'ORDER BY ' . implode(', ', $orders);
    }

    private function groupBy(array $params)
    {
        $group = 'GROUP BY';

        foreach ($params['columns'] as $column) $group = "$group {$this->quotes($column)},";

        $group = trim($group, ',');
        $this->group = $group;

        return;
    }

    private function insert(array $params)
    {
        if ($params['type'] == 'insert') {
            if (empty($params['values'])) {
                $this->insert = '';
                return;
            }

            $values = '';
            $columns = '';

            if (!is_array(reset($params['values']))) $params['values'] = [$params['values']];

            foreach ($params['values'] as $array) {
                $array_values = [];
                $array_columns = [];

                foreach ($array as $key => $value) {
                    if (!in_array($key, $array_columns) and !is_numeric($key)) $array_columns[] = $this->quotes($key);

                    if (is_string($value) and preg_match_all('/\b(' . implode('|', $this->geospatial) . ')\s*\(/i', $value, $matches)) {
                        $value = str_replace($matches[0], array_map(function ($map) {
                            return str_replace(' ', '', strtoupper($map));
                        }, $matches[0]), $value);

                        $value = trim(preg_replace('/( ){2,}/', '$1', $value));

                        $array_values[] = 'ST_GeomFromText(?)';
                        $this->bindparams[] = $value;
                    } else {
                        $array_values[] = '?';
                        $this->bindparams[] = $value;
                    }
                }

                $values .=  !empty($array_values) ? ',(' . implode(',', $array_values) . ')' : '';
            }

            $values = trim($values, ',');
            $columns = !empty($array_columns) ? '(' . implode(',', $array_columns) . ')' : '';

            $this->insert =  $columns . ' VALUES ' . $values;
        }

        // Copy values other table
        elseif ($params['type'] == 'insert_select') {
            $columns = array_map(function ($value) {
                return $this->quotes($value);
            }, $params['columns']);


            $query = $params['builder']->getQuery();
            if (count($query['bindparams'])) $this->addBindparams($query['bindparams']);


            $this->insert = '(' . implode(',', $columns) . ') ' .  $query['query'];
        }

        return;
    }

    private function update(array $params)
    {
        foreach ($params['values'] as $column => $value) {
            if (is_string($value) and preg_match_all('/\b(' . implode('|', $this->geospatial) . ')\s*\(/i', $value, $matches)) {
                $value = str_replace($matches[0], array_map(function ($map) {
                    return str_replace(' ', '', strtoupper($map));
                }, $matches[0]), $value);

                $value = trim(preg_replace('/( ){2,}/', '$1', $value));

                $this->update = "{$this->update},{$this->quotes($column)} = ST_GeomFromText(?)";
                $this->bindparams[] = $value;
            } else {
                $this->update = "{$this->update},{$this->quotes($column)} = ?";
                $this->bindparams[] = $value;
            }
        }

        $this->update = 'SET ' . trim($this->update, ',');

        return;
    }

    private function raw(array $params)
    {
        foreach ($params as $value) {
            $this->raw = "{$this->raw} {$value['raw']}";

            foreach ($value['bindparams'] as $bind) $this->bindparams[] = $bind;
        }

        $this->raw = trim($this->raw);

        return;
    }

    public function query(string $command)
    {
        $query = '';
        $ignore = array_key_exists('ignore', $this->params) ? true : false;
        $explain = array_key_exists('explain', $this->params) ? 'EXPLAIN' :  '';
        $distinct = array_key_exists('distinct', $this->params) ? 'DISTINCT' :  '';

        if (array_key_exists('columns', $this->params)) $this->columns($this->params['columns']);
        else $this->columns = '*';

        if (array_key_exists('insert', $this->params)) $this->insert($this->params['insert']);
        if (array_key_exists('table', $this->params)) $this->table($this->params['table']);
        if (array_key_exists('table_raw', $this->params)) $this->tableRaw($this->params['table_raw']);
        if (array_key_exists('update', $this->params)) $this->update($this->params['update']);
        if (array_key_exists('join', $this->params)) $this->join($this->params['join']);
        if (array_key_exists('where', $this->params)) {
            $where_data = $this->where($this->params['where']);

            $this->where = 'WHERE ' . $where_data['where'];
            $this->addBindparams($where_data['bindparams']);
        }
        if (array_key_exists('group', $this->params)) $this->groupBy($this->params['group']);
        if (array_key_exists('having', $this->params)) $this->having($this->params['having']);
        if (array_key_exists('raw', $this->params)) $this->raw($this->params['raw']);
        if (array_key_exists('order', $this->params)) $this->order($this->params['order']);
        if (array_key_exists('limit', $this->params)) $this->limit($this->params['limit']);

        $table = empty($this->table_raw) ? $this->table : $this->table_raw;
        $columns = $this->columns;
        $where = $this->where;
        $having = $this->having;
        $group = $this->group;
        $join = $this->join;
        $insert = $this->insert;
        $update = $this->update;
        $raw = $this->raw;
        $limit = $this->limit;
        $order = $this->order;

        if ($command == 'select') $query = "$explain SELECT $distinct $columns FROM $table $join $where $group $having $raw $order $limit";
        elseif ($command == 'insert') $query = $this->config['driver'] == 'pgsql' ? sprintf("INSERT INTO $table $insert %s $raw", $ignore ? 'ON CONFLICT DO NOTHING' : '') : sprintf("INSERT %s INTO $table $insert $raw", $ignore ? 'IGNORE' : '');
        elseif ($command == 'update') $query = "UPDATE $table $join $update $where $raw $limit";
        elseif ($command == 'delete') $query = "DELETE FROM $table $join $where $raw $limit";

        return [
            'query' => trim(preg_replace('/( ){2,}/', '$1', $query)),
            'bindparams' => $this->bindparams
        ];
    }

    private function quotes(string $value)
    {
        $quote = $this->config['quotes'];

        return preg_replace('/(' . preg_quote($quote) . ')\s/', '$1 ', preg_replace_callback('/\b\w+\b(?!\s*\()/i', function ($matches) use ($quote) {
            return $quote . $matches[0] . $quote;
        }, $value));
    }
}
