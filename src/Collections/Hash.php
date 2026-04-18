<?php

namespace Baseons\Collections;

use InvalidArgumentException;
use RuntimeException;

class Hash
{
    public static function createKey()
    {
        return random_bytes(self::cypher()['size']);
    }

    public static function createPassword(string $password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function checkPassword(string $password, string $hash)
    {
        return password_verify($password, $hash);
    }

    public static function createTokenNumeric(int $length = 20, string $numbers = '0123456789')
    {
        if ($length <= 0) throw new InvalidArgumentException('Token length must be greater than zero');
        if (!ctype_digit($numbers) || empty($numbers)) throw new InvalidArgumentException('Valid numbers string must contain only digits (0-9) and cannot be empty');

        $token = '';
        $maxIndex = strlen($numbers) - 1;

        for ($i = 0; $i < $length; $i++) $token .= $numbers[random_int(0, $maxIndex)];

        return (int)$token;
    }

    public static function createTokenString(int $length = 20, string|null $special = '!@#$%&?', string|null $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', string|null $numbers = '0123456789')
    {
        if ($length <= 0) throw new InvalidArgumentException('Token length must be greater than zero.');
        if (!empty($numbers) and !ctype_digit($numbers)) throw new InvalidArgumentException('Valid numbers string must contain only digits (0-9)');

        $charset =  ($special ?? '') . ($numbers ?? '') . ($characters ?? '');

        if (empty($charset)) throw new InvalidArgumentException('At least one valid character set must be provided.');

        $token = '';
        $maxIndex = strlen($charset) - 1;

        for ($i = 0; $i < $length; $i++) $token .= $charset[random_int(0, $maxIndex)];

        return $token;
    }

    public static function createTokenByString(string $base, int $length = 20, string|null $special = '!@#$%&?', string|null $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', string|null $numbers = '0123456789')
    {
        if ($length <= 0) throw new InvalidArgumentException('Token length must be greater than zero.');
        if (!empty($numbers) and !ctype_digit($numbers)) throw new InvalidArgumentException('Valid numbers string must contain only digits (0-9)');

        $charset = ($special ?? '') . ($numbers ?? '') . ($characters ?? '');

        if (empty($charset)) throw new InvalidArgumentException('At least one valid character set must be provided.');

        $hash = hash('sha256', $base, true);

        $token = '';
        $charsetLength = strlen($charset);

        for ($i = 0; $i < $length; $i++) {
            $byte = ord($hash[$i % strlen($hash)]);
            $token .= $charset[$byte % $charsetLength];
        }

        return $token;
    }

    /**
     * @return string
     */
    public static function encrypt(string $value, string $key,  string|null $cipher = null)
    {
        $cipher = self::cypher($cipher);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher['name']));

        if ($cipher['aead']) {
            $tag = '';
            $encryptedValue = openssl_encrypt($value, $cipher['name'], $key, OPENSSL_RAW_DATA, $iv, $tag);

            if ($encryptedValue === false) throw new RuntimeException('Encryption failed.');

            return base64_encode($iv . $encryptedValue . $tag);
        } else {
            $encryptedValue = openssl_encrypt($value, $cipher['name'], $key, OPENSSL_RAW_DATA, $iv);

            if ($encryptedValue === false) throw new RuntimeException('Encryption failed.');

            $hmac = hash_hmac('sha256', $encryptedValue, $key, true);
            return base64_encode($iv . $hmac . $encryptedValue);
        }
    }

    /**
     * @return string|null
     */
    public static function decrypt(string $value, string $key, string|null $cipher = null)
    {
        $cipher = self::cypher($cipher);
        $decoded = base64_decode($value);
        $ivlen = openssl_cipher_iv_length($cipher['name']);

        if ($cipher['aead']) {
            $taglen = 16;
            $iv = substr($decoded, 0, $ivlen);
            $tag = substr($decoded, -$taglen);
            $ciphertext_raw = substr($decoded, $ivlen, -$taglen);

            return openssl_decrypt($ciphertext_raw, $cipher['name'], $key, OPENSSL_RAW_DATA, $iv, $tag);
        } else {
            $sha2len = 32;
            $iv = substr($decoded, 0, $ivlen);
            $hmac = substr($decoded, $ivlen, $sha2len);
            $ciphertext_raw = substr($decoded, $ivlen + $sha2len);
            $decrypted = openssl_decrypt($ciphertext_raw, $cipher['name'], $key, OPENSSL_RAW_DATA, $iv);

            if ($decrypted === false) return null;

            $calculatedHmac = hash_hmac('sha256', $ciphertext_raw, $key, true);

            return hash_equals($hmac, $calculatedHmac) ? $decrypted : null;
        }
    }

    private static function cypher(string|null $cipher = null)
    {
        $cipher = $cipher === null ? strtolower(Config::app('encryption.cipher') ?? 'aes-256-gcm') : $cipher;

        $supportedCiphers = [
            'aes-128-cbc' => ['size' => 16, 'aead' => false],
            'aes-256-cbc' => ['size' => 32, 'aead' => false],
            'aes-128-gcm' => ['size' => 16, 'aead' => true],
            'aes-256-gcm' => ['size' => 32, 'aead' => true]
        ];

        if (!array_key_exists($cipher, $supportedCiphers)) {
            $ciphers = implode(', ', array_keys(($supportedCiphers)));

            throw new RuntimeException("Unsupported cipher or incorrect key length. Supported ciphers are: {$ciphers}.");
        }

        $supportedCiphers[$cipher]['name'] = $cipher;

        return $supportedCiphers[$cipher];
    }

    public static function otp()
    {
        return new OTP();
    }
}
