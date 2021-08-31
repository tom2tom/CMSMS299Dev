<?php
namespace cms_installer\tests;

use cms_installer\http_request;
use Exception;
use function cms_installer\lang;
use function cms_installer\to_bool;

// This method is useless, extension_loaded() is caseless
function test_extension_loaded(string $name) : bool
{
    $a = extension_loaded($name);
    //  if( !$a ) $a = extension_loaded(strtoupper($name));
    return $a;
}

function test_apache_module($name) : bool
{
    if (!$name) {
        return false;
    }
    if (!function_exists('apache_get_modules')) {
        return false;
    }
    $modules = apache_get_modules();
    return in_array($name, $modules);
}

function test_is_false($val) : bool
{
    return (to_bool($val) == false);
}

function test_is_true($val) : bool
{
    return (to_bool($val) == true);
}

function test_remote_file(string $url, int $timeout = 3, string $searchString = '') : bool
{
    $timeout = max(1, min(360, $timeout));
    $req = new http_request();
    $req->setTarget($url);
    $req->setTimeout($timeout);
    $req->execute();
    if ($req->getStatus() != 200) {
        return false;
    }
    if ($searchString && strpos($req->getResult(), $searchString) === false) {
        return false;
    }
    return true;
}

abstract class test_base
{
    const TEST_UNTESTED = 'test_untested';
    const TEST_PASS = 'test_pass';
    const TEST_FAIL = 'test_fail';
    const TEST_WARN = 'test_warn';
    const KEYS = [
    'fail_key',
    'fail_msg',
    'maximum',
    'minimum',
    'msg',
    'msg_key',
    'name',
    'name_key',
    'pass_key',
    'pass_msg',
    'recommended',
    'required',
    'status',
    'value',
    'warn_key',
    'warn_msg',
  ];
    private $_data = [];

    /**
     * @param string $name (if provided) is assumed to be a lang key, and processed immediately
     */
    public function __construct(string $name, $value, string $key = '')
    {
        if (!($name || $key)) {
            throw new Exception(lang('error_test_name'));
        }
        $this->name = ($name) ? lang($name) : '';
        $this->name_key = $key; //possibly empty
        $this->value = $value;
        $this->status = self::TEST_UNTESTED;
        $this->required = 0;
    }

    public function __get(string $key)
    {
        if (!in_array($key, self::KEYS)) {
            throw new Exception(lang('error_invalidkey', $key, __CLASS__));
        }
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
    }

    public function __isset(string $key)
    {
        if (!in_array($key, self::KEYS)) {
            throw new Exception(lang('error_invalidkey', $key, __CLASS__));
        }
        return isset($this->_data[$key]);
    }

    public function __set(string $key, $value)
    {
        if (!in_array($key, self::KEYS)) {
            throw new Exception(lang('error_invalidkey', $key, __CLASS__));
        }
        if (is_null($value) || $value === '') {
            unset($this->_data[$key]);
        } else {
            $this->_data[$key] = $value;
        }
    }

    public function __unset($key)
    {
        unset($this->_data[$key]);
    }

    abstract public function execute() : string;

    public function run()
    {
        $res = $this->execute();
        switch ($res) {
    case self::TEST_PASS:
    case self::TEST_FAIL:
    case self::TEST_WARN:
      $this->status = $res;
      break;

//    case self::TEST_UNTESTED:
    default:
      throw new Exception(lang('error_test_invalidresult').' '.$res);
    }

        return $this->status;
    }

    public function msg()
    {
        if ($this->msg_key) {
            return lang($this->msg_key);
        }
        if ($this->msg) {
            return $this->msg;
        }

        switch ($this->status) {
    case self::TEST_PASS:
      if ($this->pass_key) {
          return lang($this->pass_key);
      }
      if ($this->pass_msg) {
          return $this->pass_msg;
      }
      break;

    case self::TEST_FAIL:
      if ($this->fail_key) {
          return lang($this->fail_key);
      }
      if ($this->fail_msg) {
          return $this->fail_msg;
      }
      break;

    case self::TEST_WARN:
      if ($this->warn_key) {
          return lang($this->warn_key);
      }
      if ($this->warn_msg) {
          return $this->warn_msg;
      }
      break;

    default:
      throw new Exception(lang('error_test_invalidstatus'));
    }
    }

    protected function returnBytes($val)
    {
        if (is_string($val)) {
            $val = trim($val);
            if ($val === '') {
                return 0.0;
            }
            $last = strtolower(substr($val, -1));
            switch ($last) {
        case 'g':
        case 'm':
        case 'k':
          $val = (float) substr($val, 0, -1);
          break;
        default:
          return (float) $val;
      }
            switch ($last) {
        case 'g':
          $val *= 1024.0;
          //no break here
        case 'm':
          $val *= 1024.0;
          //no break here
        case 'k':
          $val *= 1024.0;
      }
        }

        return $val;
    }
} // class
