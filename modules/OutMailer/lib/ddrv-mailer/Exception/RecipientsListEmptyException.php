<?php

namespace Ddrv\Mailer\Exception;

use Exception;

final class RecipientsListEmptyException extends Exception
{

    #[\ReturnTypeWillChange]
    public function __construct()
    {
        parent::__construct('recipients list is empty', 1);
    }
}
