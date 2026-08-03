<?php

namespace Baseons\Test;

class Test
{
    public function assert(mixed $value, mixed $is)
    {
        $key_class = array_key_last(Controller::$results);
        $key_method = array_key_last(Controller::$results[$key_class]);

        return Controller::$results[$key_class][$key_method]['asserts'][] = $value === $is;
    }

    public function assertIn(mixed $value, array $is)
    {
        $key_class = array_key_last(Controller::$results);
        $key_method = array_key_last(Controller::$results[$key_class]);

        return Controller::$results[$key_class][$key_method]['asserts'][] = in_array($value, $is);
    }

    public function request(string $url)
    {
        return http($url);
    }
}
