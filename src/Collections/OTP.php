<?php

namespace Baseons\Collections;

class OTP
{
    private const ALPHABET = '!@#$%&?abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public static function createSecret(int $length = 32)
    {
        $secret = '';

        for ($i = 0; $i < $length; $i++) $secret .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];

        return $secret;
    }

    public static function check(string $secret, string $code, int $timeStep = 30, int $digits = 6, int $tolerance = 1)
    {
        for ($i = -$tolerance; $i <= $tolerance; $i++) {
            $expectedCode = self::createTOTP($secret, $timeStep, $digits);

            if ($expectedCode === $code) return true;
        }

        return false;
    }

    public static function createTOTP(string $secret, int $timeStep = 30, int $digits = 6)
    {
        $timeCounter = floor(time() / $timeStep);
        $key = self::base32Decode($secret);
        $binaryCounter = str_pad(pack('N', $timeCounter), 8, "\0", STR_PAD_LEFT);
        $hmac = hash_hmac('sha1', $binaryCounter, $key, true);
        $offset = ord($hmac[strlen($hmac) - 1]) & 0x0F;

        $binaryCode = (ord($hmac[$offset]) & 0x7F) << 24 |
            (ord($hmac[$offset + 1]) & 0xFF) << 16 |
            (ord($hmac[$offset + 2]) & 0xFF) << 8 |
            (ord($hmac[$offset + 3]) & 0xFF);

        return str_pad($binaryCode % pow(10, $digits), $digits, '0', STR_PAD_LEFT);
    }

    public static function createURL(string $secret, string|null $name = null, string|null $label = null)
    {
        $url = sprintf('otpauth://totp%s', $label === null ? '?' : '/' . $label . '?');

        $query = ['secret' => $secret];

        if ($name !== null) $query['issuer'] =  $name;

        return $url . http_build_query($query);
    }

    private static function base32Decode(string $input)
    {
        $binaryString = '';
        $bytes = [];

        foreach (str_split($input) as $char) $binaryString .= str_pad(decbin(strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        foreach (str_split($binaryString, 8) as $byte) $bytes[] = chr(bindec($byte));

        return implode('', $bytes);
    }
}
