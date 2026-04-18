<?php

namespace Baseons\Console\Commands;

use Baseons\Collections\Log;
use Baseons\Server\Controller;
use Baseons\Server\Http\HttpServer;
use Baseons\Server\WebSocket\WebSocketServer;
use Baseons\Shell\Shell;
use Throwable;

class CommandServer
{
    public function start(string|null $name = null)
    {
        if (!extension_loaded('swoole')) {
            Shell::red('swoole extension not loaded')->br();

            return;
        }

        $controller = new Controller();
        $started = false;

        foreach ($controller->list() as $type => $servers) foreach ($servers as $server) {
            if ($name === null or $name === $server['id'] or (!empty($server['name']) and $name and $name == $server['name'])) {
                $started = true;

                $start = Shell::normal($type, true, false);
                $start .= Shell::gray($server['alias'] . ':' . $server['port'], false, false);

                if (!empty($server['name'])) $start .= Shell::gray(' - ' . $server['name'], false, false);

                if (!ping($server['host'], $server['port'])) {
                    $end = $controller->start($server['id']) ? Shell::green('STARTED', true, false) : Shell::red('FAILED', true, false);
                } else {
                    $end = Shell::red('FAILED', true, false);
                }

                Shell::list($start, $end)->br();
            }
        }

        if (!$started and $name === null) Shell::red('no server offline')->br();
        elseif (!$started and $name !== null) Shell::red('server ' . $name . ' not found')->br();
    }

    public function restart(string|null $name = null)
    {
        if (!extension_loaded('swoole')) {
            Shell::red('swoole extension not found')->br();

            return;
        }

        $controller = new Controller();
        $count = 0;

        $controller->restart($name, function ($server, $status, $type) use (&$count) {
            $count++;

            $start = Shell::normal($type, true, false);
            $start .= Shell::gray($server['alias'] . ':' . $server['port'], false, false);

            if (!empty($server['name'])) $start .= Shell::gray(' - ' . $server['name'], false, false);

            if ($status == 'restarted') {
                $end = Shell::green('RESTARTED', true, false);
            } elseif ($status == 'started') {
                $end = Shell::green('STARTED', true, false);
            } else {
                $end = Shell::red('FAILED', true, false);
            }

            Shell::list($start, $end)->br();
        });

        if (!$count and $name !== null) Shell::red('server ' . $name . ' not found')->br();
        elseif (!$count) Shell::red('no servers')->br();
    }

    public function stop(string|null $name = null)
    {
        $controller = new Controller();
        $stopped = false;

        foreach ($controller->list() as $type => $servers) foreach ($servers as $server) {
            if ($name === null or $name === $server['id'] or (!empty($server['name']) and $name and $name == $server['name'])) {
                $online = ping($server['alias'], $server['port']);

                $stopped = true;

                $start = Shell::normal($type, true, false);
                $start .= Shell::gray($server['alias'] . ':' . $server['port'], false, false);

                if (!empty($server['name'])) $start .= Shell::gray(' - ' . $server['name'], false, false);

                if ($online) {
                    $end = $controller->stop($server['id']) ? Shell::red('STOPPED', true, false) : Shell::red('FAILED', true, false);
                } else {
                    $end = Shell::red('FAILED', true, false);
                }

                Shell::list($start, $end)->br();
            }
        }

        if (!$stopped and $name === null) Shell::red('no server online')->br();
        elseif (!$stopped and $name !== null) Shell::red('server ' . $name . ' not found')->br();
    }

    public function list()
    {
        $controller = new Controller();
        $count = 0;

        foreach ($controller->list() as $type => $servers) foreach ($servers as $server) {
            $count++;

            try {
                $fp = fsockopen($server['alias'], $server['port'], $errno, $errstr, 2);

                if ($fp) {
                    fclose($fp);

                    $online = true;
                } else {
                    $online = false;
                }
            } catch (Throwable $th) {
                $online = false;
            }

            $start = Shell::normal($type, true, false);
            $start .= Shell::gray($server['alias'] . ':' . $server['port'], false, false);

            if (!empty($server['name'])) $start .= Shell::gray(' - ' . $server['name'], false, false);

            if ($online) {
                $end = Shell::green('ONLINE', true, false);
            } else {
                $end = Shell::red('OFFLINE', true, false);
            }

            Shell::list($start, $end)->br();
        }

        if (!$count) return Shell::red('no servers found')->br();
    }

    public function run(string $name)
    {
        if (!extension_loaded('swoole')) {
            Shell::red('swoole extension not found')->br();

            return;
        }

        try {
            $controller = new Controller();

            foreach ($controller->list() as $type => $servers) foreach ($servers as $server) {
                if ($name === null or $name === $server['id'] or (!empty($server['name']) and $name and $name == $server['name'])) return $this->execute($type, $server);
            }

            Shell::red('server ' . $name[0] . ' not found')->br();
        } catch (Throwable $error) {
            $error = "{$error->getFile()} {$error->getLine()} - {$error->getMessage()}";

            Shell::red($error)->br();

            Log::create('frameowrk', $error);
        }
    }

    protected function execute(string $type, array $params)
    {
        if ($type == 'websocket') (new WebSocketServer)->run($params);
        elseif ($type == 'http') (new HttpServer)->run($params);
    }
}
