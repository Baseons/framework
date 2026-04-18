<?php

namespace Baseons\Collections;

use ErrorException;
use Baseons\Exceptions\Debug;
use InvalidArgumentException;
use Throwable;

/**
 * sudo apt install php-ssh2
 */
class SSH
{
    private $config = null;
    private mixed $connection = null;
    private mixed $shell = null;
    private mixed $sftp = null;

    public function __construct(string|null $connection = null)
    {
        try {

            $configs = config()->ssh(default: []);

            if (!array_key_exists('connections', $configs))  throw new ErrorException('Connections not found');
            if ($connection === null) $connection = $configs['default'];
            if (!array_key_exists($connection, $configs['connections'])) throw new ErrorException($connection . ' connection not found');

            $this->config = $configs['connections'][$connection];

            $requireds = ['host', 'port', 'user', 'password'];

            foreach ($requireds as $required)  if (!array_key_exists($required, $this->config) || (!is_string($this->config[$required]) && !is_numeric($this->config[$required]))) {
                throw new InvalidArgumentException(sprintf('%s connection required', $required));
            }

            $this->connection = ssh2_connect($this->config['host'], (int) $this->config['port']);

            if ($this->connection === false) throw new ErrorException('SSH connection failed');
            if (!ssh2_auth_password($this->connection, $this->config['user'], (string) $this->config['password'])) throw new ErrorException('SSH authentication failed');

            $this->shell = ssh2_shell($this->connection, 'vanilla');
            $this->sftp = ssh2_sftp($this->connection);

            return $this;
        } catch (Throwable $error) {
            return (new Debug)->exceptions($error);
        }
    }

    /**
     * Send remote command
     */
    public function command(string $command, int $microseconds = 10000)
    {
        try {
            stream_set_blocking($this->shell, false);
            fwrite($this->shell, $command . PHP_EOL);
            fflush($this->shell);
            sleep(1);

            $start = time();
            $outputs = [];
            $prompt = null;

            while (!feof($this->shell) && (time() - $start) < 1) {
                $r = [$this->shell];
                $w = null;
                $e = null;

                $n = stream_select($r, $w, $e, 0, 200000);
                if ($n > 0) {
                    $chunk = fgets($this->shell);

                    if ($chunk === false) break;

                    $outputs[] = $chunk;
                } else {
                    usleep($microseconds);
                }
            }

            $outputs = array_map(function ($line) use ($command, &$prompt) {
                $line = preg_replace('/\x1b\[[\x30-\x3f]*[\x20-\x2f]*[\x40-\x7e]/', '', $line);

                if (preg_match('/^[^\n]*@[^:]+:[^#]*#\s*/m', $line, $matches)) {
                    $prompt = rtrim(trim($matches[0]), '#');
                    $line = preg_replace('/^[^\n]*@[^:]+:[^#]*#\s*/m', '', $line);
                }

                $line = trim($line);

                if ($line == $command) return '';

                return $line;
            }, $outputs);

            $first_line = array_key_first($outputs);
            $last_line = array_key_last($outputs);

            if ($last_line !== null and array_key_exists($last_line, $outputs)) unset($outputs[$last_line]);
            if ($first_line !== null and array_key_exists($first_line, $outputs)) unset($outputs[$first_line]);

            $output = [];

            foreach (array_values($outputs) as $value) {
                if (!empty($value)) $output[] = $value;
            }

            return [
                'command' => $command,
                'output' => $output,
                'prompt' => $prompt
            ];
        } catch (Throwable $error) {
            return (new Debug)->exceptions($error);
        }
    }

    function scandir(string $path, bool $recursivo = false)
    {
        try {
            $resultado = [];
            $handle = opendir("ssh2.sftp://{$this->sftp}{$path}");

            if (!$handle) return [];

            while (false !== ($file = readdir($handle))) {
                if ($file === '.' || $file === '..') continue;


                $caminhoCompleto = $path . '/' . $file;
                $stat = ssh2_sftp_stat($this->sftp, $caminhoCompleto);

                if ($stat === false) {
                    continue;
                }

                $isDir = ($stat['mode'] & 040000) == 040000;

                $resultado[] = [
                    'name' => $file,
                    'path' => $caminhoCompleto,
                    'type' => $isDir ? 'dir' : 'file'
                ];

                if ($isDir && $recursivo) {
                    $resultado = array_merge(
                        $resultado,
                        $this->listFiles($caminhoCompleto, true)
                    );
                }
            }

            closedir($handle);

            return $resultado;
        } catch (Throwable $error) {
            return (new Debug)->exceptions($error);
        }
    }

    /**
     * @return int|false
     */
    public function fileSize(string $path)
    {
        try {
            $stat = ssh2_sftp_stat($this->sftp, $path);

            if ($stat === false) return false;

            return $stat['size'];
        } catch (Throwable $error) {
            return (new Debug)->exceptions($error);
        }
    }

    /**
     * @return bool
     */
    public function fileDownload(string $remote, string $local)
    {
        try {
            return ssh2_scp_recv($this->connection, $remote, $local);
        } catch (Throwable $error) {
            return (new Debug)->exceptions($error);
        }
    }

    /**
     * @return bool
     */
    public function fileUpload(string $local, string $remote)
    {
        try {
            if (!is_file($local)) {
                return false;
            }

            $remoteDir = dirname($remote);
            if ($remoteDir !== '.' && $remoteDir !== '/') {
                $this->ensureRemoteDirectory($remoteDir);
            }

            return ssh2_scp_send($this->connection, $local, $remote);
        } catch (Throwable $error) {
            return (new Debug)->exceptions($error);
        }
    }

    /**
     * @return bool
     */
    public function dirUpload(string $local, string $remote)
    {
        try {
            if (!is_dir($local)) {
                return false;
            }

            $this->ensureRemoteDirectory($remote);

            $handle = opendir($local);
            if (!$handle) {
                return false;
            }

            while (false !== ($file = readdir($handle))) {
                if ($file === '.' || $file === '..') continue;

                $localPath = rtrim($local, '/').'/'.$file;
                $remotePath = rtrim($remote, '/').'/'.$file;

                if (is_dir($localPath)) {
                    $this->dirUpload($localPath, $remotePath);
                } else {
                    $this->fileUpload($localPath, $remotePath);
                }
            }

            closedir($handle);
            return true;
        } catch (Throwable $error) {
            return (new Debug)->exceptions($error);
        }
    }

    /**
     * @return bool
     */
    public function dirDownload(string $remote, string $local)
    {
        try {
            if (!is_dir($local)) mkdir($local, 0777, true);

            $handle = opendir("ssh2.sftp://{$this->sftp}{$remote}");

            if (!$handle) {
                return false;
            }

            while (false !== ($file = readdir($handle))) {
                if ($file === '.' || $file === '..') continue;

                $remotePath = $remote . '/' . $file;
                $localPath  = $local . '/' . $file;

                $stat = ssh2_sftp_stat($this->sftp, $remotePath);
                if (!$stat) continue;

                $isDir = ($stat['mode'] & 040000) == 040000;

                if ($isDir) {
                    $this->dirDownload($remotePath, $localPath);
                } else {
                    $stream = fopen("ssh2.sftp://{$this->sftp}{$remotePath}", 'r');
                    $localFile = fopen($localPath, 'w');

                    if ($stream && $localFile) {
                        stream_copy_to_stream($stream, $localFile);
                    }

                    fclose($stream);
                    fclose($localFile);
                }
            }

            closedir($handle);

            return true;
        } catch (Throwable $error) {
            return (new Debug)->exceptions($error);
        }
    }

    /**
     * @return bool
     */
    public function delete(string $path)
    {
        try {
            $stat = ssh2_sftp_stat($this->sftp, $path);
            if ($stat === false) {
                return false;
            }

            $isDir = ($stat['mode'] & 040000) == 040000;

            if ($isDir) return $this->removeRemoteDirectory($path);

            return ssh2_sftp_unlink($this->sftp, $path);
        } catch (Throwable $error) {
            return (new Debug)->exceptions($error);
        }
    }

    private function removeRemoteDirectory(string $remote): bool
    {
        $handle = opendir("ssh2.sftp://{$this->sftp}{$remote}");
        if (!$handle) {
            return false;
        }

        while (false !== ($file = readdir($handle))) {
            if ($file === '.' || $file === '..') continue;

            $remotePath = rtrim($remote, '/') . '/' . $file;
            $stat = ssh2_sftp_stat($this->sftp, $remotePath);
            if ($stat === false) continue;

            $isDir = ($stat['mode'] & 040000) == 040000;
            if ($isDir) {
                $this->removeRemoteDirectory($remotePath);
            } else {
                ssh2_sftp_unlink($this->sftp, $remotePath);
            }
        }

        closedir($handle);

        return ssh2_sftp_rmdir($this->sftp, $remote);
    }

    private function ensureRemoteDirectory(string $remoteDir): bool
    {
        $remoteDir = rtrim($remoteDir, '/');
        if ($remoteDir === '' || $remoteDir === '.') {
            return true;
        }

        $remotePath = "ssh2.sftp://{$this->sftp}{$remoteDir}";
        if (is_dir($remotePath)) {
            return true;
        }

        return mkdir($remotePath, 0777, true);
    }

    private function disconnect(): void
    {
        if (is_resource($this->connection) && function_exists('ssh2_disconnect')) ssh2_disconnect($this->connection);

        $this->connection = null;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
