<?php

namespace Baseons\View\Compiler;

use Exception;

class CompilerInclude
{
    public array $includes = [];
    private $view;

    public function run($view)
    {
        $this->view = $view;
        $loop = $this->loop();

        if ($loop == true) {
            return $this->view;
        }

        return false;
    }

    protected function loop()
    {
        $regex = '/@include\(((?:[^()]+|\((?1)\))*)\)/';

        if (preg_match($regex, $this->view, $matches)) return $this->include($matches);

        return true;
    }

    protected function include($matches)
    {
        $path = executeString($matches[1]);

        if (!file_exists($path) or !is_file($path)) {
            $path = str_replace('.', DIRECTORY_SEPARATOR, $path);
            $path = path()->resources('views/' . $path);

            $path = match (true) {
                file_exists($path) and is_file($path) => $path,
                file_exists($path . '.bns.php') and is_file($path . '.bns.php') => $path . '.bns.php',
                file_exists($path . '.php') and is_file($path . '.php') => $path . '.php',
                file_exists($path . '.blade.php') and is_file($path . '.blade.php') => $path . '.blade.php',

                default =>  throw new Exception('View not found @include(' . $matches[1] . ') - ' . $path)
            };
        }

        $this->includes[] = [
            'file' => $path,
            'filemtime' => filemtime($path)
        ];

        $include = file_get_contents($path);

        if (preg_match_all("/{{--(.*?)--}}/s", $include, $match)) foreach ($match as $value) $include = str_replace($value, '', $include);

        $limit = 1;

        $this->view = str_replace($matches[0], $include, $this->view, $limit);

        return $this->loop();
    }
}
