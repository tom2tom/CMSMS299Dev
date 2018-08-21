<?php

namespace __installer\tests;

class matchany_test extends test_base
{
  private $_children;

  public function __construct($name)
  {
    parent::__construct($name,'');
  }

  public function add_child(test_base $obj)
  {
    if( !is_array($this->_children) )
      $this->_children = [];

    $this->_children[] = $obj;
  }

  public function __set($key,$value)
  {
    switch( $key )
      {
      case 'minimum':
      case 'maximum':
      case 'recommended':
      case 'success_key':
      case 'pass_key':
      case 'fail_key':
	$this->$key = $value;
	break;

      default:
	parent::__set($key,$value);
      }
  }


  public function execute()
  {
    if( count($this->_children) )
      {
	for( $i = 0; $i < count($this->_children); $i++ )
	  {
	    $res = $this->_children[$i]->execute();
	    if( $res == self::TEST_PASS )
	      {
		return self::TEST_PASS;
	      }
	  }
      }
    return self::TEST_FAIL;
  }
} //class

