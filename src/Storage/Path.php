<?php

namespace Baseons\Storage;

class Path
{
    private static string|null $path_root = null;

    /**
     * Directory base project
     */
    public static function base(string|null $path = null)
    {
        $path = self::pathPart($path);

        $base = DIRECTORY_BASE;

        return $path === null ? $base : $base . DIRECTORY_SEPARATOR . $path;
    }

    public static function public(string|null $path = null)
    {
        $path = self::pathPart($path);

        $base = DIRECTORY_BASE . DIRECTORY_SEPARATOR . 'public';

        return $path === null ? $base : $base . DIRECTORY_SEPARATOR . $path;
    }

    public static function private(string|null $path = null)
    {
        $path = self::pathPart($path);

        $base = DIRECTORY_BASE . DIRECTORY_SEPARATOR . 'private';

        return $path === null ? $base : $base . DIRECTORY_SEPARATOR . $path;
    }

    public static function resources(string|null $path = null)
    {
        $path = self::pathPart($path);

        $base = DIRECTORY_BASE . DIRECTORY_SEPARATOR . 'resources';

        return $path === null ? $base : $base . DIRECTORY_SEPARATOR . $path;
    }

    public static function storage(string|null $path = null)
    {
        $path = self::pathPart($path);

        $base = DIRECTORY_BASE . DIRECTORY_SEPARATOR . 'storage';

        return $path === null ? $base : $base . DIRECTORY_SEPARATOR . $path;
    }

    public static function framework(string|null $path = null)
    {
        $path = self::pathPart($path);

        $base = DIRECTORY_BASEONS;

        return $path === null ? $base : $base . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * Directory root system
     */
    public static function root(string|null $path = null)
    {
        $path = self::pathPart($path);

        if (self::$path_root === null) {
            self::$path_root = DIRECTORY_SEPARATOR;

            // windows
            if (self::$path_root === '\\') {
                $dir = realpath(__DIR__);

                if (preg_match('#^[A-Za-z]:\\\\#', $dir, $m)) self::$path_root = $m[0];

                self::$path_root = substr($dir, 0, 3);
            }
        }

        if ($path === null) return self::$path_root;

        return self::$path_root . $path;
    }

    public static function separator(string $path)
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private static function pathPart(string|null $path = null)
    {
        if ($path === null || $path === '') return null;

        $path = trim($path, '/');
        $path = trim($path, '\\');

        return self::separator($path);
    }
}
