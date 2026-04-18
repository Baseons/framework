<?php

namespace Baseons\Collections;

use DateTime;
use Exception;
use InvalidArgumentException;

class Str
{
    /**
     * @return string
     */
    public static function clearString(string $string, bool $numbers = false, string $separator = ' ')
    {
        $numbers ? $pattern = "/[^a-zA-Z0-9\s]/" : $pattern = "/[^a-zA-Z\s]/";
        $string = trim(preg_replace($pattern, '', $string));
        $string = preg_replace('/( ){2,}/', '$1', $string);

        return str_replace(' ', $separator, $string);
    }

    /**
     * @return string
     */
    public static function slug(string $string, string $separator = '-')
    {
        $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9\s]/', '', $string);
        $string = preg_replace('/\s+/', ' ', $string);

        return trim(str_replace(' ', $separator, $string), $separator);
    }

    /**
     * @return string
     */
    public static function camel(string $string)
    {
        return ucwords($string);
    }

    /**
     * @return int
     */
    public static function numbers(string $string)
    {
        return (int)preg_replace('/[^0-9]/', '', $string);
    }

    /**
     * @return bool
     */
    public static function contains(string $string, string $search)
    {
        return str_contains($string, $search);
    }

    /**
     * @return bool
     */
    public static function start(string $string, string $search)
    {
        return str_starts_with($string, $search);
    }

    /**
     * @return bool
     */
    public static function end(string $string, string $search)
    {
        return str_ends_with($string, $search);
    }

    /**
     * Get the portion of a string before the first occurrence of a given value.
     *
     * @param  string  $subject
     * @param  string  $search
     * @return string
     */
    public static function before($subject, $search)
    {
        if ($search === '') return $subject;

        $result = strstr($subject, (string) $search, true);

        return $result === false ? $subject : $result;
    }

    public static function after($subject, $search)
    {
        return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }

    public static function isIPV6(string|null $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? true : false;;
    }

    public static function isIPV4(string|null $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? true : false;
    }

    /**
     * @param string $background RRGGBB[AA] hexadecimal format
     * @param string $foreground RRGGBB[AA] hexadecimal format
     * @param string $format png or svg format
     * @return string
     */
    public static function qr(string $value, int $size = 450, string $background = 'FFFFFF', string $foreground = '000000', string $format = 'svg')
    {
        $format = strtolower($format);

        if (!in_array($format, ['svg', 'png'])) throw new InvalidArgumentException('Invalid format, only svg or png available.');

        return file_get_contents('https://image-charts.com/chart?' . http_build_query([
            'cht' => 'qr',
            'chs' => ceil($size / 2) . 'x' . ceil($size / 2),
            'chld' => 'H|1',
            'chl' => $value,
            'chof' => '.' . $format,
            'icqrb' => $background,
            'icqrf' => $foreground
        ]));
    }

    /**
     * Calculate the age based on the given birthdate, including years, months, days, hours, minutes, and seconds.
     *
     * @param string|null $date Birthdate
     * @return array
     * @throws Exception If the date format is invalid
     */
    public static function age(string|null $date)
    {
        if (empty($date)) $date = date('Y-m-d H:i:s');

        $time = strtotime($date);

        if ($time === false) throw new Exception('Invalid date format.');

        $birthDate = DateTime::createFromFormat('Y/m/d H:i:s', date('Y/m/d H:i:s', $time));

        if (!$birthDate) throw new Exception('Invalid date format.');

        $currentDate = new DateTime();
        $difference = $currentDate->diff($birthDate);

        $hoursLivedToday = $currentDate->format('H') - $birthDate->format('H');

        if ($hoursLivedToday < 0) $hoursLivedToday += 24;

        $minutesLivedToday = $currentDate->format('i') - $birthDate->format('i');

        if ($minutesLivedToday < 0) {
            $minutesLivedToday += 60;
            $hoursLivedToday--;
        }

        $secondsLivedToday = $currentDate->format('s') - $birthDate->format('s');

        if ($secondsLivedToday < 0) {
            $secondsLivedToday += 60;
            $minutesLivedToday--;
        }

        return [
            'years' => $difference->y,
            'months' => $difference->m,
            'days' => $difference->d,
            'hours' => $hoursLivedToday,
            'minutes' => $minutesLivedToday,
            'seconds' => $secondsLivedToday
        ];
    }

    public static function isCPF(string|null|false $cpf)
    {
        if ($cpf == null or $cpf == false) $cpf = '';

        $cpf = preg_replace('/[^0-9]/is', '', $cpf);

        if (strlen($cpf) != 11) return false;

        if (preg_match('/(\d)\1{10}/', $cpf)) return false;

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++)  $d += $cpf[$c] * (($t + 1) - $c);

            $d = ((10 * $d) % 11) % 10;

            if ($cpf[$c] != $d) return false;
        }

        return true;
    }

    public static function isCNPJ(string|null|false $cnpj)
    {
        if ($cnpj == null or $cnpj == false) $cnpj = '';

        $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);

        if (strlen($cnpj) != 14) return false;

        if (preg_match('/(\d)\1{13}/', $cnpj)) return false;

        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) return false;


        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }

    public static function isDateFormat(string $date, string $format)
    {
        $dateTime = DateTime::createFromFormat($format, $date);

        if ($dateTime && $dateTime->format($format) === $date) return true;

        return false;
    }

    public static function formatSize(int $bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes) {
            return $bytes . ' B';
        } else {
            return '0 B';
        }
    }

    public function urlQueryReplace(string $url, array $query = [], bool $reset = false)
    {
        $query_string = parse_url($url, PHP_URL_QUERY);
        $query_array = [];

        if (!empty($query_string)) {
            if (!$reset) parse_str($query_string, $query_array);

            $url = str_replace([$query_string, '?'], '', $url);
        }

        foreach ($query as $key => $value) $query_array[$key] = $value;

        $query_build = http_build_query($query_array);

        return $url . (!empty($query_build) ? '?' . $query_build : '');
    }

    public function isEmail(mixed $value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? true : false;
    }

    public function isUrl(mixed $value)
    {
        return filter_var($value, FILTER_VALIDATE_URL) ? true : false;
    }
}
