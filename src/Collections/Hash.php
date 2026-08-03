<?php

namespace Baseons\Collections;

use InvalidArgumentException;
use RuntimeException;

class Hash
{
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
    public static function encrypt(string $value, string $key, string $cipher = 'AES-256-GCM')
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
    public static function decrypt(string $value, string $key, string $cipher = 'AES-256-GCM')
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

    public static function otp()
    {
        return new OTP();
    }

    private static function cypher(string|null $cipher = 'AES-256-GCM')
    {
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

    public static function jwtEncode(array $payload, string $secret, string $alg = 'HS256')
    {
        $alg = strtoupper($alg);

        $algorithms = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
        ];

        if (!isset($algorithms[$alg])) throw new InvalidArgumentException('Algoritmo JWT não suportado.');

        $header = [
            'typ' => 'JWT',
            'alg' => $alg,
        ];

        $base64UrlHeader = self::base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signature = hash_hmac($algorithms[$alg], $base64UrlHeader . '.' . $base64UrlPayload, $secret, true);

        $base64UrlSignature = self::base64UrlEncode($signature);

        return implode('.', [
            $base64UrlHeader,
            $base64UrlPayload,
            $base64UrlSignature
        ]);
    }


    public static function jwtDecode(string $token, string $secret)
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) return null;

        [$base64UrlHeader, $base64UrlPayload, $base64UrlSignature] = $parts;

        $headerJson = self::base64UrlDecode($base64UrlHeader);
        $payloadJson = self::base64UrlDecode($base64UrlPayload);

        if ($headerJson === null || $payloadJson === null) return null;

        $header = json_decode($headerJson, true);
        $payload = json_decode($payloadJson, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($header) || !is_array($payload)) return null;

        $algorithms = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
        ];

        $alg = $header['alg'] ?? null;

        if (!$alg || !isset($algorithms[$alg])) return null;

        $expectedSignature = hash_hmac($algorithms[$alg], $base64UrlHeader . '.' . $base64UrlPayload, $secret, true);

        $expectedSignature = self::base64UrlEncode($expectedSignature);

        if (!hash_equals($expectedSignature, $base64UrlSignature)) return null;

        $now = time();

        if (isset($payload['exp']) and is_numeric($payload['exp']) and $payload['exp'] < $now) return null;
        if (isset($payload['nbf']) and is_numeric($payload['nbf']) and $payload['nbf'] > $now) return null;

        // iat = issued at
        if (isset($payload['iat']) and is_numeric($payload['iat']) and $payload['iat'] > $now) return null;

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): ?string
    {
        $data = strtr($data, '-_', '+/');

        $padding = strlen($data) % 4;

        if ($padding > 0) $data .= str_repeat('=', 4 - $padding);

        $decoded = base64_decode($data, true);

        return $decoded === false ? null : $decoded;
    }
}
