<?php
namespace cms_installer\tests;

use function cms_installer\lang;

class matchall_test extends test_base
{
    private $_children;

    public function __construct(...$args)
    {
        $args[1] = '';
        parent::__construct(...$args);
    }

    public function __set(string $key, $value) : void
    {
        switch ($key) {
          case 'recommended': // useless in this context ?
            $this->$key = $value;
            break;
          default:
            parent::__set($key, $value);
        }
    }

    public function execute() : string
    {
        $out = parent::TEST_PASS;
        if ($this->_children) {
            for ($i = 0, $n = count($this->_children); $i < $n; ++$i) {
                $res = $this->_children[$i]->run();
                if ($res == parent::TEST_FAIL) {
                    // test failed.... if this test is not required, we can continue
                    if ($this->required) {
                        return $res;
                    }
                    $out = parent::TEST_WARN;
                }
            }
        }
        return $out;
    }

    public function add_child(test_base $obj)
    {
        if (!is_array($this->_children)) {
            $this->_children = [];
        }
        $this->_children[] = $obj;
    }

    public function msg()
    {
        switch ($this->status) {
        case parent::TEST_FAIL:
            for ($i = 0, $n = count($this->_children); $i < $n; ++$i) {
                $obj = $this->_children[$i];
                if ($obj->status == parent::TEST_FAIL) {
                    if ($obj->fail_msg) {
                        return $obj->fail_msg;
                    }
                    if ($obj->fail_key) {
                        return lang($obj->fail_key);
                    }
                }
            }
            break;

        case parent::TEST_WARN:
            for ($i = 0, $n = count($this->_children); $i < $n; ++$i) {
                $obj = $this->_children[$i];
                if ($obj->status == parent::TEST_FAIL) {
                    if ($obj->warn_msg) {
                        return $obj->warn_msg;
                    }
                    if ($obj->warn_key) {
                        return lang($obj->warn_key);
                    }
                }
            }
        }

        return parent::msg();
    }
} // class
