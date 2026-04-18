<?php

namespace Baseons\Console\Commands;

use Baseons\Shell\Shell;
use Baseons\Test\Controller;

class CommandTest
{
    public function test()
    {
        $results = (new Controller())->test();

        foreach ($results as $file => $value) {
            foreach ($value as $method => $test) {
                $start = Shell::normal($file . ':' . $test['line'], true, false);
                $start .= Shell::gray($method, false, false);
                $success = true;

                if (is_array($test['asserts']) and count($test['asserts'])) {
                    foreach ($test['asserts'] as $x) {
                        if ($x === false) {
                            $success = false;
                            break;
                        }
                    }
                } else $success = false;

                if ($success) {
                    $end = Shell::green('SUCCESS', true, false);
                } else {
                    $end = Shell::red('FAILED', true, false);
                }

                Shell::list($start, $end)->br();
            }
        }
    }
}
