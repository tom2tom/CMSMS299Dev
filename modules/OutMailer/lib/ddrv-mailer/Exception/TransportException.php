<?php

namespace Ddrv\Mailer\Exception;

use RuntimeException;

final class TransportException extends RuntimeException
{

    #[\ReturnTypeWillChange]
    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}
