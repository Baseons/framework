<?php

namespace Baseons\Console\Commands;

use Baseons\Job\JobController;
use Baseons\Shell\Shell;

class CommandJobs
{
    private JobController $controller;

    public function __construct()
    {
        $this->controller = new JobController();
    }

    public function start()
    {
        if ($this->controller->start()) {
            Shell::green('master process is running')->br();
        } else {
            Shell::red('failed to start master process')->br();
        }
    }

    public function stop()
    {
        if ($this->controller->stop()) {
            Shell::red('master process is stopped')->br();
        } else {
            Shell::red('failed to stop master process')->br();
        }
    }

    public function execute(string $key)
    {
        $this->controller->execute($key);
    }

    public function master()
    {
        $this->controller->master();
    }

    public function clock()
    {
        $this->controller->clock();
    }

    public function list()
    {
        $list = $this->controller->list();

        foreach ($list as $value) {
            $start = Shell::normal($value['name'] ?? '---', true, false);
            $start .= Shell::gray($value['description'] ?? '?', false, false);

            if ($value['running']) {
                $end = Shell::green('RUNNING', true, false);
            } else {
                $end = Shell::red('STOPPED', true, false);
            }

            Shell::list($start, $end);
        }
    }
}
