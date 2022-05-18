<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Contract\Transport;
use Ddrv\Mailer\Message;

final class FileTransport implements Transport
{
    /**
     * @var string
     */
    private $dir;

    #[\ReturnTypeWillChange]
    public function __construct($dir)
    {
        if ($dir && !is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $this->dir = $dir;
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message)
    {
        if (!is_dir($this->dir)) {
            return false;
        }
        $content = $message->getHeadersRaw() . "\r\n\r\n" . $message->getBodyRaw();
        foreach ($message->getRecipients() as $email) {
            $arr = explode('@', $email);
            $user = $arr[0];
            $host = $arr[1];
            $dir = implode(DIRECTORY_SEPARATOR, array($this->dir, $host, $user));
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $num = 1;
            do {
                $prefix = 'mail_' . date('YmdHis');
                $suffix = str_pad($num, 5, '0', STR_PAD_LEFT);
                $file = implode(DIRECTORY_SEPARATOR, array($dir, $prefix . '_' . $suffix . '.eml'));
                $num++;
            } while (file_exists($file));
            file_put_contents($file, $content);
        }
        return true;
    }
}
