<?php

namespace Baseons\Collections;

use Throwable;

class Log
{
    /**
     * Register new log
     * @return bool
     */
    public static function create(string $name, string|Throwable $value)
    {
        $storage = storage();
        $path = $storage->path()->storage('logs/' . $name . '.log');

        $content = '[' . date('d/m/Y H:i:s') . '] ';
        $content .= is_string($value) ?  $value . PHP_EOL : $value->__toString() . PHP_EOL;

        return $storage->writeFile($path, $content);
    }

    /**
     * Clean log file
     * @param $logs null Clear all logs
     * @return bool
     */
    public static function clean(string|array|null $logs = null)
    {
        if ($logs === null) {
            $files = storage()->getFiles(path()->storage('logs'));

            if (is_array($files)) foreach ($files as $path) storage()->makeFile($path);

            return;
        }

        if (is_string($logs)) $logs = [$logs];

        foreach ($logs as $log) {
            $path = path()->storage("logs/$log.log");

            if (!file_exists($path)) continue;
            storage()->makeFile($path);
        }

        return true;
    }
}
