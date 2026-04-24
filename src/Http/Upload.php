<?php

namespace Baseons\Http;

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
        if (!array_key_exists('name', $this->input)) return false;

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

    public function isImage()
    {
        if (empty($this->input['tmp_name'])) return false;

        if (is_string($this->input['tmp_name'])) return Mime::isImage($this->input['tmp_name']);

        foreach ($this->input['tmp_name'] as $path) if (!Mime::isImage($path)) return false;

        return true;
    }

    /**
     * @return string|int
     */
    public function size(bool $format = false)
    {
        if (empty($this->input['name'])) return $format ? str()->formatSize(0) : 0;

        $size = is_array($this->input['size']) ? array_sum($this->input['size']) : $this->input['size'];

        return $format ? str()->formatSize($size) : $size;
    }

    /**
     * @return string|array|null
     */
    public function extension()
    {
        if (empty($this->input['name'])) return null;

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
        if (empty($this->input['tmp_name'])) return null;

        if (is_array($this->input['tmp_name'])) {
            $extensions = [];

            foreach ($this->input['tmp_name'] as $file) {
                $extension = Mime::originalExtension($file) ?? pathinfo($file, PATHINFO_EXTENSION);
                $extensions[] = $extension;
            }

            return $extensions;
        }

        return Mime::originalExtension($this->input['tmp_name']) ?? pathinfo($this->input['tmp_name'], PATHINFO_EXTENSION);
    }

    /**
     * @return string|array|null
     */
    public function originalName()
    {
        if (empty($this->input['name'])) return null;

        return $this->input['name'];
    }

    /**
     * @return string|array|null
     */
    public function baseName()
    {
        if (empty($this->input['name'])) return null;

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
        if (empty($this->input['tmp_name'])) return null;

        return $this->input['tmp_name'];
    }

    /**
     * @return int
     */
    public function save(string $path, string|array|null $name = null)
    {
        storage()->makeDirectory($path);

        $count = 0;

        if (is_string($this->input['tmp_name'])) {
            $x = $this->input['name'];

            if (is_string($name)) $x = $name;
            elseif (is_array($name) and array_key_exists(0, $name)) $x = $name[0];

            $result = move_uploaded_file($this->input['tmp_name'], $path . DIRECTORY_SEPARATOR . $x);

            if ($result) $count++;
        } elseif (is_array($this->input['tmp_name'])) {
            if (is_string($name)) $name = [$name];

            foreach ($this->input['tmp_name'] as $key => $value) {
                $x = $this->input['name'][$key];

                if (is_array($name) and array_key_exists($key, $name)) $x = $name[$key];

                $result = move_uploaded_file($value, $path . DIRECTORY_SEPARATOR . $x);

                if ($result) $count++;
            }
        }

        return $count;
    }

    /**
     * @return string|array|false
     */
    public function saveImage(string $path, string|array|null $name = null, int|array|null $resize = null, bool $resize_adaptive = true, int|null $quality = 100, string|null $format = null)
    {
        if (!extension_loaded('imagick')) throw new Exception('imagick extension not loaded');

        $result = [];
        $multiple = $this->isMultiple();
        $files = !$multiple ? [$this->input] : $this->toArray();

        if ($name === null) $name = [];
        if (is_string($name)) $name = [$name];

        storage()->makeDirectory($path);

        foreach ($files as $key => $file) {
            if ($format) $extension = $format;
            else $extension = array_key_exists($key, $name) ? pathinfo($name[$key], PATHINFO_EXTENSION) : pathinfo($file['name'], PATHINFO_EXTENSION);

            $file_name = array_key_exists($key, $name) ? pathinfo($name[$key], PATHINFO_FILENAME) : pathinfo($file['name'], PATHINFO_FILENAME);
            $file_name .= '.' . $extension;
            $save_path = $path . DIRECTORY_SEPARATOR . $file_name;

            move_uploaded_file($file['tmp_name'], $save_path);

            $imagick = new \Imagick;
            $imagick->readImage($save_path);
            
            if ($format) $imagick->setImageFormat($format);
            if ($quality !== null) $imagick->setImageCompressionQuality($quality);

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

            $result[] = $file_name;
        }

        return count($result) ? $result : false;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if (empty($this->input['name'])) return [];
        if (is_string($this->input['name'])) return $this->input;

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
