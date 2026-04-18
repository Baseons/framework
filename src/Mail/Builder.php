<?php

namespace Baseons\Mail;

use Exception;
use InvalidArgumentException;

class Builder
{
    private array $recipients = [];
    private array $attachments = [];
    private string $body = '';

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

    public function attachment(string $path, string|null $name = null)
    {
        $this->attachments[] = [
            'path' => $path,
            'name' => $name
        ];

        return $this;
    }

    public function build()
    {
        if (!count($this->recipients)) throw new InvalidArgumentException('No recipients added: to, bcc or cc');

        $content = '';
        $boundary = md5(uniqid());
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

        if (array_key_exists('Date', $this->headers)) $this->header('Date', date('r'));

        $this->header('Content-Type', !count($this->attachments) ? sprintf('%s; boundary="%s"', 'multipart/alternative', $boundary) : sprintf('%s; boundary="%s"', 'multipart/mixed', $boundary));

        foreach ($this->headers as $key => $value) $content .= $this->line($key . ': ' . $value);

        // attachments
        foreach ($this->attachments as $attachment) {
            if (!file_exists($attachment['path'])) throw new Exception('File not found: ' . $attachment['path']);

            $name = empty($attachment['name']) ? basename($attachment['path']) : $attachment['name'];

            $content .= $this->line('');
            $content .= $this->line('--' . $boundary);
            $content .= "Content-Type: application/octet-stream; name=\"$name\"\r\n";
            $content .= "Content-Disposition: attachment; filename=\"$name\"\r\n";
            $content .= "Content-Transfer-Encoding: base64\r\n";
            $content .= $this->line('');
            $content .= $this->line(base64_encode(file_get_contents($attachment['path'])));
        }

        // body
        if (!empty($this->body)) {
            $content .= $this->line('');
            $content .= $this->line('--' . $boundary);

            $content .= $this->body;
        }

        if (!empty($content)) {
            $content .= $this->line('');
            $content .= $this->line('--' . $boundary . '--');
        }

        return [
            'content' => $content . '.',
            'recipients' => $recipients
        ];
    }

    public function send(string|null $connection = null)
    {
        $smtp = Mail::smtp($connection);

        if (!array_key_exists('From', $this->headers))  $this->from($smtp->config['from_address'], $smtp->config['from_name']);

        $build = $this->build();

        return $smtp->sendBuild($build['recipients'], $build['content']);
    }

    private function line(string $value)
    {
        return $value . "\r\n";
    }
}
