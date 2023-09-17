<?php
namespace cms_installer\tests;

class version_range_test extends test_base
{
    public $success_key;

    public function __set(string $key, $value): void
    {
        switch ($key) {
          case 'success_key':
            $this->$key = $value;
            break;
          default:
            parent::__set($key, $value);
        }
    }

    public function execute(): string
    {
        if ($this->minimum) {
            if (version_compare($this->value, $this->minimum) < 0) {
                return parent::TEST_FAIL;
            }
        }
        if ($this->maximum) {
            if (version_compare($this->value, $this->maximum) > 0) {
                return parent::TEST_FAIL;
            }
        }
        if ($this->recommended) {
            if (version_compare($this->value, $this->recommended) < 0) {
                return parent::TEST_WARN;
            }
        }
        return parent::TEST_PASS;
    }
}
