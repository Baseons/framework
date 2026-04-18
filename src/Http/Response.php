<?php

namespace Baseons\Http;

use Baseons\Collections\Mime;
use Baseons\Kernel;
use Throwable;

use function PHPSTORM_META\type;

class Response
{
    public static function header(string $name, string|null $value, bool $replace = true)
    {
        try {
            if ($value === null) header_remove($name);
            else header("$name: $value", $replace);

            return true;
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    public static function cookie(string $name, string|null $value, bool $secure = false, bool $httponly = false, int $expires = 0, string $path = '', string $domain = '')
    {
        if ($value === null) $value = '';

        return setcookie($name, $value, $expires, $path, $domain, $secure, $httponly);
    }

    public static function contentType(string $value)
    {
        header('Content-Type: ' . $value);
    }

    public static function status(int $status)
    {
        http_response_code($status);

        return new self;
    }

    public static function abort(int $status = 404, string|null $message = null)
    {
        if (ob_get_level()) ob_clean();

        if (file_exists(path()->resources('views/error/' . $status . '.bns.php'))) {
            if ($message === null) $message = lang('http.status.' . $status);

            return self::view('error.' . $status, [
                'status' => $status,
                'message' => $message
            ], $status);
        } else if (file_exists(path()->resources('views/error/default.bns.php'))) {
            if ($message === null) $message = lang('http.status.' . $status);

            return self::view('error.default', [
                'status' => $status,
                'message' => $message
            ], $status);
        } else {
            self::status($status);
        }

        return Kernel::terminate();
    }

    public static function view(string $view, array|object $params = [], int|null $status = null, string|null $path = null)
    {
        self::header('Content-type', 'text/html; charset=utf-8');

        if ($status !== null) self::status($status);

        view($view, $params, path: $path);

        return Kernel::terminate();
    }

    public static function json(mixed $value, int|null $status = null)
    {
        if (ob_get_level()) ob_clean();

        if ($status !== null) self::status($status);

        self::header('Content-type', 'application/json; charset=utf-8');

        if (is_string($value) and ($type = storage()->isFilePathOrContent($value))) {
            if ($type == 'path') echo file_get_contents($value);
            else echo $value;
        } else {
            print(json_encode($value));
        }

        return Kernel::terminate();
    }

    public static function text(mixed $value, int|null $status = null)
    {
        if (ob_get_level()) ob_clean();

        if ($status !== null) self::status($status);

        self::header('Content-type', 'text/plain; charset=utf-8');

        if (is_string($value) and ($type = storage()->isFilePathOrContent($value))) {
            if ($type == 'path') echo file_get_contents($value);
            else echo $value;
        } else {
            if (is_array($value) or is_object($value)) $value = json_encode($value);

            if ($value === false) $value = '';
            echo strval($value);
        }

        return Kernel::terminate();
    }

    public static function html(mixed $value, int|null $status = null)
    {
        if (ob_get_level()) ob_clean();

        if ($status !== null) self::status($status);

        self::header('Content-type', 'text/html; charset=utf-8');

        if (is_string($value) and ($type = storage()->isFilePathOrContent($value))) {
            if ($type == 'path') echo file_get_contents($value);
            else echo $value;
        } else echo strval($value);

        return Kernel::terminate();
    }

    public static function download(string $value, string|null $name = null, int|null $status = null)
    {
        if (ob_get_level()) ob_clean();

        $type = storage()->isFilePathOrContent($value);

        if (!$type) return self::abort(404);
        if ($status !== null) self::status($status);

        if ($type == 'path') {
            if ($name === null) $file_name = basename($value);
            else {
                $extension = pathinfo($value, PATHINFO_EXTENSION);
                $file_name = $name . '.' . $extension;
            }

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($value));

            readfile($value);
        } else {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . strlen($value));

            if ($name !== null) header('Content-Disposition: attachment; filename="' . $name . '"');
            else header('Content-Disposition: attachment; filename="file.' .  Mime::originalExtension($value));

            echo $value;
        }

        return Kernel::terminate();
    }

    public static function file(string $value, int|null $status = null)
    {
        if (ob_get_level()) ob_clean();

        $type = storage()->isFilePathOrContent($value);

        if (!$type) return self::abort(404);

        if ($type == 'path') $mime = Mime::mimeByExtension($value);
        else $mime = Mime::originalMime($value);

        if ($mime) self::header('Content-type', $mime);
        if ($status !== null) self::status($status);

        if ($type == 'path') {
            self::header('Content-Length', filesize($value));
            readfile($value);
            return Kernel::terminate();
        } else {
            header('Content-Length: ' . strlen($value));
            echo $value;
            return Kernel::terminate();
        }

        return self::abort(404);
    }

    /**
     * Set a temporary value for the next request
     */
    public static function with(mixed $value = null)
    {
        request()->session()->with($value);

        return new self;
    }

    public static function terminate(string|null $value = null, int|null $status = null)
    {
        if ($status !== null) self::status($status);

        if (ob_get_level()) ob_clean();

        echo $value ?? '';

        return Kernel::terminate();
    }
}
