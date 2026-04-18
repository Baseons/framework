<?php

namespace Baseons\Job;

use Baseons\Collections\Log;
use Baseons\Shell\Shell;
use Throwable;

/**
 * If you do not have the necessary permissions posix_kill may not work correctly.
 *
 * php fpm check permission in '/etc/php/[version]/fpm/pool.d/www.conf'
 *
 * user = you user
 * group =  you user
 */
class JobController
{
    private string $pid;

    private array $jobs = [];

    private array $cache = [
        'master' => [
            'pid' => null
        ],

        'jobs' => []
    ];

    public function __construct()
    {
        $this->pid = getmypid();

        $this->update();

        $update = false;

        if ($this->cache['master']['pid']) {
            if (!Shell::running($this->cache['master']['pid'])) {
                $this->cache['master']['pid'] = null;

                $update = true;
            }
        }

        foreach ($this->cache['jobs'] as $key_jobs => $jobs) {
            foreach ($jobs as $key_job => $job) {

                if (!Shell::running($job['pid'])) {
                    $update = true;

                    unset($this->cache['jobs'][$key_jobs][$key_job]);
                }
            }
        }

        if ($update) $this->save();

        foreach (config()->route('job', []) as $job) require_once $job;

        $this->jobs = JobMemory::$jobs;
    }

    private function save()
    {
        storage()->makeJson(path()->storage('cache/jsons/jobs.json'), $this->cache);
    }

    private function update()
    {
        $path = path()->storage('cache/jsons/jobs.json');

        if (file_exists($path)) $this->cache = storage()->getJson($path, associative: true);
    }

    public function running()
    {
        if ($this->cache['master']['pid']) {
            return Shell::running($this->cache['master']['pid']);
        }

        return false;
    }

    /**
     * @return array
     */
    public function list()
    {
        $list = [];

        foreach ($this->jobs as $key => $value) {
            $list[$key] = [
                'name' => $value['name'],
                'description' => $value['description'],
                'running' => false,
                'pids' => []
            ];

            if (array_key_exists($key, $this->cache['jobs'])) foreach ($this->cache['jobs'][$key] as $key_job => $job) {
                $list[$key]['running'] = true;
                $list[$key]['pids'][] = $job['pid'];
            }
        }

        return $list;
    }

    public function     start()
    {
        Shell::exec('php ' . path('/') . ' && php base job:master > /dev/null 2>&1 &', name: env('APP_NAME', 'Baseons Job Master'));

        sleep(2);

        $this->update();

        return $this->running();
    }

    public function stop()
    {
        if ($this->cache['master']['pid']) {
            Shell::kill($this->cache['master']['pid']);

            $this->cache['master']['pid'] = null;
        }

        foreach ($this->cache['jobs'] as $key_jobs => $jobs) {
            foreach ($jobs as $key_job => $job) {
                if (Shell::running($job['pid'])) {
                    Shell::kill($this->cache['jobs'][$key_jobs][$key_job]['pid']);
                }

                unset($this->cache['jobs'][$key_jobs][$key_job]);
            }
        }

        $this->save();

        return !$this->running();
    }

    public function clock()
    {
        // kill timeout
        foreach ($this->cache['jobs'] as $key_jobs => $jobs) {
            foreach ($jobs as $key_job => $job) {
                $this->cache['jobs'][$key_jobs][$key_job]['time_executed']++;

                if (!Shell::running($job['pid'])) {
                    unset($this->cache['jobs'][$key_jobs][$key_job]);

                    continue;
                }

                if (!empty($this->cache['jobs'][$key_jobs][$key_job]['time_limit']) && $this->cache['jobs'][$key_jobs][$key_job]['time_executed'] >= $this->cache['jobs'][$key_jobs][$key_job]['time_limit']) {
                    Shell::kill($this->cache['jobs'][$key_jobs][$key_job]['pid']);

                    Log::create('jobs', sprintf('TIMEOUT KILL - %s [%s]', $this->cache['jobs'][$key_jobs][$key_job]['name'] ?? '---', $this->cache['jobs'][$key_jobs][$key_job]['description'] ?? '?'));

                    unset($this->cache['jobs'][$key_jobs][$key_job]);
                }
            }
        }

        $this->save();

        // execute
        foreach ($this->jobs as $key => $job) {
            if (!$job['valid']) continue;

            if ($job['unique']) if (array_key_exists($key, $this->cache['jobs']) && count($this->cache['jobs'][$key])) {
                continue;
            }

            Shell::exec('php ' . path('/') . ' && php base job:execute ' . $key . ' > /dev/null 2>&1 &', name: $job['name']);
        }
    }

    public function master()
    {
        if ($this->running()) return;

        cli_set_process_title('Baseons Framework Job Master');

        $this->cache['master']['pid'] = $this->pid;

        $this->save();

        $minute = date('i');

        while (true) {
            if ($minute == date('i')) {

                sleep(1);

                continue;
            }

            $minute = date('i');

            Shell::exec('cd ' . path('/') . ' && php base job:clock >> /dev/null 2>&1');

            sleep(1);
        }
    }

    public function execute(int|string $key)
    {
        set_time_limit(0);

        if (array_key_exists($key, $this->jobs)) {
            $job = $this->jobs[$key];

            if ($job['unique'] and array_key_exists($key, $this->cache['jobs']) and count($this->cache['jobs'][$key])) return;

            $log = sprintf('%s [%s]', $job['name'] ?? '---', $job['description'] ?? '?');

            Log::create('jobs', 'STARTED - ' . $log);

            // run
            if (!empty($job['action'])) {
                try {
                    $this->cache['jobs'][$key][] = [
                        'pid' => $this->pid,
                        'name' => $job['name'],
                        'description' => $job['description'],
                        'time_limit' => $job['timeout'],
                        'time_executed' => 0
                    ];

                    $this->save();

                    executeCallable($job['action'], [], $job['namespace']);
                } catch (Throwable $error) {
                    Log::create('jobs', sprintf('ERROR - %s [%s %s : %s]', $job['name'] ?? '---', $error->getMessage(), $error->getFile(), $error->getLine()));
                }
            }

            Log::create('jobs', 'FINISHED - ' . $log);

            // update
            $this->update();
        }
    }
}
