<?php

namespace Baseons\Console\Commands;

use Baseons\Collections\Log;
use Baseons\Shell\Shell;

class CommandCleaning
{
    public function views()
    {
        $files = storage()->getFiles(path()->storage('cache/views'));

        if (is_array($files)) storage()->delete($files);

        storage()->makeJson(path()->storage('cache/jsons/views.json'));

        Shell::green('views cache deleted')->br();
    }

    public function logs()
    {
        Log::clean();

        Shell::green('log files cleaned')->br();
    }

    public function caches()
    {
        $files = storage()->getFiles(path()->storage('cache'), true);

        if (is_array($files)) storage()->delete($files);

        storage()->makeJson(path()->storage('cache/jsons/files.json'));

        Shell::green('cache deleted')->br();
    }

    public function sessions()
    {
        $files = storage()->getFiles(session_save_path());

        if (is_array($files)) storage()->delete($files);

        Shell::green('sessions cache deleted')->br();
    }

    public function all()
    {
        $this->logs();
        $this->caches();
        $this->views();
        $this->sessions();
    }
}
