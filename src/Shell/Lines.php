<?php

namespace Baseons\Shell;

class Lines
{
    public static function normal(string $value, bool $space = true, bool $echo = true)
    {
        $line = sprintf("\033[0;39m%s%s\033[0m", $value, $space ? ' ' : '');

        if (!$echo) return $line;

        echo $line;

        return new self;
    }

    public static function red(string $value, bool $space = true, bool $echo = true)
    {
        $line = sprintf("\033[0;31m%s%s\033[0m", $value, $space ? ' ' : '');

        if (!$echo) return $line;

        echo $line;

        return new self;
    }

    public static function green(string $value, bool $space = true, bool $echo = true)
    {
        $line = sprintf("\033[0;32m%s%s\033[0m", $value, $space ? ' ' : '');

        if (!$echo) return $line;

        echo $line;

        return new self;
    }

    public static function yellow(string $value, bool $space = true, bool $echo = true)
    {
        $line = sprintf("\033[0;33m%s%s\033[0m", $value, $space ? ' ' : '');

        if (!$echo) return $line;

        echo $line;

        return new self;
    }

    public static function blue(string $value, bool $space = true, bool $echo = true)
    {
        $line = sprintf("\033[0;34m%s%s\033[0m", $value, $space ? ' ' : '');

        if (!$echo) return $line;

        echo $line;

        return new self;
    }

    public static function gray(string $value, bool $space = true, bool $echo = true)
    {
        $line = sprintf("\033[0;90m%s%s\033[0m", $value, $space ? ' ' : '');

        if (!$echo) return $line;

        echo $line;

        return new self;
    }

    public static function cyan(string $value, bool $space = true, bool $echo = true)
    {
        $line = sprintf("\033[0;36m%s%s\033[0m", $value, $space ? ' ' : '');

        if (!$echo) return $line;

        echo $line;

        return new self;
    }

    public static function magenta(string $value, bool $space = true, bool $echo = true)
    {
        $line = sprintf("\033[0;35m%s%s\033[0m", $value, $space ? ' ' : '');

        if (!$echo) return $line;

        echo $line;

        return new self;
    }

    public static function br()
    {
        echo PHP_EOL;
        return new self;
    }

    public static function list(string|null $start = null, string|null $end = null, string $separator = '-', int|null $width = null)
    {
        if (is_null($width)) $width = self::width();

        $original_start = $start;
        $original_end = $end;

        if (!is_null($start)) {
            $start = trim($start);
            $original_start = self::decolorize($start);

            $width -= strlen($original_start);
        }

        if (!is_null($end)) {
            $end = trim($end);
            $original_end = self::decolorize($end);

            $width -= strlen($original_end);
        }

        $line = '';
        $points = null;

        if (!empty($original_start)) {
            $width -= 2;
            $line .= $start . ' ';
        }

        if ($width > strlen($separator)) {
            $points = str_repeat($separator, intval($width / strlen($separator)));
            $line .= self::gray($points, false, false);
        }

        if (!empty($original_end)) {
            $width -= 2;

            if ($points) $line .= ' ';

            $line .= $end;
        }

        echo $line;

        return new self;
    }

    /**
     * @return string
     */
    public static function decolorize(string $line)
    {
        if (preg_match_all('/\033\[\d+(;\d+)*m(.*?)\033\[0m/', $line, $matches)) {
            $line = str_replace($matches[0], $matches[2], $line);

            return self::decolorize($line);
        }

        return $line;
    }

    public static function width()
    {
        $length = (int)exec('tput cols') ?? 0;

        return $length;
    }
}
