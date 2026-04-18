<?php

namespace Baseons\Collections\Http;

use CURLFile;
use Baseons\Collections\Mime;

class HTTP
{
    private array $header = [];
    private string|array $data = [];
    private array $files = [];
    private array $opt = [];
    private string $url = '';

    public function __construct(string|null $url = null)
    {
        if($url) $this->url = $url;

        return $this;
    }

    public function url(string $url)
    {
        $this->url = $url;

        return $this;
    }

    public function timeout(int|null $value)
    {
        if ($value === null) {
            unset($this->opt[CURLOPT_CONNECTTIMEOUT], $this->opt[CURLOPT_TIMEOUT]);
        } else {
            $this->opt[CURLOPT_CONNECTTIMEOUT] = $value;
            $this->opt[CURLOPT_TIMEOUT] = $value;
        }

        return $this;
    }

    public function header(string $name, string|null $value)
    {
        if ($value === null) unset($this->header[$name]);
        else $this->header[$name] = $name . ': ' . $value;

        return $this;
    }

    public function file(string $input, string $path, string|null $name = null)
    {
        $this->files[$input] = new CURLFile($path, Mime::mimeByExtension($path), $name);

        return $this;
    }

    public function json(mixed $data)
    {
        $this->header('Content-type', 'application/json; charset=utf-8');

        $this->data = json_encode($data);

        return $this;
    }

    public function text(mixed $data)
    {
        $this->header('Content-type', 'text/plain; charset=utf-8');

        $this->data = strval($data);

        return $this;
    }

    public function html(mixed $data)
    {
        $this->header('Content-type', 'text/html; charset=utf-8');

        $this->data = strval($data);

        return $this;
    }

    public function get(string|array|null $data = null)
    {
        if ($data !== null) $this->data = $data;

        return $this->request('get');
    }

    public function post(string|array|null $data = null)
    {
        if ($data !== null) $this->data = $data;

        return $this->request('post');
    }

    public function put(string|array|null $data = null)
    {
        if ($data !== null) $this->data = $data;

        return $this->request('put');
    }

    public function delete(string|array|null $data = null)
    {
        if ($data !== null) $this->data = $data;

        return $this->request('delete');
    }

    public function patch(string|array|null $data = null)
    {
        if ($data !== null) $this->data = $data;

        return $this->request('patch');
    }

    public function copy(string|array|null $data = null)
    {
        if ($data !== null) $this->data = $data;

        return $this->request('copy');
    }

    public function options(string|array|null $data = null)
    {
        if ($data !== null) $this->data = $data;

        return $this->request('options');
    }

    public function lock(string|array|null $data = null)
    {
        if ($data !== null) $this->data = $data;

        return $this->request('lock');
    }

    public function unlock(string|array|null $data = null)
    {
        if ($data !== null) $this->data = $data;

        return $this->request('unlock');
    }

    /**
     * @return Baseons\Collections\Http|Response
     */
    private function request(string $method)
    {
        $method = strtoupper($method);
        $cookies = [];
        $headers = [];

        if ($method === 'GET') {
            if (is_array($this->data) and count($this->data)) $this->url = str()->urlQueryReplace($this->url, $this->data);

            $curl = curl_init($this->url);
        } else {
            $curl = curl_init($this->url);
            $data = $this->data;

            if (is_array($data)) $data = $this->getData(array_merge($this->data, $this->files));

            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        if (count($this->opt)) curl_setopt_array($curl, $this->opt);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);

        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$cookies, &$headers) {
            $exp = array_map(function ($value) {
                return trim($value);
            }, explode(':', $header, 2));

            if (count($exp) == 2) {
                $headers[$exp[0]] = $exp[1];

                if ($exp[0] == 'Set-Cookie') {
                    $cookie = trim(substr($header, 11));
                    $parts = explode(';', $cookie);
                    [$name, $value] = explode('=', $parts[0], 2);
                    $cookies[$name] = $value;
                }
            }

            return strlen($header);
        });

        if (count($this->header)) curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);

        $response = curl_exec($curl);

        curl_close($curl);

        return new Response($curl, $response, $headers, $cookies);
    }

    private function getData(array $data, string $prefix = '')
    {
        $result = [];

        foreach ($data as $key => $value) {
            $newKey = $prefix === '' ? $key : "{$prefix}[{$key}]";

            if ($value instanceof CURLFile) {
                $result[$newKey] = $value;
            } elseif (is_array($value)) {
                $result += $this->getData($value, $newKey);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
