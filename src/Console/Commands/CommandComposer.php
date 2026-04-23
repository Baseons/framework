<?php

namespace Baseons\Console\Commands;

use Baseons\Collections\Hash;

class CommandComposer
{
    public function run()
    {
        $paths = [
            path()->private(),

            path()->base('app/Models'),
            path()->base('app/Mails'),
            path()->base('app/Middlewares'),
            path()->base('app/Helpers'),
            path()->base('app/Controllers'),
            path()->base('app/Classes'),

            path()->storage('logs'),
            path()->storage('cache/jsons'),
            path()->storage('cache/views'),
            path()->storage('cache/servers'),
            path()->storage('cache/files'),

            path()->base('tests'),
            path()->base('database/seeders'),
            path()->base('database/migrations')

        ];

        $sessions_path = config()->session('path');

        if (is_string($sessions_path)) $paths[] = $sessions_path;

        storage()->makeDirectory($paths);

        // env
        $path_env = path('.env');

        if (!file_exists($path_env)) storage()->makeFile($path_env, view('env', [
            'app_key' => 'base64:' . base64_encode(Hash::createKey())
        ], false, path()->framework('Templates')));
    }
}
