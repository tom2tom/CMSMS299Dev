<?php

namespace Ddrv\Mailer\Exception;

use Exception;

final class RecipientsListEmptyException extends Exception
{

    public function __construct()
    {
        parent::__construct("Recipients list is empty", 1);
    }
}
