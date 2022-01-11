<?php
namespace cms_installer;

abstract class nls
{
    protected $_aliases;
    protected $_direction = 'ltr';
    protected $_display;
    protected $_encoding;
    protected $_fullname;
    protected $_isocode;
    protected $_locale;

    abstract public function __construct();

    // since 2.99 this does case-insensitive checks
    public function matches($str)
    {
        if (strcasecmp($str, $this->name()) == 0) {
            return true;
        }
        if (strcasecmp($str, $this->locale()) == 0) {
            return true;
        }
        if (strcasecmp($str, $this->isocode()) == 0) {
            return true;
        }
        if (strcasecmp($str, $this->direction()) == 0) {
            return true;
        }
        if (strcasecmp($str, $this->fullname()) == 0) {
            return true;
        }
        $aliases = $this->aliases();
        for ($i = 0, $n = count($aliases); $i < $n; ++$i) {
            if (strcasecmp($str, $aliases[$i]) == 0) {
                return true;
            }
        }
        return false;
    }

    public function name()
    {
        $name = get_called_class();
        if (endswith($name, '_nls')) {
            $name = substr($name, 0, -4);
        }
        return $name;
    }

    public function isocode()
    {
        if (empty($this->_isocode)) {
            return substr($this->name(), 0, 2);
        }
        return $this->_isocode;
    }

    public function display()
    {
        if (empty($this->_display)) {
            return $this->fullname();
        }
        return $this->_display;
    }

    public function locale()
    {
        if (empty($this->_locale)) {
            return $this->name();
        }
        return $this->_locale;
    }

    public function encoding()
    {
        if (empty($this->_encoding)) {
            return 'UTF-8';
        }
        return strtoupper($this->_encoding);
    }

    // since 2.99
    public function direction()
    {
        if (empty($this->_direction)) {
            return 'ltr';
        }
        return strtolower($this->_direction);
    }

    public function fullname()
    {
        if (empty($this->_fullname)) {
            return $this->name();
        }
        return $this->_fullname;
    }

    public function aliases()
    {
        if (empty($this->_aliases)) {
            return [];
        }
        if (is_array($this->_aliases)) {
            return $this->_aliases;
        }
        $a = explode(',', $this->_aliases);
        return array_map('trim', $a);
    }
} // class
