<?php

namespace Baseons\Database\Query;

use Baseons\Database\Connection;

class BuilderController
{
    protected array $params = [];
    protected string|null $last_action = null;

    protected function add(string $action, mixed $params, bool $array = true)
    {
        if ($action == 'where' and $this->last_action == 'join' and !empty($this->params['join'][$this->lastKey($this->last_action)]['first'])) {
            if (is_callable($params['column'])) {
                $params['type'] = 'where_group_start';
                $this->params['join'][$this->lastKey($this->last_action)]['where'][] = $params;

                executeCallable($params['column']);

                $params['type'] = 'where_group_end';
                $params['boolean'] = null;
                $this->params['join'][$this->lastKey($this->last_action)]['where'][]  = $params;
            } else {
                $this->params['join'][$this->lastKey($this->last_action)]['where'][]  = $params;
            }

            return;
        }

        if ($action == 'where' and !empty($params['column']) and is_callable($params['column'])) {
            $params['type'] = 'where_group_start';
            $this->params[$action][] = $params;

            executeCallable($params['column']);

            $params['type'] = 'where_group_end';
            $params['boolean'] = null;
            $this->params[$action][] = $params;

            return;
        }

        $this->last_action = $action;

        if ($array == true) $this->params[$action][] = $params;
        else $this->params[$action] = $params;
    }

    protected function lastKey(string $action)
    {
        if (!array_key_exists($action, $this->params)) return null;

        return array_key_last($this->params[$action]);
    }

    protected function executeProcessor(string $command, string|null $connection = null)
    {
        $config = Connection::config($connection);
        $builder = new BuilderProcessor($config, $this->params);

        return $builder->query($command);
    }
}
