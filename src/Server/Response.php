<?php

namespace Baseons\Server;

use Baseons\Collections\Mime;
use Swoole\Http\Response as RequestResponse;

class Response
{
    private RequestResponse $response;

    public function __construct(RequestResponse $response)
    {
        $this->response = $response;
    }

    public function abort(int $status = 404)
    {
        $this->response->status($status);

        return $this->response->end();
    }

    public function end(string|null $content = null, int|null $status = null)
    {
        if ($status) $this->response->status($status);

        return $this->response->end($content);
    }

    public function status(int $status)
    {
        return $this->response->status($status);
    }

    public function header(string $name, string $value)
    {
        return $this->response->header($name, $value);
    }

    public function cookie(string $name, string|null $value, bool $secure = false, bool $httponly = false, int $expires = 0, string $path = '', string $domain = '',    string $samesite = '',   string $priority = '')
    {
        if ($value === null) $value = '';

        return $this->response->cookie($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite, $priority);
    }

    public function json(mixed $value, int|null $status = null)
    {
        if ($status) $this->response->status($status);

        $this->header('Content-type', 'application/json; charset=utf-8');
        return $this->response->end(json_encode($value));
    }

    public function text(mixed $value, int|null $status = null)
    {
        if ($status) $this->response->status($status);

        $this->header('Content-type', 'text/plain; charset=utf-8');
        return $this->response->end(json_encode($value));
    }

    public function html(mixed $value, int|null $status = null)
    {
        if ($status) $this->response->status($status);

        $this->header('Content-type', 'text/html; charset=utf-8');
        return $this->response->end(json_encode($value));
    }

    public function view(string $view, array|object $params = [], int|null $status = null, string|null $path = null)
    {
        $this->header('Content-type', 'text/html; charset=utf-8');

        if ($status) self::status($status);

        return $this->response->end(view($view, $params, false, $path));
    }

    public function download(string $path, string|null $name = null, int|null $status = null)
    {
        if (file_exists($path)) {
            if ($name == null) {
                $file_name = basename($path);
            } else {
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                $file_name = $name . '.' . $extension;
            }

            $this->header('Content-Description', 'File Transfer');
            $this->header('Content-Type', 'application/octet-stream');
            $this->header('Content-Disposition', 'attachment; filename="' . $file_name . '"');
            $this->header('Expires', '0');
            $this->header('Cache-Control', 'must-revalidate');
            $this->header('Pragma', 'public');
            $this->header('Content-Length', '' . filesize($path));

            if ($status !== null) self::status($status);

            return $this->response->sendfile($path);
        }

        $this->response->status(404);
        $this->response->end();

        return false;
    }

    public function file(string $path, int|null $status = null)
    {
        if (file_exists($path)) {
            $mime = Mime::mimeByExtension($path);

            if ($mime) $this->header('Content-type', $mime);
            $this->header('Content-Length', filesize($path));

            if ($status) self::status($status);

            return $this->response->end(file_get_contents($path));
        }

        $this->response->status(404);
        $this->response->end();

        return false;
    }
}
