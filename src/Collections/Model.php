<?php

namespace Baseons\Collections;

use BadMethodCallException;
use Baseons\Database\Connection;
use Baseons\Database\DB;

abstract class Model
{
    protected static string|null $connection = null;

    public static string $table;
    public static string|null $id = null;
    public static array $fillable = [];

    public static function __callStatic($name, $arguments)
    {
        $query = self::query();

        if (method_exists($query, $name)) return $query->$name(...$arguments);

        throw new BadMethodCallException("Method {$name} does not exist");
    }

    public static function query()
    {
        return DB::table(static::$table)->connection(static::$connection);
    }

    public static function create(array $data)
    {
        $data = self::dataFillable($data);

        return self::query()->insert($data);
    }

    /**
     * @return int|array|false
     */
    public static function createGetId(array $data)
    {
        $data = self::dataFillable($data);

        if (!is_array($data[array_key_first($data)])) return (int)self::query()->insertGetId($data);

        $ids = [];

        foreach ($data as $value) $ids[] = (int)self::query()->insertGetId($value);

        return $ids;
    }

    public static function createOrIgnore(array $data)
    {
        $data = self::dataFillable($data);

        return self::query()->insertIgnore($data);
    }

    public static function update(int|string|array $where, array $data)
    {
        $data = self::dataFillable($data);

        if (!count($data)) return 0;

        if (!is_array($where)) $where = [
            static::$id => $where
        ];

        $query = self::query();

        foreach ($where as $collumn => $value) {
            if (is_array($value)) $query->whereIn($collumn, $value);
            else $query->where($collumn, $value);
        }

        return $query->update($data);
    }

    public static function updateOrCreate(array $check, array $data)
    {
        $data = self::dataFillable($data);

        if (!count($data)) return 0;

        $has = self::query()->select(static::$id);

        foreach ($check as $column => $value) {
            if (is_array($value)) $has->whereIn($column, $value);
            else $has->where($column, $value);
        }

        $has = $has->get();

        if (count($has)) {
            $ids = [];

            foreach ($has as $value) $ids[] = static::toArray($value)[static::$id];

            if (count($ids)) return self::query()->whereIn(static::$id, $ids)->update($data);
        } else {
            return self::query()->insert($data);
        }

        return 0;
    }

    public static function delete(int|string|array $where)
    {
        if (!is_array($where)) $where = [
            static::$id => $where
        ];

        $query = self::query();

        foreach ($where as $collumn => $value) {
            if (is_array($value)) $query->whereIn($collumn, $value);
            else $query->where($collumn, $value);
        }

        return $query->delete();
    }

    public static function has(int|string|array $id)
    {
        if (!is_array($id)) $id = [$id];

        $total = self::query()->whereIn(static::$id, $id)->count();

        return count($id) == $total;
    }

    public static function id(int|string $id, string|array $columns = '*')
    {
        return self::query()->select($columns)->where(static::$id, $id)->first();
    }

    public static function by(string $column, string $value, string|array $columns = '*')
    {
        return self::query()->select($columns)->where($column, $value)->first();
    }

    public static function all(string|array $columns = '*')
    {
        return self::query()->select($columns)->get();
    }

    public static function count()
    {
        return self::query()->count();
    }

    private static function toArray(mixed $value)
    {
        if (is_object($value)) return get_object_vars($value);

        return $value;
    }

    private static function dataFillable(array $data)
    {
        $is_multiple = is_array($data[array_key_first($data)]);

        if (in_array('update_at', static::$fillable) and static::driver() == 'pgsql') {
            if ($is_multiple) {
                foreach ($data as $key => $value) {
                    if (is_array($value) and !array_key_exists('update_at', $value)) $data[$key]['update_at'] = date('Y-m-d H:i:s');
                }
            } else if (!array_key_exists('update_at', $data)) $data['update_at'] = date('Y-m-d H:i:s');
        }

        if (!$is_multiple) return array_intersect_key($data, array_flip(static::$fillable));

        return array_map(function ($value) {
            return array_intersect_key($value, array_flip(static::$fillable));
        }, $data);
    }

    /**
     * @return string|null
     */
    private static function driver()
    {
        $config = Connection::config(static::$connection);

        if (is_array($config) and array_key_exists('driver', $config)) return $config['driver'];

        return null;
    }
}
