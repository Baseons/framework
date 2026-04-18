<?php

namespace Baseons\Collections;

use DateTime;

class Validator
{
    private array $errors = [];
    private array $data = [];
    private array $nullables = [];

    public function __construct(array|null $data, array|null $availables = null)
    {
        if (is_null($data)) return $this;

        if (is_array($availables)) $data = array_intersect_key($data, array_flip($availables));

        $this->data = $data;

        return $this;
    }

    public function nullable(string|int|array $name)
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) if (!in_array($n, $this->nullables)) $this->nullables[] = $n;

        return $this;
    }

    public function required(string|int|array $name, string $message = 'required')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if (!$this->has($n)) {
                $this->error($n, $message);
            } else if (gettype($this->data[$n]) === 'string') {
                if (!strlen($this->data[$n])) $this->error($n, $message);
            } elseif (!is_numeric($this->data[$n]) and !is_bool($this->data[$n]) and empty($this->data[$n])) {
                $this->error($n, $message);
            }
        }

        return $this;
    }

    public function string(string|int|array $name, string $message = 'string')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and gettype($this->data[$n]) !== 'string') $this->error($n, $message);
        }

        return $this;
    }

    public function integer(string|int|array $name, string $message = 'integer')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n)) {
                if (filter_var($this->data[$n], FILTER_VALIDATE_INT) === false) $this->error($n, $message);
                else  $this->data[$n] = (int)$this->data[$n];
            }
        }

        return $this;
    }

    public function float(string|int|array $name, string $message = 'float')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and gettype($this->data[$n]) !== 'float') $this->error($n, $message);
            else  $this->data[$n] = (float)$this->data[$n];
        }

        return $this;
    }


    public function numeric(string|int|array $name, string $message = 'float')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and !is_numeric($this->data[$n])) $this->error($n, $message);
        }

        return $this;
    }

    public function boolean(string|int|array $name, string $message = 'boolean')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if (!$this->has($n)) continue;

            if ($this->data[$n] == 1 or $this->data[$n] === 'on' or $this->data[$n] === 'true') $this->data[$n] = true;
            if ($this->data[$n] == 0 or $this->data[$n] === 'off' or $this->data[$n] === 'false') $this->data[$n] = false;

            if (gettype($this->data[$n]) !== 'boolean') $this->error($n, $message);
        }

        return $this;
    }

    public function array(string|int|array $name, string $message = 'array')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and gettype($this->data[$n]) !== 'array') $this->error($n, $message);
        }

        return $this;
    }

    public function object(string|int|array $name, string $message = 'object')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and gettype($this->data[$n]) !== 'object') $this->error($n, $message);
        }

        return $this;
    }

    public function onlyLeters(string|int|array $name, string $message = 'only leters')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and is_numeric(filter_var($this->data[$n], FILTER_SANITIZE_NUMBER_INT))) $this->error($n, $message);
        }

        return $this;
    }

    public function minLeters(string|int|array $name, int $leters, string $message = 'min leters')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and gettype($this->data[$n]) === 'string' and strlen($this->data[$n]) < $leters) $this->error($n, $message);
        }

        return $this;
    }

    public function maxLeters(string|int|array $name, int $leters, string $message = 'max leters')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and gettype($this->data[$n]) === 'string' and strlen($this->data[$n]) > $leters) $this->error($n, $message);
        }

        return $this;
    }

    public function url(string|int|array $name, string $message = 'url')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and !str()->isUrl($this->data[$n])) $this->error($n, $message);
        }

        return $this;
    }

    public function email(string|int|array $name, string $message = 'email')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and !str()->isEmail($this->data[$n])) $this->error($n, $message);
        }

        return $this;
    }

    public function cpf(string|int|array $name, string $message = 'cpf')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and (gettype($this->data[$n]) !== 'string' or !str()->isCPF($this->data[$n]))) $this->error($n, $message);
        }

        return $this;
    }

    public function cnpj(string|int|array $name, string $message = 'cnpj')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and (gettype($this->data[$n]) !== 'string' or !str()->isCNPJ($this->data[$n]))) $this->error($n, $message);
        }

        return $this;
    }

    public function ipv4(string|int|array $name, string $message = 'ipv4')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and !str()->isIPV4($this->data[$n])) $this->error($n, $message);
        }

        return $this;
    }

    public function ipv6(string|int|array $name, string $message = 'ipv6')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and !str()->isIPV6($this->data[$n])) $this->error($n, $message);
        }

        return $this;
    }

    public function date(string|int|array $name, string $message = 'date', string $format = 'Y-m-d H:i:s')
    {
        if (!is_array($name)) $name = [$name];

        foreach ($name as $n) {
            if ($this->has($n) and (gettype($this->data[$n]) !== 'string' or !str()->isDateFormat($this->data[$n], $format))) $this->error($n, $message);
        }

        return $this;
    }

    public function addError(string|int|array $name, string $message = 'error')
    {
        foreach ($name as $n) $this->error($n, $message);
    }

    public function getErrorFirst()
    {
        $this->load();

        if (count($this->errors)) return $this->errors[array_key_first($this->errors)][0];

        return null;
    }

    public function getErrors()
    {
        $this->load();

        return $this->errors;
    }

    public function getData()
    {
        return $this->data;
    }

    // privates
    private function load()
    {
        foreach ($this->nullables as $nullable) {
            if (array_key_exists($nullable, $this->errors) and array_key_exists($nullable, $this->data) and ($this->data[$nullable] === '' or $this->data[$nullable] === null)) {
                unset($this->errors[$nullable]);
            }
        }
    }

    private function error(string|int $name, string $message)
    {
        if (!array_key_exists($name, $this->errors)) $this->errors[$name] = [];

        $this->errors[$name][] = $message;
    }

    private function has(string|int $name)
    {
        return array_key_exists($name, $this->data);
    }
}
