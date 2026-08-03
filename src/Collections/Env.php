<?php

namespace Baseons\Collections;

class Env
{
    /**
     * Env values
     */
    protected static $env = [];

    /**
     * Variáveis de ambiente env
     * @return string|array|int|false|true|null
     */
    public static function value(string $name, mixed $default = null)
    {
        if (!count(self::$env)) self::envRead();

        if (array_key_exists($name, self::$env)) return self::$env[$name];

        return $default;
    }

    public static function updateOrChange(string $key, string $value, bool $first = true)
    {
        $path = path('.env');

        if (!file_exists($path)) return false;

        $fp = fopen($path, 'c+');

        if (!$fp) return false;

        flock($fp, LOCK_EX);

        $env = stream_get_contents($fp);

        rewind($fp);

        $pattern = "/^{$key}\s*=\s*.*/m";

        if (preg_match($pattern, $env)) $env = preg_replace($pattern, "{$key} = {$value}", $env);
        else $env = $first ? "{$key} = {$value}" . PHP_EOL . $env : $env . PHP_EOL . "{$key} = {$value}";

        ftruncate($fp, 0);
        fwrite($fp, $env);
        flock($fp, LOCK_UN);
        fclose($fp);

        self::envRead();

        return true;
    }
    /**
     * @return array
     */
    private static function envRead()
    {
        $path = path('.env');
        $env = [];
        self::$env = [];

        if (file_exists($path)) $env = array_filter(file($path));

        foreach ($env as $line) {
            if ($line[0] === '#') continue;

            $line = trim($line);

            if (!strlen($line)) continue;

            $item = explode('=', $line, 2);
            $name = trim($item[0]);
            $value = array_key_exists(1, $item) ? trim($item[1]) : null;

            if ($value !== null) {
                if (strlen($value) >= 2) {
                    $fisrt = $value[0];
                    $last = $value[strlen($value) - 1];

                    if ($fisrt === '"' and $last === '"') $value = trim($value, '"');
                    else if ($fisrt === "'" and $last === "'") $value = trim($value, "'");
                }

                $value = strlen($value) ? self::checkValue($value) : null;
            }

            self::$env[$name] = $value;
        }

        foreach (self::$env as $name => $value) {
            if (!$value) continue;

            $padrao = '/\${(.*?)}/';

            if (preg_match($padrao, $value, $matches)) {
                if (array_key_exists($matches[1], self::$env)) {
                    if (is_string(self::$env[$matches[1]])) self::$env[$name] = str_replace($matches[0], self::$env[$matches[1]], $value);
                    else self::$env[$name] = self::$env[$matches[1]];
                }
            }
        }
    }

    /**
     * Check value
     */
    private static function checkValue($value)
    {
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) !== false) return (int)$value;
        if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false) return (float)$value;

        return $value;
    }
}
