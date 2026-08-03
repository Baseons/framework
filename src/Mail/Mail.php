<?php

namespace Baseons\Mail;

class Mail
{
    public static function smtp(string|null $connection = null)
    {
        return new SMTP($connection);
    }

    public static function read(string|null $connection = null)
    {
        return new IMAP($connection);
    }

    public static function builder()
    {
        return new Builder();
    }
}
