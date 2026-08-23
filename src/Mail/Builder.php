<?php

namespace Baseons\Mail;

use Baseons\Collections\Hash;
use Exception;
use InvalidArgumentException;

class Builder
{
    private array $recipients = [];
    private array $attachments = [];
    private array $embedded = [];
    private string $body = '';
    private \Baseons\Mail\SMTP|null $smtp = null;

    private array $headers = [
        'MIME-Version' =>  '1.0',
        'X-Mailer' => 'Baseons Framework',
        'X-Priority' => '3'
    ];

    public function content(string $data, bool $html = false)
    {
        $content = $this->line($html ? 'Content-Type: text/html; charset="UTF-8"' : 'Content-Type: text/plain; charset="UTF-8"');
        $content .= $this->line('Content-Transfer-Encoding: quoted-printable');
        $content .= $this->line('');
        $content .= $this->line($html ? quoted_printable_encode($data) : $data);

        $this->body = $content;

        return $this;
    }

    public function view(string $view, array|object $params = [], string|null $path = null)
    {
        $view = view($view, $params, false, $path);

        $content = $this->line('Content-Type: text/html; charset="UTF-8"');
        $content .= $this->line('Content-Transfer-Encoding: quoted-printable');
        $content .= $this->line('');
        $content .= $this->line(quoted_printable_encode($view));

        $this->body = $content;

        return $this;
    }

    public function from(string $email, string|null $name = null)
    {
        if (!empty($name)) $this->header('From', sprintf('"%s" <%s>', $name, $email));
        else $this->header('From', sprintf('<%s>', $email));

        return $this;
    }

    public function to(string $email, string|null $name = null)
    {
        $this->recipients[$email] = [
            'email' => $email,
            'name' => $name,
            'type' => 'to'
        ];

        return $this;
    }

    public function bcc(string $email, string|null $name = null)
    {
        $this->recipients[$email] = [
            'email' => $email,
            'name' => $name,
            'type' => 'bcc'
        ];

        return $this;
    }

    public function cc(string $email, string|null $name = null)
    {
        $this->recipients[$email] = [
            'email' => $email,
            'name' => $name,
            'type' => 'cc'
        ];

        return $this;
    }

    public function subject(string $value)
    {
        $this->header('Subject', $value);

        return $this;
    }

    public function priority(int $value)
    {
        if ($value < 1 or $value > 5) throw new InvalidArgumentException('Invalid priority range 1 - 5');

        $this->header('X-Priority', $value);

        return $this;
    }

    public function notificationTo(string $email, string|null $name = null)
    {
        if ($name === null) $this->header('Disposition-Notification-To', sprintf('<%s>', $email));
        else $this->header('Disposition-Notification-To', sprintf('"%s" <%s>', $name, $email));

        return $this;
    }

    public function replyTo(string $email, string|null $name = null)
    {
        if ($name === null) $this->header('Reply-To', sprintf('<%s>', $email));
        else $this->header('Reply-To', sprintf('"%s" <%s>', $name, $email));

        return $this;
    }

    public function messageId(string $value)
    {
        $this->header('Message-ID', sprintf('<%s>', $value));

        return $this;
    }

    public function header(string $name, string $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function attachment(string $value, string|null $name = null)
    {
        $type = storage()->isFilePathOrContent($value);

        if (!$type) throw new Exception('File not found' . ($type == 'path' ? ': ' . $value : ''));

        if ($type == 'path') {
            $content = file_get_contents($value);

            if ($name === null) $name = basename($value);
        } else {
            $content = $value;

            if ($name === null) $name = Hash::createTokenString(40, null, 'abcdefghijklmnopqrstuvwxyz', null) . '.' . mime()->originalExtension($content);
        }

        $this->attachments[] = [
            'name' => $name,
            'content' => $content
        ];

        return $this;
    }

    public function embed(string $value, string|null $name = null): string
    {
        $type = storage()->isFilePathOrContent($value);

        if (!$type) throw new Exception('File not found' . ($type == 'path' ? ': ' . $value : ''));

        if ($type == 'path') {
            $content = file_get_contents($value);
            if ($name === null) $name = basename($value);
        } else {
            $content = $value;
            $extension = mime()->originalExtension($content);

            if ($name === null) $name = Hash::createTokenString(40, null, 'abcdefghijklmnopqrstuvwxyz', null) . '.' . $extension;
        }

        $contentHash = md5($content);

        foreach ($this->embedded as $file) if ($file['hash'] === $contentHash) return 'cid:' . $file['cid'];

        $domain = parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'baseons.mail';
        $cid = md5(uniqid('', true)) . '@' . $domain;

        $mimeType = mime()->originalMime($content) ?? 'application/octet-stream';

        $this->embedded[] = [
            'name' => $name,
            'content' => $content,
            'cid' => $cid,
            'mime' => $mimeType,
            'hash' => $contentHash
        ];

        return 'cid:' . $cid;
    }

    public function build()
    {
        if (!count($this->recipients)) throw new InvalidArgumentException('No recipients added: to, bcc or cc');

        $content = '';
        $recipients = [];

        $to = [];
        $bcc = [];
        $cc = [];

        foreach ($this->recipients as $recipient) {
            $recipients[] = sprintf('RCPT TO: <%s>', $recipient['email']);

            $value = !empty($recipient['name']) ? sprintf('"%s" <%s>', $recipient['name'], $recipient['email']) : sprintf('<%s>', $recipient['email']);

            match (true) {
                $recipient['type'] == 'to' => $to[] = $value,
                $recipient['type'] == 'bcc' => $bcc[] = $value,
                $recipient['type'] == 'cc' => $cc[] = $value
            };
        }

        // headers
        if (count($to)) $this->header('To', implode(',', $to));
        if (count($bcc)) $this->header('Bcc', implode(',', $bcc));
        if (count($cc)) $this->header('Cc', implode(',', $cc));

        if (!array_key_exists('Date', $this->headers)) $this->header('Date', date('r'));

        $hasAttachments = count($this->attachments) > 0;
        $hasEmbedded = count($this->embedded) > 0;

        $boundaryMixed = '=_mix_' . md5(uniqid('mix', true));
        $boundaryRelated = '=_rel_' . md5(uniqid('rel', true));

        if ($hasAttachments) {
            $this->header('Content-Type', sprintf('multipart/mixed; boundary="%s"', $boundaryMixed));
        } elseif ($hasEmbedded) {
            $this->header('Content-Type', sprintf('multipart/related; boundary="%s"', $boundaryRelated));
        } else {
            $this->header('Content-Type', sprintf('multipart/alternative; boundary="%s"', $boundaryMixed));
        }

        foreach ($this->headers as $key => $value) $content .= $this->line($key . ': ' . $value);

        if ($hasAttachments) {
            $content .= $this->line('');
            $content .= $this->line('--' . $boundaryMixed);

            if ($hasEmbedded) {
                $content .= $this->line(sprintf('Content-Type: multipart/related; boundary="%s"', $boundaryRelated));
                $content .= $this->line('');
            }
        }

        $activeBoundary = $hasEmbedded ? $boundaryRelated : $boundaryMixed;

        // body
        if (!empty($this->body)) {
            $content .= $this->line('');
            $content .= $this->line('--' . $activeBoundary);
            $content .= $this->body;
        }

        // embedded
        if ($hasEmbedded) {
            foreach ($this->embedded as $embed) {
                $content .= $this->line('');
                $content .= $this->line('--' . $boundaryRelated);
                $content .= "Content-Type: {$embed['mime']}; name=\"{$embed['name']}\"\r\n";
                $content .= "Content-ID: <{$embed['cid']}>\r\n";
                $content .= "Content-Disposition: inline; filename=\"{$embed['name']}\"\r\n";
                $content .= "Content-Transfer-Encoding: base64\r\n";
                $content .= $this->line('');
                $content .= $this->line(chunk_split(base64_encode($embed['content'])));
            }

            $content .= $this->line('');
            $content .= $this->line('--' . $boundaryRelated . '--');
        }

        // attachments
        if ($hasAttachments) {
            foreach ($this->attachments as $attachment) {
                $content .= $this->line('');
                $content .= $this->line('--' . $boundaryMixed);
                $content .= "Content-Type: application/octet-stream; name=\"{$attachment['name']}\"\r\n";
                $content .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n";
                $content .= "Content-Transfer-Encoding: base64\r\n";
                $content .= $this->line('');
                $content .= $this->line(chunk_split(base64_encode($attachment['content'])));
            }
            $content .= $this->line('');
            $content .= $this->line('--' . $boundaryMixed . '--');
        } elseif (!$hasEmbedded && !empty($content)) {
            $content .= $this->line('');
            $content .= $this->line('--' . $boundaryMixed . '--');
        }

        return [
            'content' => $content . '.',
            'recipients' => $recipients
        ];
    }

    public function send(string|null $connection = null)
    {
        $this->smtp = Mail::smtp($connection);

        if (!array_key_exists('From', $this->headers)) $this->from($this->smtp->config['from_address'], $this->smtp->config['from_name']);

        $build = $this->build();

        return $this->smtp->sendBuild($build['recipients'], $build['content']);
    }

    private function line(string $value)
    {
        return $value . "\r\n";
    }
}
