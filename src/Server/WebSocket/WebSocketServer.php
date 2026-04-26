<?php

namespace Baseons\Server\WebSocket;

use Baseons\Collections\Log;
use Baseons\Kernel;
use Baseons\Server\Request;
use Baseons\Server\Response as ServerResponse;
use Baseons\Server\Timer;
use Baseons\Server\WebSocket;
use Baseons\Shell\Shell;
use Swoole\Http\Request as HttpRequest;
use Swoole\Http\Response;
use Swoole\WebSocket\Server;
use Throwable;

class WebSocketServer
{
    protected Server|null $server = null;
    protected Timer|null $timer = null;

    public function run(array $params)
    {
        cli_set_process_title('Baseons Framework Server' . (!empty($params['name']) ? " - {$params['name']}" : ''));

        try {
            $class = $params['class'];

            if (!class_exists($class) and $params['namespace']) $class = $params['namespace'] . '\\' . $class;

            if (!class_exists($class)) {
                Shell::red('Class not found: ' . $class)->br();
                Log::create('framework', 'Class not found: ' . $class);
                Kernel::terminate();
            }

            $class = new $class();

            if ($params['ssl']['active'] and $params['ssl']['crt'] and $params['ssl']['key']) {
                $this->server = new Server($params['host'], $params['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
                $this->server->ssl = true;

                $this->server->set([
                    'ssl_protocols' => 0,
                    'ssl_cert_file' => $params['ssl']['crt'],
                    'ssl_key_file' => $params['ssl']['key']
                ]);
            } else {
                $this->server = new Server($params['host'], $params['port'], SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
                $this->server->ssl = false;
            }

            $this->server->set([
                // Only 1 worker should be used because variables in memory are not shared between them.
                'worker_num' => $params['workers'],

                'reload_async' => true,
                // 'max_request' => 10000,
                // 'log_file' => path()->storage('logs/swoole_ws.log'),
                // 'max_wait_time' => 2,
                'websocket_compression' => true,
                'open_websocket_close_frame' => true,
                'open_websocket_pong_frame' => true,
                // 'display_errors' => true
                // 'tcp_user_timeout' => 2 * 1000, // 10 seconds
            ]);

            $this->server->on('start', function (Server $server) use ($class) {
                $path = path()->storage('cache/servers/' . $server->getMasterPid());

                storage()->delete($path);
                storage()->makeDirectory($path);

                if (method_exists($class, 'onStart')) {
                    try {
                        $class->onStart(new WebSocket(null, $this->server));
                    } catch (Throwable $error) {
                        $this->handleError(null, $class, 'start', $error);
                    }
                }
            });

            $this->server->on('handshake', function (HttpRequest $request, Response $response) use ($params, $class) {
                $response->header('server', 'Baseons Framework');

                if (empty($request->header['sec-websocket-key'])) {
                    $response->status(403);
                    $response->end();
                    $response->close();

                    return;
                }

                $status = $this->server->stats();

                if (!empty($params['connections'])) {
                    if ($status['connection_num'] >= $params['connections']) {
                        $response->status(403);
                        $response->end();
                        $response->close();

                        return;
                    }
                }

                $request_params = [];

                $request_params = $this->handlePath($params['path'] ?? '/', $request->server['path_info']);

                if ($request_params === false) {
                    $response->status(404);
                    $response->end();
                    $response->close();

                    return;
                };

                if (method_exists($class, 'onHandshake')) {
                    try {
                        $aproved = $class->onHandshake(new WebSocket($request->fd, $this->server), new Request(array_merge(get_object_vars($request), ['params' => $request_params])));

                        if (!$aproved) {
                            $response->status(403);
                            $response->end();

                            return;
                        }
                    } catch (Throwable $error) {
                        $this->handleError($request->fd, $class, 'handshake', $error);
                        $response->status(500);
                        $response->end();

                        return;
                    }
                };

                $secWebSocketKey = $request->header['sec-websocket-key'];
                $pattern = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';

                if (0 === preg_match($pattern, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
                    $response->status(403);
                    $response->end();
                    $response->close();

                    return;
                }

                $key = base64_encode(
                    sha1(
                        $request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
                        true
                    )
                );

                $headers = [
                    'Upgrade' => 'websocket',
                    'Connection' => 'Upgrade',
                    'Sec-WebSocket-Accept' => $key,
                    'Sec-WebSocket-Version' => '13'
                ];

                if (isset($request->header['sec-websocket-protocol'])) {
                    $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
                }

                foreach ($headers as $key => $val) $response->header($key, $val);

                $response->status(101);
                $response->end();

                if (!method_exists($class, 'onOpen')) return;

                try {
                    $class->onOpen(new WebSocket($request->fd, $this->server), new Request(array_merge(get_object_vars($request), ['params' => $request_params])));
                } catch (Throwable $error) {
                    $this->handleError($request->fd, $class, 'open', $error);
                }
            });

            $this->server->on('message', function (Server $server, $frame) use ($class, $params) {
                if (!method_exists($class, 'onMessage') or empty($params['receive'])) return;

                $bynary = $frame->opcode == WEBSOCKET_OPCODE_BINARY ? true : false;

                try {
                    $class->onMessage(new WebSocket($frame->fd, $server), $frame->data, $bynary);
                } catch (Throwable $error) {
                    $this->handleError($frame->fd, $class, 'message', $error);
                }
            });

            $this->server->on('close', function (Server $server, $fd) use ($class) {
                $ws = new WebSocket($fd, $server);

                if (method_exists($class, 'onClose')) {
                    try {
                        $class->onClose($ws);
                    } catch (Throwable $error) {
                        $this->handleError($fd, $class, 'close', $error);
                    }
                }

                $client_storage = $ws->clientStorage();

                if ($client_storage) $client_storage->set(null)->finish();
            });

            $this->server->on('request', function (HttpRequest $request, Response $response) use ($class, $params) {
                if (!$request->isCompleted()) return;

                $response->setHeader('server', 'Baseons Framework');

                $request_data = get_object_vars($request);
                $request_data['params'] = $this->handlePath($params['path'] ?? '/', $request->server['path_info']);
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
                    } elseif (method_exists($class, 'onRequest')) {
                        $request_data['is_callback'] = true;

                        try {
                            $class->onRequest(new WebSocket($request->fd, $this->server), new Request($request_data), new ServerResponse($response));
                        } catch (Throwable $error) {
                            $this->handleError($request->fd, $class, 'request', $error);
                        }
                    }

                    return;
                }

                // request
                if (!method_exists($class, 'onRequest')) {
                    $response->status(404);
                    $response->end('Page not found');

                    return;
                }

                try {
                    $class->onRequest(new WebSocket($request->fd, $this->server), new Request($request_data), new ServerResponse($response));
                } catch (Throwable $error) {
                    $this->handleError($request->fd, $class, 'request', $error);
                }

                return;
            });

            $this->server->on('WorkerStart', function ($server, $workerId) use ($class) {
                if (method_exists($class, 'timer')) {
                    try {
                        $this->timer = new Timer;

                        $class->timer(new WebSocket(null, $this->server), $this->timer);
                    } catch (Throwable $error) {
                        $this->handleError(null, $class, 'timer', $error);
                    }
                }
            });

            $this->server->on('WorkerExit', function ($server, $workerId) {
                if ($this->timer) $this->timer->clearAll();

                // if (extension_loaded('apcu') and ini_get('apc.enable_cli')) {
                //     apcu_clear_cache();
                // }
            });

            // mesmo que handshake
            // $this->server->on('open', function (Server $server, $fd) use ($class) {
            //     // ...
            // });

            // $this->server->on('connect', function (Server $server, $fd) use ($class) {
            //     // ...
            // });

            // $this->server->on('receive', function (Server $server, $fd) use ($class) {
            //     // ...
            // });

            // $this->server->on('request', function (Server $server, $fd) use ($class) {
            //     // ...
            // });

            // $this->server->on('packet', function (Server $server, $fd) use ($class) {
            //     // ...
            // });

            // $this->server->on('workerstart', function (Server $server) {
            //     // ...
            // });

            // $id = Hash::createTokenString(10,null,numbers:null);

            // $httpServer->on("shutdown", function () {
            //     echo "Servidor encerrado corretamente!\n";
            // });

            // 'worker_num' => 4, // Número de processos workers
            // 'daemonize' => false, // Para rodar em primeiro plano durante o desenvolvimento
            // 'log_file' => '/tmp/swoole.log', // Caminho para o arquivo de log
            // 'max_wait_time' => 10, // Espera até 10 segundos para workers finalizarem

            $this->server->on('shutdown', function (Server $server) use ($class) {
                if (method_exists($class, 'onStop')) {
                    try {
                        $class->onStop(new WebSocket(null, $this->server));
                    } catch (Throwable $error) {
                        $this->handleError(null, $class, 'stop', $error);
                    }
                }

                $path = path()->storage('cache/servers/' . $server->getMasterPid());

                storage()->delete($path);
            });

            $this->server->start();
        } catch (Throwable $error) {
            Log::create('framework', $error);

            Shell::red("{$error->getMessage()} : {$error->getFile()} {$error->getLine()}")->br();
        }

        die;
    }

    protected function handleError(int|null $fd, $class, string $on, Throwable $error)
    {
        if (!method_exists($class, 'onError')) return;

        $class->onError(new WebSocket($fd, $this->server), $on, $error);
    }

    protected function handlePath(string $path, string $request_path)
    {
        $path = trim($path, '/');
        $request_path = trim($request_path, '/');
        $check = $path;
        $params = [];

        if (preg_match('/{(.*?)}/', $path)) {
            $array_route = explode('/', $path);
            $array_url = explode('/', $request_path);

            foreach ($array_route as $key => $value) {
                if (preg_match('/{(.*?)}/', $value, $math)) {
                    $param = str_replace(['?}', '{', '}'], '', $math[0]);

                    if (isset($array_url[$key])) {
                        $params[$param] = $array_url[$key];
                        $check = str_replace($math[0], $array_url[$key], $check);
                    } elseif (substr($value, -2) == '?}') {
                        $params[$param] = null;
                        $check = str_replace("/$math[0]", '', $check);
                    }
                }
            }
        }

        $params = array_map(function ($value) {
            return $value == '' ? null : $value;
        }, $params);

        if ($check == $request_path) return $params;

        return false;
    }
}
