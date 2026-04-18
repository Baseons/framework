<?php

namespace Baseons\Console\Commands;

use Baseons\Kernel;
use Baseons\Router\RouteController;
use Baseons\Shell\Shell;
use Throwable;

class CommandHttp
{
    public function run(string|null $port = null)
    {
        cli_set_process_title('Baseons Framework Web Server');

        if ($port) {
            if ((int)$port != $port or !is_numeric($port)) {
                Shell::red('the port must contain only numbers')->br();

                return;
            } else if (!$this->checkPort($port)) {
                Shell::red('port ' . $port . ' unavailable')->br();

                return;
            }
        } else {
            $port = 3000;

            while ($this->checkPort($port) == false) {

                Shell::red('port ' . $port . ' unavailable')->br();
                $port++;
            }
        }

        Shell::green('web development server enabled on')->normal('http://localhost:' . $port)->br()->br();

        $command = sprintf('php -S localhost:%s "%s"', $port, path()->framework('Collections/WebServer.php'));

        Shell::exec($command, function ($line) {
            if ($data = json_decode($line, true)) {
                if (!empty($data['file'])) {
                    $start = Shell::normal($data['date'], true, false);
                    $start .= Shell::gray(strtoupper(str()->formatSize($data['file']['size'])), false, false);

                    $end = Shell::magenta('FILE', true, false);
                    $end .= Shell::blue($data['file']['url'], false, false);

                    Shell::list($start, $end)->br();
                } elseif (!empty($data['request'])) {
                    $start = Shell::normal($data['date'], true, false);
                    $start .= Shell::gray(strtoupper(str()->formatSize($data['request']['size'] ?? 0)), false, false);

                    $end = Shell::green($data['request']['method'], true, false);
                    $end .= Shell::blue($data['request']['url'], false, false);

                    Shell::list($start, $end)->br();
                }
            }
        }, 'server', 'web development server port ' . $port);
    }

    public function list()
    {
        (new RouteController())->load();

        $routes = Kernel::getMemory('route.routes', []);
        $config = null;
        $domain = config()->app('url', '');

        foreach ($routes as $route) {
            if ($config != $route['config']) {
                if ($config) Shell::br()->br();
                Shell::yellow('config: ' . $route['config'])->br();
            }

            $route['route'] = empty($route['route']) ? $domain : $domain . '/' . $route['route'];

            $first = Shell::normal($route['route'], false, false);
            if (!empty($route['name'])) $first .= Shell::gray(' ' .$route['name'], false, false);
            $last = Shell::blue(implode(' ', $route['methods'] ?? []), true, false);

            Shell::list($first, $last);

            $config = $route['config'];
        }
    }

    protected function checkPort(int $port)
    {
        try {
            if ($socket = @fsockopen('localhost', $port, $errno, $errstr, 2)) {
                return false;
                fclose($socket);
            } else {
                return true;
            }
        } catch (Throwable) {
            return true;
        }
    }
}
