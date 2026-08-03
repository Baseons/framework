<?php

namespace Baseons\Collections;

class Lang
{
    private static array $data = [];
    private static string|null $locale = null;

    public static function get(string $key, mixed $default = null)
    {
        if (self::$locale === null) self::$locale = config()->app('locale');
        if (self::$locale === null) return $default;

        $explode = explode('.', $key, 2);

        $name = $explode[0];
        $key = $explode[1] ?? null;

        if (!array_key_exists(self::$locale, self::$data)) self::$data[self::$locale] = [];

        if (!array_key_exists($name, self::$data[self::$locale])) {
            $path = path('lang/' . self::$locale . "/$name.php");

            if (!file_exists($path)) return $default;

            self::$data[self::$locale][$name] = require $path;
        }

        if ($key === null) return self::$data[self::$locale][$name];

        return arr()->get(self::$data[self::$locale][$name], $key, $default);
    }

    public static function setLocale(string $locale)
    {
        self::$locale = $locale;

        config()->set('app', 'locale', $locale);

        return new self;
    }
}
