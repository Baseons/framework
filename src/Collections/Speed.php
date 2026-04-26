<?php

namespace Baseons\Collections;

use Exception;

/**
 * Install extension "apcu" and enable on CLI on /etc/php/{version}/cli/conf.d/20-apcu.ini add apc.enable_cli = 1
 */
class Speed
{
    public function __construct()
    {
        // if (!extension_loaded('apcu')) throw new Exception('apcu extension not loaded');

        // if (!ini_get('apc.enable_cli')) {
        //     $ini_files = php_ini_scanned_files() ?: '';
        //     $files_array = explode(',', $ini_files);
        //     $acpu_file = preg_grep('/apcu/i', $files_array);
        //     $acpu_file = !empty($acpu_file) ? trim(reset($acpu_file)) : null;

        //     $error = 'apcu not enabled in CLI';

        //     if ($acpu_file) $error .= ', add "apc.enable_cli = 1" in file ' . $acpu_file;

        //     throw new Exception($error);
        // }

        return $this;
    }

    /**
     * @return bool|array If $key is an array, the array with existing items will be returned
     */
    public function has(string|array|int $key)
    {
        $key = $this->filterKey($key);
        $result = apcu_exists($key);

        if (is_array($result)) {
            if (!count($result)) return false;

            return $this->filterKey(array_keys($result));
        }

        return $result;
    }

    public function set(string|int $key, mixed $value = null, int|null $block = null)
    {
        $key = $this->filterKey($key);

        if ($block === null) {
            while (apcu_exists('block-' . $key)) {
                usleep(1000);
            }
        } elseif (apcu_fetch('block-' . $key) != $block) {
            return false;
        }

        return apcu_store($key, $value);
    }

    /**
     * @return mixed|null
     */
    public function get(string|int $key, int|null $block = null)
    {
        $key = $this->filterKey($key);

        if ($block === null) {
            while (apcu_exists('block-' . $key)) {
                usleep(1000);
            }
        } elseif (apcu_fetch('block-' . $key) != $block) {
            return null;
        }

        $success = false;

        $value = apcu_fetch($key, $success);

        return $success ? $value : null;
    }

    /**
     * @return bool|array If $key is an array, the array with excluded items will be returned
     */
    public function delete(string|array|int $key)
    {
        $key = $this->filterKey($key);
        $result = apcu_delete($key);

        if (is_array($key)) {
            $blockeds = true;

            while ($blockeds) {
                $blocked = false;

                foreach ($key as $value) {
                    $block = apcu_exists('block-' . $value);

                    if ($block) $blocked = true;
                }

                $blockeds = $blocked;

                usleep(1000);
            }
        } else {
            while (apcu_exists('block-' . $key)) {
                usleep(1000);
            }
        }

        if (is_array($result)) {
            if (!count($result)) return true;

            $result = $this->filterKey($result);

            return array_diff($key, $result);
        }

        return $result;
    }

    public function block(string|int $key)
    {
        $key = $this->filterKey($key);

        while (apcu_exists('block-' . $key)) {
            usleep(1000);
        }

        $token = Hash::createTokenNumeric();

        $this->set('block-' . $key, $token);

        return $token;
    }

    public function unblock(string|int $key, string $token)
    {
        $key = $this->filterKey($key);

        if (apcu_fetch('block-' . $key) == $token) {
            $this->delete('block-' . $key);

            return true;
        }

        return false;
    }

    public function blocked(string|int $key)
    {
        $key = $this->filterKey($key);

        return apcu_exists('block-' . $key) ? true : false;
    }

    public function clear()
    {
        return apcu_clear_cache();
    }

    private function filterKey(string|array|int $key)
    {
        if (is_array($key)) return array_map(function ($value) {
            if (filter_var($value, FILTER_VALIDATE_INT) !== false) return (string)$value;

            return $value;
        }, $key);

        if (filter_var($key, FILTER_VALIDATE_INT) !== false) return (string)$key;

        return $key;
    }
}
