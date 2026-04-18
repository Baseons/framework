<?php

namespace Baseons\Http;

use Baseons\Collections\Validator;

class Request
{
    public function all()
    {
        return array_merge($_GET, $_POST, $_FILES ?? []);
    }

    public function get(string|null $input = null, mixed $default = null)
    {
        if ($input === null) return $_GET ?? $default;
        if (array_key_exists($input, $_GET)) return $_GET[$input];

        return $default;
    }

    public function post(string|null $input = null, mixed $default = null)
    {
        if ($input === null) return $_POST ?? $default;
        if (array_key_exists($input, $_POST)) return $_POST[$input];

        return $default;
    }

    public function input(string $input, mixed $default = null)
    {
        if ($post = $this->post($input)) return $post;
        elseif ($file = $this->file($input)) return $file;
        elseif ($get = $this->get($input)) return $get;

        return $default;
    }

    public function file(string|null $input = null, mixed $default = null)
    {
        if ($input === null) return $_FILES;

        if (array_key_exists($input, $_FILES)) return $_FILES[$input];

        return $default;
    }

    public function body()
    {
        return file_get_contents('php://input');
    }

    public function upload(string|array $input)
    {
        return new Upload($input);
    }

    public function method(string|null $is = null)
    {
        $accepteds = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'COPY', 'OPTIONS', 'LOCK', 'UNLOCK'];
        $is = $is !== null ? strtoupper($is) : null;

        $method = !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        if ($method === 'POST' and array_key_exists('_method', $_POST) and is_string($_POST['_method']) and in_array(strtoupper($_POST['_method']), $accepteds)) $method = strtoupper($_POST['_method']);

        if ($is !== null) return $is == $method;

        return $method;
    }

    /**
     * Current domain
     * @return string|null
     */
    public function domain()
    {
        return $_SERVER['SERVER_NAME'] ?? null;
    }

    public function https()
    {
        if ((!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') ||
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ||
            (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')
        ) return true;

        return false;
    }

    /**
     * @return string
     */
    public function url(string|null $path = null)
    {
        if (!empty($_SERVER['HTTP_HOST'])) {
            $http = $this->https() ? 'https://' : 'http://';
            $host = $http . $_SERVER['HTTP_HOST'];
        } else $host = config()->app('APP_URL', 'http://localhost:3000');

        return  $host . (!empty($path) ? '/' . trim($path, '/') : '');
    }

    public function urlPath()
    {
        $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        return '/' . $path;
    }

    /**
     * @return string
     */
    public function urlFull(string|null $path = null)
    {
        $url_path = $this->urlPath();
        return trim($this->url($url_path) . (!empty($path) ? '/' . trim($path, '/') : ''), '/');
    }

    /**
     * @return string
     */
    public function urlFullQuery(array|null $query = null)
    {
        $url_query = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);

        if ($query !== null) {
            $gets = filter_input_array(INPUT_GET, $_GET, FILTER_SANITIZE_SPECIAL_CHARS);

            foreach ($query as $key => $value) $gets[$key] = $value;

            $url_query = http_build_query($gets);
        }

        return $this->urlFull() . (!empty($url_query) ? '?' . $url_query : '');
    }

    public function userAgent()
    {
        return $this->header('User-Agent');
    }

    /**
     * @return string|null
     */
    public function ip()
    {
        $headers = [
            'HTTP_X_REAL_IP', // Nginx
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR', // Proxy
            'REMOTE_ADDR',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR'
        ];

        foreach ($headers as $header) {
            if (empty($_SERVER) or !is_array($_SERVER)) break;

            if (array_key_exists($header, $_SERVER) and !empty($_SERVER[$header])) {
                $ipList = explode(',', $_SERVER[$header]);

                foreach ($ipList as $ip) return trim($ip);
            }
        }

        return null;
    }

    public function mobile()
    {
        return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|ipad|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"] ?? '') ? true : false;
    }

    public function header(string|null $name = null, mixed $default = null)
    {
        if (!function_exists('getallheaders')) return null;

        $headers = [];

        foreach (getallheaders() ?? [] as $k => $v) $headers[strtolower($k)] = $v;

        if (!is_null($name)) {
            $name = strtolower($name);

            if (array_key_exists($name, $headers)) return $headers[$name];

            return $default;
        }

        return $headers;
    }

    public function cookie(string|null $name = null, mixed $default = null)
    {
        if (is_null($name)) return $_COOKIE;

        if (array_key_exists($name, $_COOKIE)) return $_COOKIE[$name];

        return $default;
    }

    public function origin()
    {
        if (isset($_SERVER['HTTP_ORIGIN'])) return trim($_SERVER['HTTP_ORIGIN'], '/');
        if (isset($_SERVER['HTTP_REFERER'])) return trim($_SERVER['HTTP_REFERER'], '/');

        return null;
    }

    /**
     * Validate request GET and POST
     *
     * @param string|int|array|null $input If is null validate all, if is array validate inputs in array, if is string or int and input value is array validate the array found
     */
    public function validator(string|int|array|null $input = null, array|null $availables = null)
    {
        $data = array_merge($_GET, $_POST);

        if (is_array($input)) $data = array_intersect_key($data, array_flip($input));
        elseif ($input !== null) $data = $data[$input] ?? [];

        if (!is_array($data)) $data = [];

        return new Validator($data, $availables);
    }

    /**
     * @return mixed|Session
     */
    public function session(string|null $key = null, mixed $default = null)
    {
        if ($key !== null) return Session::get($key, $default);

        return new Session;
    }

    /**
     * @return mixed
     *
     * Returns the value defined in the last request
     */
    public function old(string|null $key = null, mixed $default = null)
    {
        return Session::old($key, $default);
    }
}
