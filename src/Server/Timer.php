<?php

namespace Baseons\Server;

use Baseons\Collections\Log;
use Swoole\Timer as SwooleTimer;

class Timer
{
    protected array $ids = [];

    public function setInterval(int $milliseconds, callable $callback)
    {
        $id = SwooleTimer::tick($milliseconds, $callback);

        if ($id !== false) $this->ids[] = $id;

        return $id;
    }

    public function setTimeout(int $milliseconds, callable $callback)
    {
        $id = SwooleTimer::after($milliseconds, $callback);

        if ($id !== false) $this->ids[] = $id;

        return $id;
    }

    public function clear(int $id)
    {
        if (!in_array($id, $this->ids)) return false;

        $clear = SwooleTimer::clear($id);

        if ($clear) $this->ids = array_diff($this->ids, [$id]);

        return $clear;
    }

    public function clearAll()
    {
        foreach ($this->ids as $id) $this->clear($id);

        return count($this->ids) === 0;
    }

    public function info(int $id)
    {
        return SwooleTimer::info($id);
    }
}
