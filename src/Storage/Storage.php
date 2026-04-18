<?php

namespace Baseons\Storage;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Storage
{
    /**
     * @return string|Path
     */
    public static function path(string|null $path = null)
    {
        return $path === null ? new Path : Path::base($path);
    }

    /**
     * Delete directories and files
     * @return bool|int
     */
    public static function delete(string|array $path)
    {
        $multiple = false;
        $count = 0;

        if (is_string($path)) $path = [$path];
        else $multiple = true;

        foreach ($path as $value) {
            if (!file_exists($value)) continue;

            if (is_dir($value)) {
                $directory_iterator = new RecursiveDirectoryIterator($value, FilesystemIterator::SKIP_DOTS);
                $iterator = new RecursiveIteratorIterator($directory_iterator, RecursiveIteratorIterator::CHILD_FIRST);

                foreach ($iterator as $file) $file->isFile() ? unlink($file->getPathname()) : rmdir($file->getPathname());

                if (rmdir($value)) $count++;
            } elseif (unlink($value)) $count++;
        }

        if ($multiple) return $count;

        return $count ? true : false;
    }

    public static function makeDirectory(string|array $path,  int $permissions = 0777)
    {
        $multiple = false;
        $count = 0;

        if (is_string($path)) $path = [$path];
        else $multiple = true;

        foreach ($path as $value) {
            if (file_exists($value)) continue;
            if (mkdir($value, $permissions, true)) $count++;
        }

        if ($multiple) return $count;

        return $count ? true : false;
    }

    public static function makeFile(string $path, string $content = '', int $permissions = 0777)
    {
        self::makeDirectory(dirname($path));

        $exists = file_exists($path);
        $fp = fopen($path, 'w');

        while (true) {
            if (!is_resource($fp)) $fp = fopen($path, 'w');

            if (is_resource($fp) and flock($fp, LOCK_EX | LOCK_NB)) {
                fwrite($fp, $content);
                flock($fp, LOCK_UN);
                fclose($fp);

                break;
            }

            usleep(5000);
        }

        if (!$exists and file_exists($path)) chmod($path, $permissions);

        return true;
    }

    /**
     * Wwrite the file at the end
     * The file will be created if it does not exist
     */
    public static function writeFile(string $path, string $content, int $permissions = 0777)
    {
        self::makeDirectory(dirname($path));

        $exists = file_exists($path);
        $fp = fopen($path, 'a');

        while (true) {
            if (!is_resource($fp)) $fp = fopen($path, 'a');

            if (is_resource($fp) && flock($fp, LOCK_EX | LOCK_NB)) {
                fwrite($fp, $content);
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);

                break;
            }

            usleep(5000);
        }

        if (!$exists && file_exists($path)) chmod($path, $permissions);

        return true;
    }

    public static function makeJson(string $path, mixed $data = [], int $permissions = 0777)
    {
        $json = json_encode($data);

        if (!is_string($json)) return false;

        return self::makeFile($path, $json, $permissions);
    }

    public static function getJson(string $path, mixed $default = null, bool $associative = true)
    {
        if (!file_exists($path)) return $default;

        return json_decode(file_get_contents($path), $associative);
    }

    public static function getContent(string $path, mixed $default = null)
    {
        if (!file_exists($path)) return $default;

        return file_get_contents($path);
    }

    /**
     * Move or rename
     */
    public static function move(string $from, string $to)
    {
        return rename($from, $to);
    }

    public static function copy(string $from, string $to)
    {
        return copy($from, $to);
    }

    /**
     * @return array|null
     */
    public static function getFiles(string $path, bool $recursive = false)
    {
        if (!is_dir($path)) return null;

        $files = [];

        if (!$recursive) {
            if (is_dir($path))  foreach (scandir($path) as $item) {
                if ($item === '.' || $item === '..') continue;

                $caminho = $path . DIRECTORY_SEPARATOR . $item;

                if (is_file($caminho)) $files[] = realpath($caminho);
            }
        } else {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));

            foreach ($iterator as $file) if ($file->isFile()) $files[] = $file->getRealPath();
        }

        sort($files, SORT_STRING);

        return $files;
    }

    function getSize(string $path, bool $format = false)
    {
        if (!file_exists($path)) return $format ? str()->formatSize(0) : 0;
        if (is_file($path)) return $format ? str()->formatSize(filesize($path)) : filesize($path);

        $size = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) if ($file->isFile()) {
            $size += $file->getSize();
        }

        return $format ? str()->formatSize($size) : $size;
    }

    /**
     * @return array|null
     */
    public static function getDirectories(string $path, bool $recursive = false)
    {
        if (!is_dir($path)) return null;

        $directories = [];

        if (!$recursive) {
            if (is_dir($path)) {
                foreach (scandir($path) as $item) {
                    if ($item === '.' || $item === '..') continue;

                    $caminho = $path . DIRECTORY_SEPARATOR . $item;

                    if (is_dir($caminho)) $directories[] = realpath($caminho);
                }
            }
        } else {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);

            foreach ($iterator as $item) if ($item->isDir()) $directories[] = $item->getRealPath();
        }

        return $directories;
    }

    /**
     * Check is file path or file contents
     * @return string|false 'path' | 'content' | false
     */
    public static function isFilePathOrContent(string $value)
    {
        if ($value === '') return false;

        $len = strlen($value);

        if ($len > 4096 or strpos($value, "\0") !== false or strpbrk($value, "\r\n") !== false) return 'content';
        if ($len < 1024 && is_file($value)) return 'path';

        return false;
    }
}
