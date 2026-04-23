<?php

namespace Baseons\Server\Http;

use Baseons\Collections\Log;
use Baseons\Kernel;
use Baseons\Server\Request as ServerRequest;
use Baseons\Server\Response as ServerResponse;
use Baseons\Shell\Shell;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Throwable;

class HttpServer
{
    protected Server|null $server = null;

    public function run(array $params)
    {
        cli_set_process_title('Baseons Framework Server' . (!empty($params['name']) ? " - {$params['name']}" : ''));

        $class = $params['class'];

        if (!class_exists($class) and $params['namespace']) $class = $params['namespace'] . '\\' . $class;

        if (!class_exists($class)) {
            Shell::red('Class not found: ' . $class)->br();
            Log::create('framework', 'Class not found: ' . $class);
            Kernel::terminate();
        }

        $class = new $class();

        if ($params['ssl']['active'] and $params['ssl']['crt'] and $params['ssl']['key']) {
            $this->server = new Server($params['host'], $params['port'], SWOOLE_SSL);
            $this->server->ssl = true;

            $this->server->set([
                'ssl_protocols' => 0,
                'ssl_cert_file' => $params['ssl']['crt'],
                'ssl_key_file' => $params['ssl']['key']
            ]);
        } else {
            $this->server = new Server($params['host'], $params['port']);
            $this->server->ssl = false;
        }

        $this->server->set([
            'worker_num' => $params['workers'],
            'reload_async' => true,
            // 'log_file' => path()->storage('logs/swoole_ws.log')
        ]);

        $this->server->on('request', function (Request $request, Response $response) use ($class, $params) {
            if (!$request->isCompleted()) return;

            $response->setHeader('server', 'Baseons Framework');

            $request_data = get_object_vars($request);
            $request_data['content'] = $request->getContent();

            // callback
            if ($request->getMethod() == 'POST' and is_array($request->header) and array_key_exists('auth-token', $request->header) and $request->header['auth-token'] == env('APP_KEY') and trim($request->server['path_info'], '/') == 'callback') {
                if (is_array($request->post) and array_key_exists('baseons', $request->post)) {
                    $callback = $request->post['baseons'];

                    if ($callback == 'stop') {
                        $response->status(200);
                        $response->end('ok');

                        $this->server->shutdown();
                    } elseif ($callback == 'connections') {
                        $response->status(200);
                        $response->end($this->server->connections->count() - 1);
                    }
                } elseif (method_exists($class, 'request')) {
                    $request_data['is_callback'] = true;

                    try {
                        $class->request(new ServerRequest($request_data), new ServerResponse($response));
                    } catch (Throwable $error) {
                        $this->handleError($class, $error);
                    }
                }

                return;
            }

            // request
            if (!method_exists($class, 'request')) {
                $response->status(404);
                $response->end('Page not found');

                return;
            }

            try {
                $class->request(new ServerRequest($request_data), new ServerResponse($response));
            } catch (Throwable $error) {
                $this->handleError($class, $error);
            }

            return;
        });

        $this->server->start();
    }

    protected function handleError($class, Throwable $error)
    {
        if (!method_exists($class, 'onError')) return;

        $class->onError($error);
    }
}
