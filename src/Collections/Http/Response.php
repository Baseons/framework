<?php

namespace Baseons\Collections\Http;

use CurlHandle;

class Response
{
    private array $info = [];
    private string $body = '';
    private array $headers = [];
    private array $cookies = [];

    public function __construct(private CurlHandle $curl, private string|bool $response, array $headers = [], array $cookies = [])
    {
        $this->info = curl_getinfo($curl);
        $this->body = is_string($response) ? substr($response, $this->info['header_size'] ?? 0) : '';

        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->cookies = array_change_key_case($cookies, CASE_LOWER);

        return $this;
    }

    public function body()
    {
        return $this->body;
    }

    public function error()
    {
        return curl_errno($this->curl);
    }

    public function header(string|null $name = null, mixed $default = null)
    {
        if ($name === null) return $this->headers;

        $name = strtolower($name);

        if (array_key_exists($name, $this->headers)) return $this->headers[$name];

        return $default;
    }

    public function cookie(string|null $name = null, mixed $default = null)
    {
        if ($name === null) return $this->cookies;

        $name = strtolower($name);

        if (array_key_exists($name, $this->cookies)) return $this->cookies[$name];

        return $default;
    }

    public function status(int|null $is = null)
    {
        $code = $this->info['http_code'] ?? null;

        if ($is !== null) return $is == $code;

        return $code;
    }
}
