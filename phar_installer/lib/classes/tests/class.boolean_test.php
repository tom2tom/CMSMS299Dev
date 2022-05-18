<?php
namespace cms_installer\tests;

use function cms_installer\to_bool;

class boolean_test extends test_base
{
    #[\ReturnTypeWillChange]
    public function __construct(...$args)
    {
        $args[1] = (bool)$args[1];
        parent::__construct(...$args);
    }

    public function execute() : string
    {
        $val = to_bool($this->value);
        return ($val) ? parent::TEST_PASS : parent::TEST_FAIL;
    }
}
