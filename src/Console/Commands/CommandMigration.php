<?php

namespace Baseons\Console\Commands;

use finfo;
use Baseons\Database\Connection;
use Baseons\Database\DB;
use Baseons\Database\Migration\MigrationRunner;
use Baseons\Shell\Lines;
use Baseons\Shell\Shell;
use Baseons\Database\Migration\Scheme\Memory;
use Throwable;

class CommandMigration
{
    public function run(string|null $connection = null)
    {
        Connection::setDefault($connection);

        $migration_files = storage()->getFiles(path('database/migrations'), true);
        $updates = false;

        foreach ($migration_files as $file) {
            $migration = new MigrationRunner();
            $migration->run($file);

            foreach ($migration->getInfos() as $info) {
                $start = Shell::normal($info['migration'], true, false);
                $end = Shell::green($info['message'], true, false);

                if ($info['table']) $start .= Shell::gray('[' . $info['table'] . ']', false, false);

                Shell::list($start, $end)->br();
            }

            foreach ($migration->getErrors() as $error) {
                $start = Shell::normal($error['migration'], true, false);
                $end = Shell::red($error['message'], true, false);

                if ($error['table']) $start .= Shell::gray('[' . $error['table'] . ']', false, false);

                Shell::list($start, $end)->br();
            }

            if (count($migration->getErrors()) or count($migration->getInfos())) $updates = true;
        }

        Connection::setDefault(null);

        if (!$updates) Shell::red('no pending migrations')->br();
    }

    public function up(string $name)
    {
        $file = path('database/migrations/' . $name . '.php');

        if (!file_exists($file)) return Shell::red('migration file not found')->normal($file)->br();

        $migration = new MigrationRunner();

        $migration->run($file, true);

        $updates = false;

        foreach ($migration->getInfos() as $info) {
            $start = Shell::normal($info['migration'], true, false);
            $end = Shell::green($info['message'], true, false);

            if ($info['table']) $start .= Shell::gray('[' . $info['table'] . ']', false, false);

            Shell::list($start, $end)->br();
        }

        foreach ($migration->getErrors() as $error) {
            $start = Shell::normal($error['migration'], true, false);
            $end = Shell::red($error['message'], true, false);

            if ($error['table']) $start .= Shell::gray('[' . $error['table'] . ']', false, false);

            Shell::list($start, $end)->br();
        }

        if (count($migration->getErrors()) or count($migration->getInfos())) $updates = true;

        if (!$updates) Shell::red('no pending migration')->br();
    }

    public function down(string $name)
    {
        $file = path('database/migrations/' . $name . '.php');

        if (!file_exists($file)) return Shell::red('migration file not found')->normal($file)->br();

        $migration = new MigrationRunner();

        $migration->run($file, true, 'down');

        $updates = false;

        foreach ($migration->getErrors() as $error) {
            $start = Shell::normal($error['migration'], true, false);
            $end = Shell::red($error['message'], true, false);

            if ($error['table']) $start .= Shell::gray('[' . $error['table'] . ']', false, false);

            Shell::list($start, $end)->br();
        }

        foreach ($migration->getInfos() as $info) {
            $start = Shell::normal($info['migration'], true, false);
            $end = Shell::green($info['message'], true, false);

            if ($info['table']) $start .= Shell::gray('[' . $info['table'] . ']', false, false);

            Shell::list($start, $end)->br();
        }

        if (count($migration->getErrors()) or count($migration->getInfos())) $updates = true;

        if (!$updates) Shell::red('no pending migration')->br();
    }

    public function reset(string|null $connection = null)
    {
        Shell::red('Are you sure you want to reset the database? (y/n)');

        $response = Shell::readline();

        if (strtolower($response) != 'y') {
            Shell::red('operation canceled')->br();

            return;
        }

        Connection::setDefault($connection);

        DB::scheme($connection)->table()->dropAll();

        $this->run();

        Connection::setDefault(null);
    }

    public function seed(string|null $name)
    {
        if ($name !== null) {
            $file = path('database/seeders/' . $name . '.php');

            if (!file_exists($file)) return Shell::red('seed file not found')->normal($file)->br();

            $migration_files = [$file];
        } else {
            $migration_files = storage()->getFiles(path('database/seeders'), true) ?? [];
        }

        foreach ($migration_files as $file) {
            try {
                $seed = require $file;

                $seed->run();
            } catch (Throwable $error) {
                $e = Lines::red($error->getMessage(), false, false);

                Shell::list(str_replace('.php', '', $file), $e)->br();
            }
        }

        Shell::green('seeders run')->br();
    }

    public function list()
    {
        $files = array_map(function ($value) {
            return pathinfo($value, PATHINFO_FILENAME);
        }, storage()->getFiles(path('database/migrations'), true));

        $list = [
            'pending' => [],
            'completed' => []
        ];


        if (DB::scheme()->table()->has('migrations')) foreach (DB::table('migrations')->get('migration', 'created_at') as $migration) $list['completed'][$migration->migration] = $migration->created_at;

        foreach($files as $file) {
            if(array_key_exists($file, $list['completed'])) continue;

            $list['pending'][$file] = '???';
        }


        foreach ($list as $type => $migrations) {
            foreach ($migrations as $file => $date) {


                $start = Shell::normal($file, true, false);
                $start .= Shell::gray($date != '???' ? date('Y-m-d H:i:s', strtotime($date)) : $date, false, false);

                if (!empty($server['name'])) $start .= Shell::gray(' - ' . $server['name'], false, false);

                if ($type == 'pending') {
                    $end = Shell::red('PENDING', true, false);
                } else {
                    $end = Shell::green('COMPLETED', true, false);
                }

                Shell::list($start, $end)->br();
            }
        }
        // dd($list);
    }
}
