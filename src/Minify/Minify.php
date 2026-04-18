<?php

namespace Baseons\Minify;

use Exception;
use Imagick;

class Minify
{
    public function css(string $css)
    {
        return (new CSS)->add($css)->execute();
    }

    public function js(string $js)
    {
        return (new JS)->add($js)->execute();
    }

    public function img(string $value, int $quality = 75, int|array|null $resize = null)
    {
        $type = storage()->isFilePathOrContent($value);

        if (!$type) return false;

        if ($type == 'path') $value = file_get_contents($value);

        try {
            $imagick = new Imagick();
            $imagick->readImageBlob($value);
            $imagick->setImageCompressionQuality($quality);
            $imagick->stripImage();

            if ($resize !== null) {
                $width = $resize;
                $height = $resize;

                if (is_array($resize)) {
                    if (array_key_exists(1, $resize)) {
                        $width = $resize[0];
                        $height = $resize[1];
                    } else {
                        $width = $resize[0];
                        $height = $resize[0];
                    }
                }

                $imagick->adaptiveResizeImage($width, $height, true);
            }

            return $imagick->getImageBlob();
        } catch (Exception $e) {
            return false;
        }
    }
}
