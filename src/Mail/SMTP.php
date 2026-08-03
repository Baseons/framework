<?php

namespace Baseons\Mail;

use Exception;
use InvalidArgumentException;
use Throwable;

class SMTP
{
    private $connection = null;
    public array $config;

    private int $timeout = 30;
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private bool $auth;
    private string $encryption;

    public function __construct(string|null $connection = null)
    {
        if ($connection === null) $connection = config()->mail('default');

        if (empty($connection)) throw new Exception('E-mail configuration not found');

        $this->config = config()->mail('connections.' . $connection . '.send', []);

        if (empty($this->config)) throw new Exception('E-mail configuration not found');

        $requireds = ['host', 'username', 'password'];

        foreach ($requireds as $required) if (empty($this->config[$required])) {
            throw new InvalidArgumentException(sprintf('%s connection required', $required));
        }

        if (empty($this->config['port'])) $this->config['port'] = 465;
        if (empty($this->config['auth'])) $this->config['auth'] = 'login';
        if (empty($this->config['encryption'])) $this->config['encryption'] = $this->config['port'] == 465 ? 'ssl' : 'tls';
        if (empty($this->config['from_address'])) $this->config['from_address'] = $requireds['username'];
        if (empty($this->config['from_name'])) $this->config['from_name'] = null;

        $this->config['auth'] = strtolower($this->config['auth']);
        $this->config['encryption'] = strtolower($this->config['encryption']);

        $this->host = $this->config['host'];
        $this->port = $this->config['port'];
        $this->auth =  $this->config['auth'];
        $this->username = $this->config['username'];
        $this->password = $this->config['password'];
        $this->encryption = $this->config['encryption'];

        if ($this->encryption == 'ssl' or $this->port == 465) {
            $this->host = strpos($this->host, 'ssl://') ? $this->host : 'ssl://' . $this->host;
            $this->encryption = 'ssl';
        }

        $context = [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
                'crypto_method' => $this->cryptoMethod()
            ]
        ];

        $this->connection = stream_socket_client(sprintf('%s:%d', $this->host, $this->port), $errorCode, $errorMessage, $this->timeout, STREAM_CLIENT_CONNECT, stream_context_create($context));

        if (!is_resource($this->connection)) throw new Exception('Failed connection');

        $response = $this->read();

        if ($this->status($response) !== 220) throw new Exception('Failed connection');

        $this->helo();

        if ($this->encryption == 'tls') {
            $starttls = $this->send('STARTTLS');

            if ($this->status($starttls) !== 220) throw new Exception('Failed connection (STARTTLS) - ' . $this->text($starttls));

            if (!stream_socket_enable_crypto($this->connection, true, $this->cryptoMethod())) throw new Exception('Failed enable crypto');

            $this->helo();
        }

        $this->auth();

        return $this;
    }

    private function helo()
    {
        $ehlo = $this->send('EHLO ' .  $this->host);

        if ($this->status($ehlo) !== 250) {
            $helo = $this->send('HELO ' .  $this->host);
            if ($this->status($helo) !== 250) throw new Exception('Failed connection (EHLO / HELO) - ' . $this->text($helo));
        }

        return true;
    }

    private function cryptoMethod()
    {
        if ($this->encryption == 'tls') {
            $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;

            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
            }

            return $crypto_method;
        }

        return STREAM_CRYPTO_METHOD_SSLv23_SERVER | STREAM_CRYPTO_METHOD_SSLv2_CLIENT | STREAM_CRYPTO_METHOD_SSLv3_CLIENT;
    }

    private function auth()
    {
        if ($this->auth == 'login') {
            $login = $this->send('AUTH LOGIN');
            if ($this->status($login) !== 334) throw new Exception('Failed connection login - ' . $this->text($login));

            $username = $this->send(base64_encode($this->username));
            if ($this->status($username) !== 334) throw new Exception('Failed connection login username - ' . $this->text($username));

            $password = $this->send(base64_encode($this->password));
            if ($this->status($password) !== 235) throw new Exception('Failed connection login password - ' . $this->text($password));
        } else if ($this->auth == 'plain') {
            $plain = $this->send('AUTH PLAIN');
            if ($this->status($plain) !== 334) throw new Exception('Failed connection login - ' . $this->text($plain));

            $auth =  $this->send(base64_encode("\0" . $this->username . "\0" . $this->password));
            if ($this->status($auth) !== 235) throw new Exception('Failed connection login - ' . $this->text($auth));
        } else if ($this->auth == 'xoauth2') {
            $auth =  $this->send('AUTH XOAUTH2 ' . base64_encode('user=' . $this->username . "\001auth=Bearer " . $this->password . "\001\001"));

            if ($this->status($auth) !== 235) throw new Exception('Failed connection login - ' . $this->text($auth));
        } else {
            throw new InvalidArgumentException('Invalid authentication method');
        }

        $from = $this->send(sprintf("MAIL FROM: <%s>", $this->username));
        if ($this->status($from) !== 250) throw new Exception('Failed to set \'from email\' - ' . $this->text($from));
    }

    private function status(string $data)
    {
        if (strlen($data) < 3) return null;

        return (int)substr($data, 0, 3);
    }

    private function text(string $data)
    {
        return substr($data, 4);
    }

    public function read()
    {
        $meta = stream_get_meta_data($this->connection);

        if ($meta["eof"]) throw new Exception("Connection closed");

        $messages = [];

        while (true) {
            $response = fgets($this->connection, 512);

            if (strlen($response) < 3) return '';

            if (strlen($response) == 3) {
                break;
            } else {
                array_push($messages, $response);

                if ($response[3] == " ")  break;
            }
        }

        return end($messages);
    }

    public function send(string $data)
    {
        $meta = stream_get_meta_data($this->connection);

        if ($meta["eof"]) throw new Exception("Connection closed");

        fputs($this->connection, $data .  "\r\n");

        return $this->read();
    }

    public function sendBuild(array $recipients, string $content)
    {
        if (!count($recipients) or empty($content)) return false;

        foreach ($recipients as $recipient) {
            $result = $this->send($recipient);
            if ($this->status($result) !== 250) throw new Exception('Invalid recipient e-mail - ' . $this->text($result));
        }

        $result = $this->send('DATA');
        if ($this->status($result) !== 354) throw new Exception('Failed to send data - ' . $this->text($result));

        $result = $this->send($content);
        if ($this->status($result) !== 250) throw new Exception('Failed to send e-mail - ' . $this->text($result));

        return true;
    }

    private function close()
    {
        if (is_resource($this->connection)) {
            try {
                $this->send('QUIT');

                fclose($this->connection);
            } catch (Throwable $error) {
                // skip error
            }
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
