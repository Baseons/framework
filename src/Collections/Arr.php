<?php

namespace Baseons\Collections;

class Arr
{
    public static function set(array &$array, int|string|array $keys, mixed $value = null)
    {
        if (is_numeric($keys)) $keys = [(int)$keys];
        else if (!is_array($keys)) $keys = explode('.', $keys);

        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) $array[(string)$key] = [];

            $array = &$array[$key];
        }

        $array = $value;

        return true;
    }

    public static function unset(array &$array, int|string|array $keys)
    {
        if (is_numeric($keys)) $keys = [(int)$keys];
        else if (!is_array($keys)) $keys = explode('.', $keys);

        $lastKey = array_pop($keys);

        foreach ($keys as $key) {
            if (!isset($array[$key]) || !is_array($array[$key])) return false;

            $array = &$array[$key];
        }

        if (array_key_exists($lastKey, $array)) {
            unset($array[$lastKey]);

            return true;
        }

        return false;
    }

    public static function get(array $array, int|string|array $keys, mixed $default = null)
    {
        if (is_numeric($keys)) $keys = (int)[$keys];
        else if (!is_array($keys)) $keys = explode('.', $keys);

        if (!count($keys) or !array_key_exists($keys[0], $array)) return $default;

        foreach ($keys as $key) {
            if (!array_key_exists((string)$key, $array)) return $default;

            $array = $array[$key];
        };

        return $array;
    }
}
