<?php

namespace Baseons\Console;

use Baseons\Job\JobController;
use Baseons\Shell\Shell;

class BaseonsCommands
{
    public static function run()
    {
        Console::namespace('Baseons\Console\Commands')->group(function () {
            Console::command('', 'CommandDashboard::run', false);
            Console::command('composer', 'CommandComposer::run', false);
            Console::command('test', 'CommandTest::test')->description('run tests');

            Console::title('Http')->prefix('http:')->group(function () {
                Console::command('server {port?}', 'CommandHttp::server')->description('development server');
                Console::command('list', 'CommandHttp::list')->description('list routes');
            });

            Console::title('Servers')->prefix('server:')->group(function () {
                Console::command('restart {name?}', 'CommandServer::restart')->description('start or restart servers');
                Console::command('start {name?}', 'CommandServer::start')->description('start servers');
                Console::command('stop {name?}', 'CommandServer::stop')->description('stop servers');
                Console::command('run {name}', 'CommandServer::run')->description('run server');
                Console::command('list', 'CommandServer::list')->description('list servers');
            });

            Console::title('Jobs')->prefix('job:')->group(function () {
                $job = (new JobController())->running();

                Console::command($job ? 'stop' : 'start', 'CommandJobs::' . ($job ? 'stop' : 'start'))->description(($job ? Shell::green('running', false, false) : Shell::red('stopped', false, false)) . ' ' . ($job ? 'stop' : 'start') . ' master process ');
                Console::command('clock', 'CommandJobs::clock')->description('executes valid jobs');
                Console::command('list', 'CommandJobs::list')->description('list jobs');
                Console::command('master', 'CommandJobs::master', false);
                Console::command('execute {key}', 'CommandJobs::execute', false);
            });

            Console::title('Migrations')->prefix('migrate')->group(function () {
                Console::command(' {connection?}', 'CommandMigration::run')->description('execute pending migrations');
                Console::command(':seed {name?}', 'CommandMigration::seed')->description('run seeders');
                Console::command(':reset {connection?}', 'CommandMigration::reset')->description('clear database and run migrations');
                Console::command(':up {name}', 'CommandMigration::up')->description('run migration up');
                Console::command(':down {name}', 'CommandMigration::down')->description('run migration down');
                Console::command(':list', 'CommandMigration::list')->description('list all migrations');
            });

            Console::title('Cleaning')->prefix('clear')->group(function () {
                Console::command(':all', 'CommandCleaning::all')->description('clear all');
                Console::command(':logs', 'CommandCleaning::logs')->description('clear log files');
                Console::command(':views', 'CommandCleaning::views')->description('clear cache views');
                Console::command(':caches', 'CommandCleaning::caches')->description('clear all cache files');
                Console::command(':sessions', 'CommandCleaning::sessions')->description('clear sessions files');
            });

            Console::title('Makers')->prefix('make:')->group(function () {
                Console::command('env', 'CommandMake::env')->description('create a env file');
                Console::command('key', 'CommandMake::key')->description('make or update app key on env file');
                Console::command('web {name}', 'CommandMake::web')->description('create a new web controller');
                Console::command('api {name}', 'CommandMake::api')->description('create a new api controller');
                Console::command('migration {name} {table?}', 'CommandMake::migration')->description('create a new migration');
                Console::command('seed {name}', 'CommandMake::seed')->description('create a new seed');
                Console::command('mail {name}', 'CommandMake::mail')->description('create a new mail');
                Console::command('model {name} {connection?}', 'CommandMake::model')->description('[--all to create all models] create a new model');
                Console::command('job {name}', 'CommandMake::job')->description('create a new job');
                Console::command('middleware {name}', 'CommandMake::middleware')->description('create a new middleware');
                Console::command('class {name}', 'CommandMake::class')->description('create a new class');
                Console::command('console {name}', 'CommandMake::console')->description('create a new console');
                Console::command('test {name}', 'CommandMake::test')->description('create a new test');
                Console::command('ws {name}', 'CommandMake::ws')->description('create a new websocket controller');
                Console::command('http {name}', 'CommandMake::http')->description('create a new http controller');
            });
        });
    }
}
