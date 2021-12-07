<?php

namespace Ddrv\Mailer\Contract;

use Ddrv\Mailer\Message;

interface Spool
{

    /**
     * @param Message $message
     * @param int $attempt
     */
    public function push(Message $message, $attempt);

    /**
     * @param int $attempt
     * @return Message|null
     */
    public function pull($attempt);
}
