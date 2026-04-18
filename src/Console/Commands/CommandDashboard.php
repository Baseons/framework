<?php

namespace Baseons\Console\Commands;

use Baseons\Console\ConsoleMemory;
use Baseons\Collections\Str;
use Baseons\Shell\Shell;

class CommandDashboard
{
    public function run()
    {
        $commands = ConsoleMemory::$commands;
        $commands_list = [];

        foreach ($commands as $value) {
            if ($value['list']) {
                $title = $value['title'] ?? 'Available commands';

                if (!array_key_exists($title, $commands_list)) $commands_list[$title] = [];

                $command = $value['command'];
                $options = [];

                if (preg_match_all('/{(.*?)}/', $command, $matches)) {
                    foreach ($matches[0] as $k => $v) {
                        $required = Str::end($matches[1][$k] ?? '', '?') ? 'optional' : 'required';
                        $n = trim($matches[1][$k] ?? '', '?');

                        $command = trim(str_replace($v, '', $command));
                        $options[] = "[$required:$n]";
                    }
                };

                $options = implode(' ', $options);

                $start = Shell::green($command, false, false);

                if (!empty($options)) $start .= ' ' . Shell::blue($options, false, false);

                $end = null;

                if (!empty($value['description'])) $end = $value['description'];

                $commands_list[$title][] = [
                    'start' => $start,
                    'end' => $end
                ];
            }
        }

        $width = 120;

        if (Shell::width() < 120) $width = Shell::width();

        $title = Shell::gray('| ', false, false);
        $title .= Shell::green('BASEONS FRAMEWORK', false, false);
        $title .= Shell::gray(' |', false, false);

        $version = Shell::gray('| ', false, false);
        $version .= Shell::green('1.0.0', false, false);
        $version .= Shell::gray(' |', false, false);

        Shell::list(width: $width)->br();
        Shell::list($title, $version, width: $width)->br();
        Shell::list(width: $width)->br();

        foreach ($commands_list as $title => $value) {
            Shell::br()->yellow($title)->br();

            foreach ($value as $x) Shell::list($x['start'], $x['end'], width: $width)->br();
        }

        Shell::br();
    }
}
