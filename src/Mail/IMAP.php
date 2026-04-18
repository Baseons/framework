<?php

namespace Baseons\Mail;

use Exception;
use InvalidArgumentException;

class IMAP
{
    private $connection;
    private array $config;

    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $protocol;
    private bool $ssl;
    private bool $cert;

    public function __construct(string|null $connection = null)
    {
        if ($connection === null) $connection = config()->mail('default');

        if (empty($connection)) throw new Exception('E-mail configuration not found');

        $this->config = config()->mail('connections.' . $connection . '.read', []);

        if (empty($this->config)) throw new Exception('E-mail configuration not found');

        $requireds = ['host', 'username', 'password'];

        foreach ($requireds as $required) if (empty($this->config[$required])) {
            throw new InvalidArgumentException(sprintf('%s connection required', $required));
        }

        if (empty($this->config['port'])) $this->config['port'] = 993;
        if (empty($this->config['protocol'])) $this->config['protocol'] = $this->config['protocol'] == 993 ? 'imap' : 'pop';

        if (!array_key_exists('cert', $this->config) or $this->config['cert'] === null) $this->config['cert'] = true;
        if (!array_key_exists('ssl', $this->config) or $this->config['ssl'] === null) $this->config['ssl'] = true;

        $this->config['protocol'] = strtolower($this->config['protocol']);

        $this->host = $this->config['host'];
        $this->port = $this->config['port'];
        $this->username = $this->config['username'];
        $this->password = $this->config['password'];
        $this->protocol = $this->config['protocol'];
        $this->ssl = $this->config['ssl'];
        $this->cert = $this->config['cert'];

        if (!in_array($this->protocol, ['pop3', 'imap', 'nntp'])) throw new InvalidArgumentException('Invalid protocol');

        $options = [
            $this->host . ':' . $this->port,
            $this->protocol
        ];

        if ($this->ssl) $options = array_merge($options, [
            'ssl'
        ]);

        $options[] = $this->cert ? 'validate-cert' : 'novalidate-cert';

        $mailbox = '{' . implode('/', $options) . '}INBOX';

        $this->connection = imap_open($mailbox, $this->username, $this->password, options: ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
    }

    public function total()
    {
        return (int)imap_num_msg($this->connection);
    }
}
