<?php

namespace Baseons\View\Compiler;

use Baseons\Collections\Str;

class CompilerPHP
{
    protected string $view;
    protected array $statements = [];

    protected array $tags = [
        'endif',
        'endfor',
        'endforeach',
        'endwhile',
        'break',
        'continue'
    ];

    protected array $echo_tags = [
        'if',
        'else',
        'elseif',
        'foreach',
        'while',
        'for'
    ];

    public function run($view)
    {
        $this->view = $view;

        // php tag replace
        if (str_starts_with($this->view, '<?php')) {
            $this->view = '<?php echo \'<?php\' ?>' . substr($this->view, strlen('<?php'));
        }

        // echo
        $regex = "/{{(.*?)}}/s";

        if (preg_match_all($regex, $this->view, $match)) {
            foreach ($match[0] as $key => $value) {
                $this->view = str_replace($value, preg_replace('/( ){2,}/', '$1', '<?php echo ' . $match[1][$key] . ' ?>'), $this->view);
            }
        }

        // php
        $regex = "/@php(.*?)@endphp/s";
        if (preg_match_all($regex, $this->view, $match)) {
            foreach ($match[0] as $key => $value) {
                $this->view = str_replace($value, '<?php ' . $match[1][$key] . ' ?>', $this->view);
            }
        }

        $this->compilerStatements();

        foreach ($this->statements as $value) {
            // csrf
            if ($value['tag'] == 'csrf') {
                $this->view = str_replace($value['original'], '<?php echo csrf()->token(); ?>', $this->view);
                continue;
            }

            // route
            elseif ($value['tag'] == 'route') {
                if (!empty($value['value'])) {
                    $this->view = str_replace($value['original'], '<?php echo route' . $value['value'] . ' ?>', $this->view);
                }

                continue;
            }

            // old
            if ($value['tag'] == 'old') {
                $this->view = str_replace($value['original'], '<?php echo request()->old(' . trim($value['value'], '\(\)') . ',"")' . ' ?>', $this->view);
                continue;
            }

            // url
            elseif ($value['tag'] == 'url') {
                if (!empty($value['value'])) {
                    $this->view = str_replace($value['original'], '<?php echo request()->url' . $value['value'] . ' ?>', $this->view);
                } else {
                    $this->view = str_replace($value['original'], '<?php echo request()->url(); ?>', $this->view);
                }

                continue;
            }

            if (in_array($value['tag'], $this->tags)) {
                if (!empty($value['value'])) {
                    $this->view = str_replace($value['original'], '<?php ' . $value['tag'] . $value['value'] . ' ?>', $this->view);
                } else {
                    $this->view = str_replace($value['original'], '<?php ' . $value['tag'] . ' ?>', $this->view);
                }
            } elseif (in_array($value['tag'], $this->echo_tags)) {
                if (!empty($value['value'])) {
                    $this->view = str_replace($value['original'], '<?php ' . $value['tag'] . $value['value'] . ' :?>', $this->view);
                } else {
                    $this->view = str_replace($value['original'], '<?php ' . $value['tag'] . ' :?>', $this->view);
                }
            }
        }

        return $this->view;
    }

    private function compilerStatements()
    {
        if (preg_match_all('/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( [\S\s]*? ) \))?/x', $this->view, $matches)) {

            for ($i = 0; isset($matches[0][$i]); $i++) {
                $match = [
                    $matches[0][$i],
                    $matches[1][$i],
                    $matches[2][$i],
                    $matches[3][$i] ?: null,
                    $matches[4][$i] ?: null,
                ];

                // Here we check to see if we have properly found the closing parenthesis by
                // regex pattern or not, and will recursively continue on to the next ")"
                // then check again until the tokenizer confirms we find the right one.
                while (isset($match[4]) &&  Str::end($match[0], ')') &&  !$this->hasEvenNumberOfParentheses($match[0])) {
                    if (($after = Str::after($this->view, $match[0])) === $this->view) {
                        break;
                    }

                    $rest = Str::before($after, ')');

                    if (isset($matches[0][$i + 1]) && Str::contains($rest . ')', $matches[0][$i + 1])) {
                        unset($matches[0][$i + 1]);
                        $i++;
                    }

                    $match[0] = $match[0] . $rest . ')';
                    $match[3] = $match[3] . $rest . ')';
                    $match[4] = $match[4] . $rest;
                }


                if (empty($match[1])) break;


                $this->statements[] = [
                    'tag' => $match[1],
                    'original' => $match[0],
                    'value' => $match[3]
                ];
            }
        };
    }

    /**
     * Determine if the given expression has the same number of opening and closing parentheses.
     *
     * @param  string  $expression
     * @return bool
     */
    protected function hasEvenNumberOfParentheses(string $expression)
    {
        $tokens = token_get_all('<?php ' . $expression);

        if (end($tokens) !== ')') return false;

        $opening = 0;
        $closing = 0;

        foreach ($tokens as $token) {
            if ($token == ')') {
                $closing++;
            } elseif ($token == '(') {
                $opening++;
            }
        }

        return $opening === $closing;
    }

    protected function replaceFirstStatement($search, $replace, $subject, $offset)
    {
        $search = (string) $search;

        if ($search === '')  return $subject;

        $position = strpos($subject, $search, $offset);

        if ($position !== false) {
            return [
                substr_replace($subject, $replace, $position, strlen($search)),
                $position + strlen($replace),
            ];
        }

        return [$subject, 0];
    }
}
