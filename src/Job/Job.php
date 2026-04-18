<?php

namespace Baseons\Job;

class Job
{
    private static int $group = 0;
    private static array $attributes = [];

    /**
     * Specific date
     * @param string $date H:i d/m/Y
     */
    public static function date(string $date, string|array|callable|null $action = null)
    {
        $valid = date('H:i d/m/Y') == $date;

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    /**
     * If in values as date
     */
    public static function raw(string|array $values, string $date, string|array|callable|null $action = null)
    {
        $valid = false;

        if (is_string($values)) $values = [$values];

        foreach ($values as $value) {
            if ($value == $date) {
                $valid = true;
                break;
            }
        }

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    /**
     * Every minute
     * @param string $interval range 1 - 59
     */
    public static function everyMinute(int $interval, string|array|callable|null $action = null)
    {
        if ($interval > 59) $interval = 59;

        $max = 60;
        $valid = false;

        while ($max > 0) {
            $max -= $interval;

            if ($max == date('i')) {
                $valid = true;
                break;
            };
        }

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    /**
     * Every hour
     * @param string $interval range 1 - 23
     */
    public static function everyHour(int $interval, string|array|callable|null $action = null)
    {
        if ($interval > 23) $interval = 23;

        $max = 24;
        $valid = false;

        while ($max > 0) {
            $max -= $interval;

            if (date('i') !== '00') break;

            if ($max == date('G')) {
                $valid = true;
                break;
            };
        }

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    /**
     * Every day at
     * @param string|int $hours H:i or H
     */
    public static function everyDayAt(string|int $hours, string|array|callable|null $action = null)
    {
        $valid = false;

        if (str_contains($hours, ':')) {
            if (date('H:i') == $hours) $valid = true;
        } else {
            if (date('H') == $hours and date('i') == '00') $valid = true;
        }

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    /**
     * Day at
     * @param int|array $days d or [d, ...]
     * @param string|int $hours H:i or H
     */
    public static function dayAt(int|string|array $days, string|int $hours,  string|array|callable|null $action = null)
    {
        $valid = false;

        if (is_int($days) or is_string($days)) $days = [$days];

        foreach ($days as $day) {
            if (date('d') == $day) {
                if (str_contains($hours, ':')) {
                    if (date('H:i') == $hours) $valid = true;
                } else {
                    if (date('H') == $hours and date('i') == '00') $valid = true;
                }
            }

            if ($valid) break;
        }

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    /**
     * Sundays at
     * @param string|int $hours H:i or H
     */
    public static function sundaysAt(string|int $hours, string|array|callable|null $action = null)
    {
        $valid = false;

        if (date('l') == 'Sunday') {
            if (str_contains($hours, ':')) {
                if (date('H:i') == $hours) $valid = true;
            } else {
                if (date('H') == $hours and date('i') == '00') $valid = true;
            }
        }

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    /**
     * Mondays at
     * @param string|int $hours H:i or H
     */
    public static function mondaysAt(string|int $hours, string|array|callable|null $action = null)
    {
        $valid = false;

        if (date('l') == 'Monday') {
            if (str_contains($hours, ':')) {
                if (date('H:i') == $hours) $valid = true;
            } else {
                if (date('H') == $hours and date('i') == '00') $valid = true;
            }
        }

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    /**
     * Tuesdays at
     * @param string|int $hours H:i or H
     */
    public static function tuesdaysAt(string|int $hours, string|array|callable|null $action = null)
    {
        $valid = false;

        if (date('l') == 'Tuesday') {
            if (str_contains($hours, ':')) {
                if (date('H:i') == $hours) $valid = true;
            } else {
                if (date('H') == $hours and date('i') == '00') $valid = true;
            }
        }

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    /**
     * Wednesdays at
     * @param string|int $hours H:i or H
     */
    public static function wednesdaysAt(string|int $hours, string|array|callable|null $action = null)
    {
        $valid = false;

        if (date('l') == 'Wednesday') {
            if (str_contains($hours, ':')) {
                if (date('H:i') == $hours) $valid = true;
            } else {
                if (date('H') == $hours and date('i') == '00') $valid = true;
            }
        }

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    /**
     * Thursdays at
     * @param string|int $hours H:i or H
     */
    public static function thursdaysAt(string|int $hours, string|array|callable|null $action = null)
    {
        $valid = false;

        if (date('l') == 'Thursday') {
            if (str_contains($hours, ':')) {
                if (date('H:i') == $hours) $valid = true;
            } else {
                if (date('H') == $hours and date('i') == '00') $valid = true;
            }
        }

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    /**
     * Fridays at
     * @param string|int $hours H:i or H
     */
    public static function fridaysAt(string|int $hours, string|array|callable|null $action = null)
    {
        $valid = false;

        if (date('l') == 'Friday') {
            if (str_contains($hours, ':')) {
                if (date('H:i') == $hours) $valid = true;
            } else {
                if (date('H') == $hours and date('i') == '00') $valid = true;
            }
        }

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    /**
     * Saturdays at
     * @param string|int $hours H:i or H
     */
    public static function saturdaysAt(string|int $hours, string|array|callable|null $action = null)
    {
        $valid = false;

        if (date('l') == 'Saturday') {
            if (str_contains($hours, ':')) {
                if (date('H:i') == $hours) $valid = true;
            } else {
                if (date('H') == $hours and date('i') == '00') $valid = true;
            }
        }

        JobMemory::job($action, $valid);

        return new JobOptions;
    }

    public static function name(string $value)
    {
        JobMemory::setAttribute('name', $value);
        self::$attributes[self::$group][] = 'name';

        return new self;
    }

    public static function namespace(string $value)
    {
        JobMemory::setAttribute('namespace', trim($value, '\\'));
        self::$attributes[self::$group][] = 'namespace';

        return new self;
    }

    /**
     * Timeout kill
     */
    public static function timeout(int|false $minutes)
    {
        JobMemory::setAttribute('timeout', $minutes);
        self::$attributes[self::$group][] = 'timeout';

        return new self;
    }

    /**
     * Single process, waits for the previous process to finish before starting another one
     */
    public static function unique(bool $unique = true)
    {
        JobMemory::setAttribute('unique', $unique);
        self::$attributes[self::$group][] = 'unique';

        return new self;
    }

    public static function group(callable $routes)
    {
        $group = self::$group;

        self::$group++;

        if (is_callable($routes)) call_user_func($routes, $group);

        foreach (self::$attributes[$group] as $name) JobMemory::removeAttribute($name);

        unset(self::$attributes[$group]);
    }
}
