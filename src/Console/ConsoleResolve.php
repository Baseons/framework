<?php

namespace Baseons\Console;

use Baseons\Kernel;
use Baseons\Shell\Shell;

class ConsoleResolve
{
    private string $console = '';
    private array $params = [];

    public function run(array $commands)
    {
        global $argv;

        unset($argv[0]);

        $this->console = preg_replace('/( ){2,}/', '$1', implode(' ', $argv));

        foreach ($commands as $value) if ($this->checkCommand($value['command']))  return $this->execute($value);

        Shell::red('command not found')->br();
    }

    private function execute(array $command)
    {
        if (empty($command['action'])) return;

        executeCallable($command['action'], $this->params, $command['namespace'] ?? null);
    }

    private function checkCommand(string $command)
    {
        $check = $command;
        $params = [];

        if (preg_match('/{(.*?)}/', $command)) {
            $array_command = explode(' ', $command);
            $array_console = explode(' ', $this->console);

            foreach ($array_command as $key => $value) {
                if (preg_match('/{(.*?)}/', $value, $math)) {
                    $param = str_replace(['?}', '{', '}'], '', $math[0]);

                    if (isset($array_console[$key])) {
                        $params[$param] = $array_console[$key];
                        $check = str_replace($math[0], $array_console[$key], $check);
                    } elseif (substr($value, -2) == '?}') {
                        $params[$param] = null;
                        $check = str_replace("$math[0]", '', $check);
                    }
                }
            }
        }

        $check = trim($check);

        if ($this->console != $check) return false;

        $this->params = $params;
        return true;
    }
}
