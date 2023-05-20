<?php
namespace cms_installer\tests;

use cms_installer\tests\test_base;

class informational_test extends test_base
{
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
