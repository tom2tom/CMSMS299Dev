<?php

namespace cms_installer\tests;

use cms_installer\utils;

// like a boolean test, but uses TEST_WARN instead of TEST_FAIL
class warning_test extends test_base
{
  public function __construct(...$args)
  {
    $args[1] = (bool)$args[1];
    parent::__construct(...$args);
  }

  public function execute() : string
  {
    $val = utils::to_bool($this->value);
    return ( $val ) ? parent::TEST_PASS : parent::TEST_WARN;
  }
}
