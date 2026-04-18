<?php

namespace Baseons\Shell;

/**
 * If you do not have the necessary permissions posix_kill may not work correctly.
 *
 * php fpm check permission in '/etc/php/[version]/fpm/pool.d/www.conf'
 *
 * user = you user
 * group =  you user
 */
class Shell extends Lines
{
    /**
     * @param callback function (string $line, array $status) ...
     * @return bool|int false = error | true = success and process is closed | int = success and process is running
     */
    public static function exec(string $command, callable|null $callback = null, string|null $name = null, string|null $description = null)
    {
        $process = proc_open($command, [
            0 => ['pipe', 'r'], // Standard process input
            1 => ['pipe', 'w'], // Standard process output
            2 => ['pipe', 'w']  // Process error output
        ], $pipes);

        // checks whether the process was opened successfully
        if (!is_resource($process)) return false;

        storage()->makeDirectory(path()->storage('cache/jsons'));

        $status = proc_get_status($process);
        $cache_path = path()->storage('cache/jsons/shell.json');
        $cache = [];
        $pid = $status['pid'] + 1;

        if (file_exists($cache_path)) $cache = json_decode(file_get_contents($cache_path), true);

        $cache[$pid] = [
            'command' => $command,
            'name' => $name,
            'pid' => $pid,
            'description' => $description
        ];

        file_put_contents($cache_path, json_encode($cache));

        // configure standard and error output to not block
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        while (true) {
            $status = proc_get_status($process);

            if ($callback) {
                // Cria um array de streams para monitorar
                $read = array($pipes[1], $pipes[2]);
                $write = NULL;
                $except = NULL;
                $line = '';

                // Espera até que os streams estejam prontos para leitura
                if (stream_select($read, $write, $except, 0)) {
                    // Lê a saída do processo
                    foreach ($read as $stream) {
                        $line .= fread($stream, 1024);

                        if (strpos($line, "\n") !== false && $line !== '') {

                            executeCallable($callback, ['line' => trim($line), 'status' => $status]);

                            $line = '';
                        }

                        flush();
                    }
                }
            }

            // Verifica se o processo terminou
            if (!$status['running']) break;

            // Dá um pequeno intervalo de tempo para evitar uso excessivo da CPU
            usleep(10000);
        }

        // close os pipes
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        // close process
        proc_close($process);

        usleep(1000);

        self::updateCache();

        if (self::running($pid)) return $pid;

        return true;
    }

    /**
     * @return array
     */
    public static function pids()
    {
        storage()->makeDirectory(path()->storage('cache/jsons'));

        $cache_path = path()->storage('cache/jsons/shell.json');
        $cache = [];

        if (file_exists($cache_path)) {
            $cache = json_decode(file_get_contents($cache_path), true);

            $save = false;

            foreach (array_keys($cache) as $pid) if (!self::running($pid, 0)) {
                unset($cache[$pid]);

                $save = true;
            }

            if ($save) file_put_contents($cache_path, json_encode($cache));
        }

        return $cache;
    }

    /**
     * @return string
     */
    public static function readline()
    {
        return readline('') ?? '';
    }

    /**
     * @return bool
     */
    public static function kill(int|array $pid)
    {
        if (!is_array($pid)) $pid = [$pid];

        $response = false;

        foreach ($pid as $value) {
            if (!is_numeric($value)) continue;

            $kill = posix_kill($value, SIGKILL);

            if ($kill) $response = true;
        }

        self::updateCache();

        return $response;
    }

    /**
     * @return bool
     */
    public static function running(int $pid)
    {
        return posix_kill($pid, 0);
    }

    private static function updateCache()
    {
        storage()->makeDirectory(path()->storage('cache/jsons'));

        $cache_path = path()->storage('cache/jsons/shell.json');

        if (file_exists($cache_path)) {
            $cache = json_decode(file_get_contents($cache_path), true);

            $save = false;

            foreach (array_keys($cache) as $pid) if (!self::running($pid, 0)) {
                unset($cache[$pid]);

                $save = true;
            }

            if ($save) file_put_contents($cache_path, json_encode($cache));
        }
    }
}
