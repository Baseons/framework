<?php

namespace Baseons\View;

use Exception;
use Baseons\View\Compiler\CompilerInclude;
use Baseons\View\Compiler\CompilerPHP;
use Baseons\View\Compiler\CompilerSections;

class View
{
    public function load(string $view, array|object $params = [], bool $echo = true, string|null $path = null)
    {
        // dd(php_strip_whitespace());

        $view = str_replace('.', DIRECTORY_SEPARATOR, $view);
        $path = $path === null ? path()->resources('views/' . $view) : path()->separator($path . '/' . $view);

        $path = match (true) {
            file_exists($path) and is_file($path) => $path,
            file_exists($path . '.bns.php') and is_file($path . '.bns.php') => $path . '.bns.php',
            file_exists($path . '.php') and is_file($path . '.php') => $path . '.php',
            file_exists($path . '.blade.php') and is_file($path . '.blade.php') => $path . '.blade.php',

            default => throw new Exception('File view not found: ' . $path)
        };

        foreach ($params as $key => $value) $$key = $value;

        $content = $this->content($path);

        if ($echo) {
            if (ob_get_level()) ob_clean();

            require $content;

            return;
        }

        ob_start();

        require $content;

        return ob_get_clean();
    }

    private function content(string $path)
    {
        $path_json = path()->storage('cache/jsons/views.json');

        if (!file_exists($path_json)) {
            storage()->makeJson($path_json);
            return $this->compiler($path);
        }

        $json = storage()->getJson($path_json, associative: true);
        $key = $this->key($path);
        $delete = [];

        if (!array_key_exists($key, $json)) return $this->compiler($path);

        if ($json[$key]['filemtime'] != filemtime($path) or !file_exists($json[$key]['file'])) $delete[] = $json[$key]['file'];

        foreach ($json[$key]['includes'] as $include) if (!file_exists($include['file']) or filemtime($include['file']) != $include['filemtime']) $delete[] = $json[$key]['file'];

        if (!count($delete)) return $json[$key]['file'];

        storage()->delete($delete);

        return $this->compiler($path);
    }

    private function compiler(string $path)
    {
        // view file
        $view = file_get_contents($path);

        // remove comments
        if (preg_match_all("/{{--(.*?)--}}/s", $view, $match)) foreach ($match as $key => $value) $view = str_replace($value, '', $view);

        // includes
        $include = new CompilerInclude;
        $view = $include->run($view);

        // sections
        $sections = new CompilerSections;
        $view = $sections->run($view);

        // php compiler
        $php = new CompilerPHP;
        $view = $php->run($view);

        $path_json = path()->storage('cache/jsons/views.json');
        $json = storage()->getJson($path_json, associative: true);
        $path_view = path()->storage('cache/views/' . strtolower(bin2hex(random_bytes(12))) . '.php');
        $key = $this->key($path);

        $json[$key]['file'] = $path_view;
        $json[$key]['includes'] = $include->includes;
        $json[$key]['filemtime'] = filemtime($path);

        storage()->makeJson($path_json, $json);
        storage()->makeFile($path_view, $view);

        return $path_view;
    }

    private function key(string $path)
    {
        $key = str_replace(['.php', '.blade.php', '.bns.php'], '', $path);
        $key = trim(str_replace(['/', '\\', '.'], '-', $key), '-');

        return $key;
    }
}
