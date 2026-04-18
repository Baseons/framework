<?php

namespace Baseons\Exceptions;

use Baseons\Collections\Hash;
use Baseons\Kernel;
use Baseons\Shell\Shell;
use Throwable;

class Debug
{
    protected int $space = 0;
    protected string $dd = '';
    protected array $tokens = [];

    public function exceptions(Throwable $error)
    {
        if (config()->app('debug') == false and Kernel::type() === 'http') return response()->abort(500);

        if (Kernel::type() === 'console') {
            Shell::red("{$error->getFile()} {$error->getLine()} - {$error->getMessage()}")->br();

            (new Kernel)->terminate();
        }

        $server = $_SERVER ?? [];

        $server['CONTENT_LENGTH'] = empty($server['CONTENT_LENGTH']) ? 0 : $server['CONTENT_LENGTH'];
        $server['CONTENT_LENGTH'] = str()->formatSize($server['CONTENT_LENGTH']);

        return response()->view('exception',  [
            'error' => $error,
            'include' => file_get_contents(path()->framework('Exceptions/views/exception-include.html')),
            'file' =>  htmlspecialchars(file_get_contents($error->getFile())),
            'server' => $server,
            'ms' => number_format((microtime(true) - BASEONS_START) * 1000, 3, '.', '') . ' MS',

            'request' => [
                'class' => request(),
                'all' => request()->all(),
                'header' => request()->header(),
                'cookie' => request()->cookie(),
                'session' => $_SESSION ?? []
            ]
        ], 500, path()->framework('Exceptions/views'));
    }

    public function dd(int $line, string $file, $values)
    {
        if (!count($values)) $values = [null];

        foreach ($values as $value) {
            $this->dd .= "<div class=\"dd-code\">";
            $this->ddValues($value);
            $this->dd .= '</div>';
        }

        return response()->view('dd', [
            'dd' => $this->dd,
            'line' => $line,
            'file' => $file,
            'id' => Hash::createTokenString(characters: 'abcdefghijklmnopqrstuvwxyz', special: null, numbers: null)
        ], path: path()->framework('Exceptions/views'));
    }

    public function dump(int $line, string $file, $values)
    {
        if (!count($values)) $values = [null];

        foreach ($values as $value) {
            $this->dd .= "<div class=\"dd-code\">";
            $this->ddValues($value);
            $this->dd .= '</div>';
        }

        echo view('dump', [
            'dd' => $this->dd,
            'line' => $line,
            'file' => $file,
            'id' => Hash::createTokenString(characters: 'abcdefghijklmnopqrstuvwxyz', special: null, numbers: null)
        ], false, path()->framework('Exceptions/views'));
    }

    protected function ddValues($value, string|null $array_name = null)
    {
        $type = gettype($value);
        $hidden_class = implode(' ', $this->tokens);

        $this->dd .= '<div class="dd-copy">';

        if ($array_name === null) {
            $key = '';
        } elseif (is_numeric($array_name)) {
            $key = '<span class="dd-code-array-key ' . $hidden_class . '">' . $array_name . '</span><span class="dd-code-arrow"> => </span>';
        } else {
            $key = '<span class="dd-code-array-key ' . $hidden_class . '">"' . $array_name . '"</span><span class="dd-code-arrow"> => </span>';
        }

        if ($value === null) {
            $this->dd .= '<p title="' . gettype($value) . '"' . $this->ddSpace() . ' class="dd-code-var ' . $hidden_class . '">' . $key . 'null</p>';
            $this->dd .= '</div>';
            return;
        }

        if ($value === false) {
            $this->dd .= '<p title="' . gettype($value) . '"' . $this->ddSpace() . ' class="dd-code-var ' . $hidden_class . '">' . $key . 'false</p>';
            $this->dd .= '</div>';
            return;
        }

        if ($value === true) {
            $this->dd .= '<p title="' . gettype($value) . '"' . $this->ddSpace() . ' class="dd-code-var ' . $hidden_class . '">' . $key . 'true</p>';
            $this->dd .= '</div>';
            return;
        }

        if (is_callable($value)) {
            $this->dd .= '<p title="' . gettype($value) . '"' . $this->ddSpace() . ' class="dd-code-var ' . $hidden_class . '">' . $key . print_r($value, true) . '</p>';
            $this->dd .= '</div>';
            return;
        }

        if (is_numeric($value) and $type !== 'string') {
            $this->dd .= '<p title="' . gettype($value) . '"' . $this->ddSpace() . ' class="dd-code-var ' . $hidden_class . '">' . $key . $value . '</p>';
            $this->dd .= '</div>';
            return;
        }

        if (is_string($value)) {
            $this->dd .= '<p title="' . gettype($value) . ' - ' . strlen($value) . '"' . $this->ddSpace() . ' class="dd-code-string-value ' . $hidden_class . '">' . $key . '"' . htmlspecialchars($value) . '"</p>';
            $this->dd .= '</div>';
            return;
        }

        if (is_object($value)) $value = (array)$value;

        if (is_array($value)) {
            $token = 'dd' . bin2hex(random_bytes(6));
            $this->tokens[] = $token;

            $this->dd .= '<p class="' . $hidden_class . '" title="' . $type . '"' . $this->ddSpace() . '>' . $key . '<span  data-token="' . $token . '" class="dd-code-type">' . $type . ':' . count($value) . ' </span><span class="dd-code-tags ' . $hidden_class . '">[</span></p>';
            $this->space++;

            foreach ($value as $key => $value) $this->ddValues($value, $key);

            $last_token = array_key_last($this->tokens);
            unset($this->tokens[$last_token]);

            $this->space--;
            $this->dd .= '<p ' . $this->ddSpace() . ' ><span class="dd-code-tags ' . $hidden_class . '">]</span></p>';
        }

        $this->dd .= '</div>';
    }

    protected function ddSpace()
    {
        $margin = $this->space * 20;
        return "style=\"margin-left: {$margin}px;\"";
    }
}
