<?php

namespace Ddrv\Mailer\Transport;

use Ddrv\Mailer\Contract\Transport;
use Ddrv\Mailer\Message;

final class FakeTransport implements Transport
{

    /**
     * @inheritDoc
     */
    public function send(Message $message)
    {
        return true;
    }
}
