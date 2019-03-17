<?php

namespace cms_installer;

abstract class nls
{
    protected $_isocode;
    protected $_locale;
    protected $_fullname;
    protected $_encoding;
    protected $_aliases;
    protected $_display;

    abstract public function __construct();

    public function matches($str)
    {
        if ($str == $this->name()) {
            return true;
        }
        if ($str == $this->locale()) {
            return true;
        }
        if ($str == $this->isocode()) {
            return true;
        }
        if ($str == $this->fullname()) {
            return true;
        }
        $aliases = $this->aliases();
        if (!is_array($aliases)) {
            $aliases = explode(',', $aliases);
        }
        if (is_array($aliases) && ($n = count($aliases))) {
            for ($i = 0; $i < $n; $i++) {
                if ($aliases[$i] == $str) {
                    return true;
                }
            }
        }
        return false;
    }

    public function name()
    {
        $name = get_class();
        if (endswith($name, '_nls')) {
            $name = substr($name, 0, strlen($name)-4);
        }
        return $name;
    }

    public function isocode()
    {
        if (!$this->_isocode) {
            return substr($this->name, 0, 2);
        }
        return $this->_isocode;
    }

    public function display()
    {
        if (!$this->_display) {
            return $this->fullname();
        }
        return $this->_display;
    }

    public function locale()
    {
        if (!$this->_locale) {
            return $this->name();
        }
        return $this->_locale;
    }

    public function encoding()
    {
        if (!$this->_encoding) {
            return 'UTF-8';
        }
        return $this->_encoding;
    }

    public function fullname()
    {
        if (!$this->_fullname) {
            return $this->name();
        }
        return $this->_fullname;
    }

    public function aliases()
    {
        if ($this->_aliases) {
            if (is_array($this->_aliases)) {
                return $this->_aliases;
            }
            return explode(',', $this->_aliases);
        }
    }
} // class
