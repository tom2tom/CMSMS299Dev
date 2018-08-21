<?php

namespace __installer\tests;

use __installer\utils;


// just like a boolean test, but uses TEST_WARN instaed of TESt_FAIL
class warning_test extends test_base
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
    return self::TEST_WARN;
  }
}
