<?php

namespace Baseons\Http;

use Baseons\Collections\Hash;
use Exception;
use Baseons\Collections\Mime;

class Upload
{
    protected array|null $input = null;

    public function __construct(string|array $input)
    {
        $this->input = is_array($input) ? $input : request()->file($input, []);
    }

    /**
     * @return string|array|null
     */
    public function errors()
    {
        if (!is_array($this->input) or empty($this->input['tmp_name'])) return null;

        if (is_array($this->input['error'])) {
            $errors = [];

            foreach ($this->input['error'] as $key => $error) if ($error !== 0) $errors[$key] = $error;

            return $errors;
        }

        return $this->input['error'];
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        if (!is_array($this->input) or empty($this->input['tmp_name'])) return false;

        if (is_string($this->input['name'])) {
            if (!is_uploaded_file($this->input['tmp_name']) or !empty($this->input['error'])) return false;
        } else {
            foreach ($this->input['tmp_name'] as $key => $tmp_name) {
                if (!is_uploaded_file($tmp_name) or !empty($this->input[$key]['error'])) return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isMultiple()
    {
        if (empty($this->input['name']) or is_string($this->input['name'])) return false;

        return true;
    }

    /**
     * @return bool
     */
    public function isImage()
    {
        if (!is_array($this->input) or empty($this->input['tmp_name'])) return null;

        if (is_string($this->input['tmp_name'])) return mime()->isImage($this->input['tmp_name']);

        foreach ($this->input['tmp_name'] as $path) if (!mime()->isImage($path)) return false;

        return true;
    }

    /**
     * @return string|int
     */
    public function size(bool $format = false)
    {
        if (!is_array($this->input) or empty($this->input['tmp_name'])) return $format ? str()->formatSize(0) : 0;

        $size = is_array($this->input['size']) ? array_sum($this->input['size']) : $this->input['size'];

        return $format ? str()->formatSize($size) : $size;
    }

    /**
     * @return string|array|null
     */
    public function extension()
    {
        if (!is_array($this->input) or empty($this->input['tmp_name'])) return null;

        if (is_array($this->input['name'])) {
            $extensions = [];

            foreach ($this->input['name'] as $file) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $extensions[] = $extension;
            }

            return $extensions;
        }

        return pathinfo($this->input['name'], PATHINFO_EXTENSION);
    }

    /**
     * @return string|array|null
     */
    public function originalExtension()
    {
        if (!is_array($this->input) or empty($this->input['tmp_name'])) return null;

        if (is_array($this->input['tmp_name'])) {
            $extensions = [];

            foreach ($this->input['tmp_name'] as $file) {
                $extension = mime()->originalExtension($file) ?? pathinfo($file, PATHINFO_EXTENSION);
                $extensions[] = $extension;
            }

            return $extensions;
        }

        return mime()->originalExtension($this->input['tmp_name']) ?? pathinfo($this->input['tmp_name'], PATHINFO_EXTENSION);
    }

    /**
     * @return string|array|null
     */
    public function originalName()
    {
        if (!is_array($this->input) or empty($this->input['tmp_name'])) return null;

        return $this->input['name'];
    }

    /**
     * @return string|array|null
     */
    public function baseName()
    {
        if (!is_array($this->input) or empty($this->input['tmp_name'])) return null;

        if (is_array($this->input['name'])) {
            $names = [];

            foreach ($this->input['name'] as $file) {
                $extension = pathinfo($file, PATHINFO_FILENAME);

                if (!in_array($extension, $names)) $names[] = $extension;
            }

            return $names;
        }

        return pathinfo($this->input['name'], PATHINFO_FILENAME);
    }

    /**
     * @return string|array|null
     */
    public function path()
    {
        if (!is_array($this->input) or empty($this->input['tmp_name'])) return null;

        return $this->input['tmp_name'];
    }

    /**
     * @return string|array|null
     */
    public function save(string $path, string|array|null $name = null)
    {
        if (!is_array($this->input) or empty($this->input['tmp_name'])) return null;

        storage()->makeDirectory($path);

        if (is_string($this->input['tmp_name'])) {
            if (is_string($name)) $file_name = $name;
            elseif (is_array($name) and array_key_exists(0, $name)) $file_name = $name[0];
            else {
                $extension = mime()->originalExtension($this->input['tmp_name']);
                $extension = $extension ? '.' . $extension : '';

                $file_name = Hash::createTokenString(special: null, numbers: null, characters: 'abcdefghijklmnopqrstuvwxyz') . $extension;
            }

            $result = move_uploaded_file($this->input['tmp_name'], $path . DIRECTORY_SEPARATOR . $file_name);

            if ($result) return $file_name;

            return null;
        } elseif (is_array($this->input['tmp_name'])) {
            $return = [];

            foreach ($this->input['tmp_name'] as $key => $value) {
                if (is_array($name) and array_key_exists($key, $name)) $file_name = $name[$key];
                else {
                    $extension = mime()->originalExtension($this->input['tmp_name'][$key]);
                    $extension = $extension ? '.' . $extension : '';

                    $file_name = Hash::createTokenString(special: null, numbers: null, characters: 'abcdefghijklmnopqrstuvwxyz') . $extension;
                }

                $result = move_uploaded_file($value, $path . DIRECTORY_SEPARATOR . $file_name);

                if ($result) $return[$key] = $file_name;
            }

            return count($return) ? $return : null;
        }
    }

    /**
     * @return string|array|null
     */
    public function saveImage(string $path, string|array|null $name = null, int|array|null $resize = null, bool $resize_adaptive = true, int|null $quality = 100, string|null $format = null)
    {
        if (!is_array($this->input) or empty($this->input['tmp_name'])) return null;

        if (!extension_loaded('imagick')) throw new Exception('imagick extension not loaded');

        $return = [];
        $multiple = $this->isMultiple();
        $files = $this->toArray();

        if ($name === null) $name = [];
        if (is_string($name)) $name = [$name];

        storage()->makeDirectory($path);

        foreach ($files as $key => $file) {
            if ($name) {
                if (is_string($name)) $file_name = $name;
                elseif (is_array($name) and array_key_exists($key, $name)) $file_name = $name[$key];
                else $file_name = $file['name'];
            } else {
                $extension = $format ? $format : mime()->originalExtension($file['tmp_name']);
                $file_name = Hash::createTokenString(special: null, numbers: null, characters: 'abcdefghijklmnopqrstuvwxyz') . '.' . $extension;
            }

            $save_path = $path . DIRECTORY_SEPARATOR . $file_name;

            if (move_uploaded_file($file['tmp_name'], $save_path)) {
                $imagick = new \Imagick;
                $imagick->readImage($save_path);

                if ($format) $imagick->setImageFormat($format);
                if ($quality) $imagick->setImageCompressionQuality($quality);

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

                    $imagick->adaptiveResizeImage($width, $height, $resize_adaptive);
                }

                $imagick->writeImages($save_path, true);
                $imagick->clear();
                $imagick->destroy();

                if (!$multiple) return $file_name;

                $return[$key] = $file_name;
            }
        }

        return count($return) ? $return : null;
    }

    /**
     * @return array
     */
    private function toArray()
    {
        if (empty($this->input['name'])) return [];
        if (is_string($this->input['name'])) return [$this->input];

        $data = [];

        foreach ($this->input['name'] as $key => $value) $data[$key] = [
            'name' => $this->input['name'][$key],
            'full_path' => $this->input['full_path'][$key],
            'type' => $this->input['type'][$key],
            'tmp_name' => $this->input['tmp_name'][$key],
            'error' => $this->input['error'][$key],
            'size' => $this->input['size'][$key]
        ];

        return $data;
    }
}
