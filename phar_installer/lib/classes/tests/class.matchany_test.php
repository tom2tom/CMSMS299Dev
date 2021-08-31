<?php
namespace cms_installer\tests;

use function cms_installer\lang;

class matchany_test extends test_base
{
    private $_children;

    public function __construct(...$args)
    {
        $args[1] = '';
        parent::__construct(...$args);
        $this->pass_key = false;
    }

    public function __set(string $key, $value)
    {
        switch ($key) {
      case 'recommended': //unused
        $this->$key = $value;
        break;
      case 'success_key':
        $key = 'pass_key';
        // no break here
      default:
        parent::__set($key, $value);
    }
    }

    public function execute() : string
    {
        if (($n = count($this->_children))) {
            for ($i = 0; $i < $n; ++$i) {
                if ($this->_children[$i]->execute() == parent::TEST_PASS) {
                    $this->pass_key = $i;
                    return parent::TEST_PASS;
                }
            }
        }
        return parent::TEST_FAIL;
    }

    public function add_child(test_base $obj)
    {
        if (!is_array($this->_children)) {
            $this->_children = [];
        }
        $this->_children[] = $obj;
    }

    /**
     * @since 1.4
     * @return mixed
     */
    public function msg()
    {
        if ($this->pass_key !== false) {
            $ob = $this->_children[$this->pass_key];
            if (!empty($ob->name_key)) {
                $msg = lang($ob->name_key);
            } else {
                $msg = $ob->name;
            }
            if (!empty($ob->pass_key)) {
                $tmp = $ob->pass_msg;
                if (strpos($tmp, '%s') !== false) {
                    return lang($tmp, $msg);
                } else {
                    return lang($tmp);
                }
            } elseif (!empty($ob->pass_msg)) {
                $tmp = $ob->pass_msg;
                if (strpos($tmp, '%s') !== false) {
                    return sprintf($tmp, $msg);
                } else {
                    return $tmp;
                }
            }
            return $msg;
        }

        return parent::msg();
    }

    /**
     * @since 1.4
     * @return mixed
     */
    public function get_passed()
    {
        if ($this->pass_key !== false) {
            return $this->_children[$this->pass_key];
        }
        return null;
    }
} //class
