<?php

namespace Baseons\Server\WebSocket;

use Throwable;

class WebSocketData
{
    private $stream = null;

    private string $path;
    private bool $perm = false;
    private bool $delete = false;
    private mixed $data = null;

    public function __construct(string $path, int|null $timeout = null)
    {
        $this->path = $path;
        $this->perm = !file_exists($path);

        storage()->makeDirectory(dirname($path));

        $start = microtime(true);

        while (true) {
            if ($timeout !== null and ((microtime(true) - $start) * 1000) >= $timeout) return null;
            if (!$this->stream) $this->stream = fopen($path, 'c+');
            if ($this->stream && flock($this->stream, LOCK_EX | LOCK_NB)) break;

            usleep(1000);
        }

        $data = stream_get_contents($this->stream);

        if (is_string($data)) {
            $this->data = $data;
        } else {
            $this->data = null;
        }

        return $this;
    }

    public function get()
    {
        return $this->data;
    }

    public function set(string|null $value)
    {
        if ($value === null) {
            $this->delete = true;
        } else {
            ftruncate($this->stream, 0);
            rewind($this->stream);
            fwrite($this->stream, $value);
            fflush($this->stream);

            $this->stream = null;
        }

        if ($this->perm && file_exists($this->path)) {
            chmod($this->path, 0777);
            $this->perm = false;
        }

        return $this;
    }

    public function finish()
    {
        if ($this->stream) {
            try {
                flock($this->stream, LOCK_UN);
            } catch (Throwable $th) {
                // ignore errors
            }

            try {
                fclose($this->stream);
            } catch (Throwable $th) {
                // ignore errors
            }

            $this->stream = null;
        }

        if ($this->delete and file_exists($this->path)) unlink($this->path);
    }

    public function __destruct()
    {
        $this->finish();
    }
}
