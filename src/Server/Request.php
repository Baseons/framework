<?php

namespace Baseons\Server;

use Baseons\Http\Upload;

class Request
{
    private array $params = [];
    private array $header = [];
    private array $cookie = [];
    private array $server = [];
    private array $get = [];
    private array $post = [];
    private array $files = [];
    private string $content = '';
    private bool $is_callback = false;

    public function __construct(array $request)
    {
        if (array_key_exists('params', $request) and is_array($request['params'])) $this->params = $request['params'];
        if (array_key_exists('content', $request) and is_string($request['content'])) $this->content = $request['content'];
        if (array_key_exists('header', $request) and is_array($request['header'])) foreach ($request['header'] as $key => $value) $this->header[strtolower($key)] = $value;
        if (array_key_exists('cookie', $request) and is_array($request['cookie'])) $this->cookie = $request['cookie'];
        if (array_key_exists('server', $request) and is_array($request['server'])) $this->server = $request['server'];
        if (array_key_exists('get', $request) and is_array($request['get'])) $this->get = $request['get'];
        if (array_key_exists('post', $request) and is_array($request['post'])) $this->post = $request['post'];
        if (array_key_exists('files', $request) and is_array($request['files'])) $this->files = $request['files'];
        if (array_key_exists('is_callback', $request) and is_bool($request['is_callback'])) $this->is_callback = $request['is_callback'];

        if ($this->method('post') and !count($this->post)) {
            $data = json_decode($this->content, true);

            if (json_last_error() === JSON_ERROR_NONE and is_array($data)) $this->post = $data;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isCallback()
    {
        return $this->is_callback;
    }

    /**
     * @return string
     */
    public function path(string|null $is = null)
    {
        if (is_string($is)) return trim($is, '/') == trim($this->server['path_info'], '/');

        return $this->server['path_info'];
    }

    /**
     * @return string|bool|null
     */
    public function method(string|null $is = null)
    {
        if (is_string($is)) {
            $is = strtoupper($is);

            if (!in_array($is, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'COPY', 'OPTIONS', 'LOCK', 'UNLOCK'])) return false;

            return $is == strtoupper($this->server['request_method'] ?? '');
        }

        return $this->server['request_method'] ?? null;
    }

    /**
     * @return string
     */
    public function ip()
    {
        return $this->server['remote_addr'];
    }

    public function param(string|null $name = null, mixed $default = null)
    {
        if (is_null($name)) return $this->params;

        if (array_key_exists($name, $this->params)) return $this->params[$name];

        return $default;
    }

    public function header(string|null $name = null, mixed $default = null)
    {
        if (is_null($name)) return $this->header;

        $name = strtolower($name);

        if (array_key_exists($name, $this->header)) return $this->header[$name];

        return $default;
    }

    public function cookie(string|null $name = null, mixed $default = null)
    {
        if (is_null($name)) return $this->cookie;

        if (array_key_exists($name, $this->cookie)) return $this->cookie[$name];

        return $default;
    }

    public function server(string|null $key = null, mixed $default = null)
    {
        if (is_null($key)) return $this->server;

        if (array_key_exists($key, $this->server)) return $this->server[$key];

        return $default;
    }

    /**
     * @return string|null
     */
    public function content()
    {
        return $this->content;
    }

    public function get(string|null $input = null, mixed $default = null)
    {
        if (is_null($input)) return $this->get;

        if (array_key_exists($input, $this->get)) return $this->get[$input];

        return $default;
    }

    public function post(string|null $input = null, mixed $default = null)
    {
        if (is_null($input)) return $this->post;

        if (array_key_exists($input, $this->post)) return $this->post[$input];

        return $default;
    }

    public function file(string|null $input = null, mixed $default = null)
    {
        if (is_null($input)) return $this->files;

        if (array_key_exists($input, $this->files)) return $this->files[$input];

        return $default;
    }

    public function upload(string $input)
    {
        $file = [];

        if (array_key_exists($input, $this->files)) $file = $this->files[$input];

        return new Upload($file);
    }
}
