<?php
namespace Baseons\Console;

class ConsoleOptions
{
    public function description(string $value)
    {
        $key = array_key_last(ConsoleMemory::$commands);

        ConsoleMemory::$commands[$key]['description'] = $value;

        return $this;
    }
}