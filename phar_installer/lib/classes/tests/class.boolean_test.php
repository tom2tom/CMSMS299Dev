<?php

namespace cms_installer\tests;

use cms_installer\utils;

class boolean_test extends test_base
{
  private $_data = [];

  public function __construct($name,$value)
  {
    $value = (bool)$value;
    parent::__construct($name,$value);
  }

  public function execute()
  {
    $val = utils::to_bool($this->value);
    if( $val ) return self::TEST_PASS;
    return self::TEST_FAIL;
  }
}
