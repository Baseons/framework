<?php

namespace Baseons\Server\Http;

use Baseons\Server\ServerMemory;

class HttpOptions
{
    public function workers(int $value)
    {
        $key = array_key_last(ServerMemory::$servers['http']);

        ServerMemory::$servers['http'][$key]['workers'] = $value;

        return $this;
    }

    /**
     * Set server name
     */
    public function name(string $value)
    {
        $key = array_key_last(ServerMemory::$servers['http']);

        if (empty(ServerMemory::$servers['http'][$key]['name'])) {
            ServerMemory::$servers['http'][$key]['name'] = $value;
        } else {
            ServerMemory::$servers['http'][$key]['name'] .= '.' . $value;
        }

        return $this;
    }

    public function host(string $value)
    {
        $key = array_key_last(ServerMemory::$servers['http']);

        ServerMemory::$servers['http'][$key]['host'] = $value;

        return $this;
    }

    public function alias(string $value)
    {
        $key = array_key_last(ServerMemory::$servers['http']);

        ServerMemory::$servers['http'][$key]['alias'] = $value;

        return $this;
    }
}
