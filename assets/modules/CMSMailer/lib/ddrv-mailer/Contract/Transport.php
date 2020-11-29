<?php

namespace Ddrv\Mailer\Contract;

use Ddrv\Mailer\Message;

interface Transport
{
    /**
     * @param Message $message
     * @return bool true or not at all
     * @throws RecipientsListEmptyException, Exception upon error
     */
    public function send(Message $message);
}
