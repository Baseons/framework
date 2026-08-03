<?php

namespace Baseons\Database\Migration\Scheme;

class Columns
{
    private array $config = [];
    private string|null $table = null;

    public function __construct(array $config, string $table)
    {
        $this->config = $config;
        $this->table = $table;
    }

    /**
     * @return array
     */
    public function create(string $name, string $type, array $options = [], bool $display_name = true)
    {
        $build = [
            'columns' => [],
            'others' => []
        ];

        if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
            $addons = [];

            if (array_key_exists('NULLABLE', $options)) {
                $addons['NULLABLE'] = $options['NULLABLE'] ? 'NULL' : 'NOT NULL';
            }

            if (array_key_exists('DEFAULT', $options) and $options['DEFAULT'] !== null) {
                $addons['DEFAULT'] = sprintf('DEFAULT %s', $options['DEFAULT']);
            }

            if (array_key_exists('ONUPDATE', $options) and $options['ONUPDATE']) {
                $addons['ONUPDATE'] = sprintf('ON UPDATE %s', $options['ONUPDATE']);
            }

            if (array_key_exists('COMMENT', $options) and $options['COMMENT'] !== null) {
                $addons['COMMENT'] = sprintf('COMMENT %s', "'" . $options['COMMENT'] . "'");
            }

            if (array_key_exists('POSITION', $options)) $addons['POSITION'] = $options['POSITION'];

            $column =  sprintf('%s %s %s', $display_name ? $this->quotes($name) : '', $type, implode(' ', $addons));

            $build['columns'][] = trim(preg_replace('/( ){2,}/', '$1', $column));
        } else if ($this->config['driver'] == 'pgsql') {
            $addons = [];

            if (array_key_exists('NULLABLE', $options)) {
                $addons['NULLABLE'] = $options['NULLABLE'] ? 'NULL' : 'NOT NULL';
            }

            if (array_key_exists('DEFAULT', $options) and $options['DEFAULT'] !== null) {
                $addons['DEFAULT'] = sprintf('DEFAULT %s', $options['DEFAULT']);
            }

            if (array_key_exists('COMMENT', $options) and $options['COMMENT'] !== null) {
                $build['others'][] = sprintf('COMMENT ON COLUMN %s.%s IS \'%s\'', $this->quotes($this->table), $this->quotes($name), $options['COMMENT']);
            }

            $column = sprintf('%s %s %s', $display_name ? $this->quotes($name) : '', $type, implode(' ', $addons));

            $build['columns'][] = trim(preg_replace('/( ){2,}/', '$1', $column));
        }

        return $build;
    }

    /**
     * @return array
     */
    public function edit(string $name, string $type, array $options = [], bool $display_name = true)
    {
        $build = [
            'columns' => [],
            'others' => []
        ];

        if (in_array($this->config['driver'], ['mysql', 'mariadb'])) {
            $addons = [];

            if (array_key_exists('NULLABLE', $options)) {
                $addons['NULLABLE'] = $options['NULLABLE'] ? 'NULL' : 'NOT NULL';
            }

            if (array_key_exists('DEFAULT', $options) and $options['DEFAULT'] !== null) {
                $addons['DEFAULT'] = sprintf('DEFAULT %s', $options['DEFAULT']);
            }

            if (array_key_exists('ONUPDATE', $options) and $options['ONUPDATE']) {
                $addons['ONUPDATE'] = sprintf('ON UPDATE %s', $options['ONUPDATE']);
            }

            if (array_key_exists('COMMENT', $options) and $options['COMMENT'] !== null) {
                $addons['COMMENT'] = sprintf('COMMENT %s', "'" . $options['COMMENT'] . "'");
            }

            if (array_key_exists('POSITION', $options) and $options['POSITION']) {
                $addons['POSITION'] = $options['POSITION'];
            }

            $column =  sprintf('%s %s %s', $display_name ? $this->quotes($name) : '', $type, implode(' ', $addons));

            $build['columns'][] = trim(preg_replace('/( ){2,}/', '$1', $column));
        } else if ($this->config['driver'] == 'pgsql') {
            $addons = [];

            $type = 'TYPE ' . $type;

            if (array_key_exists('NULLABLE', $options)) {
                $build['others'][] = sprintf('ALTER TABLE %s ALTER COLUMN %s %s', $this->quotes($this->table), $this->quotes($name), $options['NULLABLE'] ? 'DROP NOT NULL' : 'SET NOT NULL');
            }

            if (array_key_exists('DEFAULT', $options) and $options['DEFAULT'] !== null) {
                $build['others'][] = sprintf('ALTER TABLE %s ALTER COLUMN %s SET DEFAULT %s', $this->quotes($this->table), $this->quotes($name), $options['DEFAULT']);
            }

            if (array_key_exists('COMMENT', $options) and $options['COMMENT'] !== null) {
                $build['others'][] = sprintf('COMMENT ON COLUMN %s.%s IS \'%s\'', $this->quotes($this->table), $this->quotes($name), $options['COMMENT']);
            }

            $column = sprintf('%s %s %s', $display_name ? $this->quotes($name) : '', $type, implode(' ', $addons));

            $build['columns'][] = trim(preg_replace('/( ){2,}/', '$1', $column));
        }

        return $build;
    }

    private function quotes(string $string)
    {
        $string = preg_replace('/\b(?!as\b)(\w+)\b/i', $this->config['quotes'] . '$1' . $this->config['quotes'], $string);
        $string = preg_replace('/(' . preg_quote($this->config['quotes']) . ')\s/', '$1 ', $string);

        return $string;
    }
}
