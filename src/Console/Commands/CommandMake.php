<?php

namespace Baseons\Console\Commands;

use Baseons\Collections\Env;
use Baseons\Collections\Hash;
use Baseons\Database\DB;
use Baseons\Shell\Shell;

class CommandMake
{
    public function migration(string $name, string|null $table = null)
    {
        $name = date('Y_m_d_His') . '_' . str_replace(['/', '\\'], '_', $name);
        $params = $this->params($name, path('database/migrations'));

        if (file_exists($params['path'])) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();
                return;
            }
        }

        $template = view('migration', [
            'table' => $table
        ], false, path()->framework('Templates'));

        storage()->makeFile($params['path'], $template);

        if (file_exists($params['path'])) {
            Shell::green("migration {$params['name']} created")->normal($params['path'])->br();
        } else {
            Shell::red('failed to create migration');
        }
    }

    public function seed(string $name)
    {
        $name = str_replace(['/', '\\'], '_', $name);
        $params = $this->params($name, path('database/seeders'));

        if (file_exists($params['path'])) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();
                return;
            }
        }

        $template = view('seed', [], false, path()->framework('Templates'));

        storage()->makeFile($params['path'], $template);

        if (file_exists($params['path'])) {
            Shell::green("seed {$params['name']} created")->normal($params['path'])->br();
        } else {
            Shell::red('failed to create seed');
        }
    }

    public function key()
    {
        $create = true;

        if (!empty(env('APP_KEY'))) {
            $create = false;
            Shell::red('update current APP_KEY ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();
                return;
            }
        }

        $result = Env::updateOrChange('APP_KEY', 'base64:' . base64_encode(Hash::createKey()));

        if ($result) Shell::green('APP_KEY ' . ($create ? 'created' : 'updated'))->br();
        else Shell::red('failed to ' . ($create ? 'created' : 'updated') . ' APP_KEY')->br();
    }

    public function env()
    {
        $path = path('.env');

        if (file_exists($path)) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();
                return;
            }
        }

        // env
        $template = view('env', [
            'app_key' => 'base64:' . base64_encode(Hash::createKey())
        ], false, path()->framework('Templates'));

        storage()->makeFile($path, $template);

        if (file_exists($path)) {
            Shell::green('env created')->normal($path)->br();
        } else {
            Shell::red('failed to create env')->br();
        }
    }

    public function model(string $name, string|null $connection = null)
    {
        $scheme = DB::scheme($connection);
        $all = strtolower($name) == '--all';

        if ($all) {
            $names = $scheme->table()->getNames() ?? [];
        } else {
            $names = [$name];
        }

        if (count($names)) {
            foreach ($names as $name) {
                if ($name == 'migrations') continue;

                $params = $this->params($name, path('app/Models'));

                if (file_exists($params['path'])) {
                    if ($all) continue;

                    Shell::red('replace current file ? (y/n)');

                    $response = Shell::readline();

                    if (strtolower($response) != 'y') {
                        Shell::red('operation canceled')->br();
                        return;
                    }
                }

                $primary = null;
                $columns = [];

                if ($scheme->table()->has($params['name'])) {
                    $primary = $scheme->constraint()->getPrimaryKey($params['name']);
                    $columns = $scheme->column()->getNames($params['name']);
                }

                $columns = array_map(function ($column) {
                    return "'$column'";
                }, $columns);

                $template = view('model', [
                    'connection' => $connection,
                    'name' => $params['name'],
                    'namespace' => $params['namespace'],
                    'primary' => $primary,
                    'columns' => $columns
                ], false, path()->framework('Templates'));

                storage()->makeFile($params['path'], $template);

                if (file_exists($params['path'])) {
                    Shell::green("model {$params['name']} created")->normal($params['path'])->br();
                } else {
                    Shell::red('failed to create model')->br();
                }
            }
        }
    }

    public function job(string $name)
    {
        $params = $this->params($name, path('app/Jobs'));

        if (file_exists($params['path'])) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();
                return;
            }
        }

        $template = view('job', [
            'name' => $params['name'],
            'namespace' => $params['namespace']
        ], false, path()->framework('Templates'));

        storage()->makeFile($params['path'], $template);

        if (file_exists($params['path'])) {
            Shell::green("job {$params['name']} created")->normal($params['path'])->br();
        } else {
            Shell::red('failed to create job')->br();
        }
    }

    public function web(string $name)
    {
        $params = $this->params($name, path('app/Controllers/Web'));

        if (file_exists($params['path'])) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();

                return;
            }
        }

        $template = view('web', [
            'name' => $params['name'],
            'namespace' => $params['namespace']
        ], false, path()->framework('Templates'));

        storage()->makeFile($params['path'], $template);

        if (file_exists($params['path'])) {
            Shell::green("web controller {$params['name']} created")->normal($params['path'])->br();
        } else {
            Shell::red('failed to create web controller')->br();
        }
    }

    public function api(string $name)
    {
        $params = $this->params($name, path('app/Controllers/Api'));

        if (file_exists($params['path'])) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();

                return;
            }
        }

        $template = view('api', [
            'name' => $params['name'],
            'namespace' => $params['namespace']
        ], false, path()->framework('Templates'));

        storage()->makeFile($params['path'], $template);

        if (file_exists($params['path'])) {
            Shell::green("api controller {$params['name']} created")->normal($params['path'])->br();
        } else {
            Shell::red('failed to create api controller')->br();
        }
    }

    public function ws(string $name)
    {
        $params = $this->params($name, path('app/Controllers/Server'));

        if (file_exists($params['path'])) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();

                return;
            }
        }

        $template = view('ws', [
            'name' => $params['name'],
            'namespace' => $params['namespace']
        ], false, path()->framework('Templates'));

        storage()->makeFile($params['path'], $template);

        if (file_exists($params['path'])) {
            Shell::green("websocket controller {$params['name']} created")->normal($params['path'])->br();
        } else {
            Shell::red('failed to create websocket controller')->br();
        }
    }

    public function http(string $name)
    {
        $params = $this->params($name, path('app/Controllers/Server'));

        if (file_exists($params['path'])) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();

                return;
            }
        }

        $template = view('http', [
            'name' => $params['name'],
            'namespace' => $params['namespace']
        ], false, path()->framework('Templates'));

        storage()->makeFile($params['path'], $template);

        if (file_exists($params['path'])) {
            Shell::green("http controller {$params['name']} created")->normal($params['path'])->br();
        } else {
            Shell::red('failed to create http controller')->br();
        }
    }

    public function middleware(string $name)
    {
        $params = $this->params($name, path('app/Middlewares'));

        if (file_exists($params['path'])) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();

                return;
            }
        }

        $template = view('middleware', [
            'name' => $params['name'],
            'namespace' => $params['namespace']
        ], false, path()->framework('Templates'));

        storage()->makeFile($params['path'], $template);

        if (file_exists($params['path'])) {
            Shell::green("middleware {$params['name']} created")->normal($params['path'])->br();
        } else {
            Shell::red('failed to create middleware')->br();
        }
    }

    public function class(string $name)
    {
        $params = $this->params($name, path('app/Classes'));

        if (file_exists($params['path'])) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();

                return;
            }
        }

        $template = view('class', [
            'name' => $params['name'],
            'namespace' => $params['namespace']
        ], false, path()->framework('Templates'));

        storage()->makeFile($params['path'], $template);

        if (file_exists($params['path'])) {
            Shell::green("class {$params['name']} created")->normal($params['path'])->br();
        } else {
            Shell::red('failed to create class')->br();
        }
    }

    public function mail(string $name)
    {
        $params = $this->params($name, path('app/Mails'));

        if (file_exists($params['path'])) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();

                return;
            }
        }

        $template = view('mail', [
            'name' => $params['name'],
            'namespace' => $params['namespace']
        ], false, path()->framework('Templates'));

        storage()->makeFile($params['path'], $template);

        if (file_exists($params['path'])) {
            Shell::green("mail {$params['name']} created")->normal($params['path'])->br();
        } else {
            Shell::red('failed to mail class')->br();
        }
    }

    public function console(string $name)
    {
        $params = $this->params($name, path('app/Console'));

        if (file_exists($params['path'])) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();

                return;
            }
        }

        $template = view('console', [
            'name' => $params['name'],
            'namespace' => $params['namespace']
        ], false, path()->framework('Templates'));

        storage()->makeFile($params['path'], $template);

        if (file_exists($params['path'])) {
            Shell::green("console {$params['name']} created")->normal($params['path'])->br();
        } else {
            Shell::red('failed to console class')->br();
        }
    }

    protected function params(string $name, string $directory)
    {
        $explode = explode('/', $name);

        $name = end($explode);

        unset($explode[array_key_last($explode)]);

        $namespace = implode('\\', $explode);

        if (!strlen($name)) $namespace = null;

        $directory = rtrim(path()->separator($directory . '/' . implode('/', $explode)), '/');

        return [
            'name' => $name,
            'path' => $directory . DIRECTORY_SEPARATOR . $name . '.php',
            'namespace' => $namespace
        ];
    }

    public function test(string $name)
    {
        $params = $this->params($name, path('tests'));

        if (file_exists($params['path'])) {
            Shell::red('replace current file ? (y/n)');

            $response = Shell::readline();

            if (strtolower($response) != 'y') {
                Shell::red('operation canceled')->br();

                return;
            }
        }

        $template = view('test', [
            'name' => $params['name'],
        ], false, path()->framework('Templates'));

        storage()->makeFile($params['path'], $template);

        if (file_exists($params['path'])) {
            Shell::green("test {$params['name']} created")->normal($params['path'])->br();
        } else {
            Shell::red('failed to create test')->br();
        }
    }
}
