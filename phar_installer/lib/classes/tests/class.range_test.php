<?php
namespace cms_installer\tests;

class range_test extends test_base
{
    public function __set(string $key, $value) : void
    {
        switch ($key) {
      case 'success_key':
        $this->$key = $value;
        break;
      default:
        parent::__set($key, $value);
    }
    }

    public function execute() : string
    {
        $val = $this->returnBytes($this->value);
        if ($this->minimum) {
            $tmp = $this->returnBytes($this->minimum);
            if ($val < $tmp) {
                return parent::TEST_FAIL;
            }
        }
        if ($this->maximum) {
            $tmp = $this->returnBytes($this->maximum);
            if ($val > $tmp) {
                return parent::TEST_FAIL;
            }
        }
        if ($this->recommended) {
            $tmp = $this->returnBytes($this->recommended);
            if ($val < $tmp) {
                return parent::TEST_WARN;
            }
        }
        return parent::TEST_PASS;
    }
}
