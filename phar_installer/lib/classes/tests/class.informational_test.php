<?php

use cms_installer\tests\test_base;

namespace cms_installer\tests;

class informational_test extends test_base
{
    #[\ReturnTypeWillChange]
    public function __construct(string $name, $value, string $message = '', string $key = '')
    {
        parent::__construct($name, $value, $key);
        if ($message) {
            $this->msg_key = $message;
        }
    }

    /**
     * Mandatory but irrelevant method
     *
     * @return integer -1 for fail
     */
    public function execute() : string
    {
        return -1;
    }
} // class
