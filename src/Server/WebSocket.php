<?php

namespace Baseons\Server;

use Baseons\Collections\Speed;
use Baseons\Server\WebSocket\WebSocketData;
use Swoole\WebSocket\Server;

class WebSocket
{
    protected Server|null $server = null;
    protected int|null $fd = null;

    public function __construct(int|null $fd, Server $server)
    {
        $this->fd = $fd;
        $this->server = $server;

        return $this;
    }

    public function id()
    {
        return $this->fd;
    }

    /**
     * Get all clients ids
     */
    public function clients(bool $self = true)
    {
        $clients = [];

        foreach ($this->server->connections as $fd) {
            if (!$self and $fd === $this->fd) continue;
            if (!$this->server->isEstablished($fd)) continue;

            $clients[] = $fd;
        }

        return $clients;
    }

    /**
     * Send data
     */
    public function send(string $data, int|array|null $id = null, bool $bynary = false)
    {
        if ($id === null) {
            if ($this->fd === null) return false;
            $id = $this->fd;
        }

        $result = true;

        if (!is_array($id)) $id = [$id];
        else $result = 0;

        if (!count($id)) return $result;

        // SWOOLE_WEBSOCKET_FLAG_COMPRESS

        $opcode = $bynary ? WEBSOCKET_OPCODE_BINARY : WEBSOCKET_OPCODE_TEXT;

        foreach ($id as $fd) {
            $send = $this->server->isEstablished($fd) and $this->server->push($fd, $data, $opcode);

            if (is_numeric($result)) $result = $send ? $result + 1 : $result;
            else $result = $send;
        }

        return is_numeric($result) and !$result ? false : $result;
    }

    /**
     * Get client info
     * @return array|null
     */
    public function clientGetInfo(int|null $id = null)
    {
        if ($id === null) {
            if ($this->fd === null) return null;
            $id = $this->fd;
        }

        $info = $this->server->getClientInfo($id);

        if (!$info) return null;

        return [
            'id' => $id,
            'remote_port' => $info['remote_port'],
            'remote_ip' => $info['remote_ip'],
            'connect_time' => $info['connect_time'],
            'last_time' => $info['last_time'],
            'last_recv_time' => $info['last_recv_time'],
            'last_send_time' => $info['last_send_time'],
        ];
    }

    /**
     * Temporary client session data
     * @param int|null $timeout set timeout in milliseconds to lock file for reading and writing, default infinity.
     * @return WebSocketData|null
     */
    public function clientStorage(int|null $id = null, int|null $timeout = null)
    {
        if ($id === null) {
            if ($this->fd === null) return null;
            $id = $this->fd;
        }

        if ($this->server->isEstablished($id)) {
            $path = path()->storage('cache/servers/' . $this->pid() . '/' . $id);

            return new WebSocketData($path, $timeout);
        }

        return null;
    }

    /**
     * Temporary server data
     * @param int|null $timeout set timeout in milliseconds to lock file for reading and writing, default infinity.
     * @return WebSocketData|null
     */
    public function serverStorage(int|null $timeout = null)
    {
        $path = path()->storage('cache/servers/' . $this->pid() . '/server');

        return new WebSocketData($path, $timeout);
    }

    /**
     * Install extension "apcu" and enable on CLI on /etc/php/{version}/cli/conf.d/20-apcu.ini add apc.enable_cli = 1
     */
    public function speed(string|int|null $key = null, int|null $block = null)
    {
        if ($key !== null) return (new Speed())->get($key, $block);

        return new Speed();
    }

    public function online(int $id)
    {
        return $this->server->isEstablished($id);
    }

    /**
     * Get the number of connections
     * @return int
     */
    public function connections()
    {
        $connections = 0;

        foreach ($this->server->connections as $fd) if ($this->server->isEstablished($fd))  $connections++;

        return $connections;
    }

    /**
     * Close the connection
     */
    public function close(int|array|null $id = null)
    {
        if ($id === null) {
            if ($this->fd === null) return false;
            $id = $this->fd;
        }

        $result = true;

        if (!is_array($id)) $id = [$id];

        foreach ($id as $fd) if (!$this->server->close($fd, WEBSOCKET_CLOSE_NORMAL)) $result = false;

        return $result;
    }

    /**
     * Stop server
     */
    public function stop()
    {
        return $this->server->shutdown();
    }

    protected function pid()
    {
        return $this->server->getMasterPid();
    }
}
