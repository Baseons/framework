<?php

namespace Baseons\View\Compiler;

class CompilerSections
{
    protected $view;

    public function run($view)
    {
        $this->view = $view;
        $loop = $this->loop();

        if ($loop == true) return $this->view;

        return false;
    }

    protected function loop()
    {
        $regex = "/@yield\((.*?)\)/s";

        if (preg_match_all($regex, $this->view, $match)) {
            $match[0] = array_reverse($match[0]);
            $match[1] = array_reverse($match[1]);

            foreach ($match[0] as $key => $value) $this->replace($match[0][$key], $match[1][$key]);

            return $this->loop();
        }

        $regex = "/@section\((.*?)\)(.*?)@endsection/s";

        if (preg_match_all($regex, $this->view, $match)) $this->view = str_replace($match[0], '', $this->view);

        return true;
    }

    protected function replace(string $replace, $value)
    {
        if (preg_match_all('/(?<!@)@section\(' . $value . '\)(.*?)@endsection/s', $this->view, $match)) {
            $match[0] = array_reverse($match[0]);
            $match[1] = array_reverse($match[1]);

            foreach ($match[0] as $key => $value) {
                $this->view = str_replace($match[0][$key], '', $this->view);
                $this->view = str_replace($replace, trim($match[1][$key]), $this->view);
            }
        } else {
            $this->view = str_replace($replace, '', $this->view);
        }

        return $this->loop();
    }
}
