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

        if (!$this->connection) {
            throw new Exception('Failed to connect to IMAP server: ' . imap_last_error());
        }
    }

    public function list($page = 1, $limit = 100)
    {
        $uids = imap_sort($this->connection, SORTDATE, 1, SE_UID);

        if ($uids === false || empty($uids)) return [];

        $pagedUids = array_slice($uids, ($page - 1) * $limit, $limit);

        if (empty($pagedUids)) return [];

        $sequence = implode(',', $pagedUids);
        $overview = imap_fetch_overview($this->connection, $sequence, FT_UID);

        // O imap_fetch_overview não garante a ordem original da string, então reordenamos
        // com base no nosso array paginado para manter do mais novo para o mais velho
        usort($overview, function ($a, $b) use ($pagedUids) {
            $posA = array_search($a->uid, $pagedUids);
            $posB = array_search($b->uid, $pagedUids);
            return $posA <=> $posB;
        });

        return $overview;
    }

    public function info(int $uid)
    {
        $msgNo = imap_msgno($this->connection, $uid);

        if (!$msgNo) return null;

        $header = imap_headerinfo($this->connection, $msgNo);

        return $header === false ? null : $header;
    }

    public function total()
    {
        return imap_num_msg($this->connection);
    }


    public function view(int|array $uid)
    {
        $uidList = is_array($uid) ? implode(',', $uid) : $uid;

        return imap_setflag_full($this->connection, $uidList, "\\Seen", ST_UID) ? true : false;
    }

    public function delete(int $uid)
    {
        $deleted = imap_delete($this->connection, $uid, FT_UID);

        imap_expunge($this->connection);

        return $deleted ? true : false;
    }

    public function body(int $uid)
    {
        $structure = imap_fetchstructure($this->connection, $uid, FT_UID);

        if (!isset($structure->parts) || empty($structure->parts)) return $this->decodeMessage(imap_body($this->connection, $uid, FT_UID), $structure->encoding);

        foreach ($structure->parts as $index => $part) {
            $partNumber = $index + 1;

            if ($part->subtype == 'PLAIN') {
                $data = imap_fetchbody($this->connection, $uid, $partNumber, FT_UID);
                return $this->decodeMessage($data, $part->encoding);
            }

            if ($part->subtype == 'HTML') {
                $data = imap_fetchbody($this->connection, $uid, $partNumber, FT_UID);
                return $this->decodeMessage($data, $part->encoding);
            }
        }

        return null;
    }

    private function decodeMessage($data, $encoding)
    {
        switch ($encoding) {
            case 3: // BASE64
                return trim(base64_decode($data));
            case 4: // QUOTED-PRINTABLE
                return trim(quoted_printable_decode($data));
            default:
                return trim($data);
        }
    }

    public function __destruct()
    {
        if ($this->connection) imap_close($this->connection);
    }
}
