<?php

namespace CMSMS\internal;

class SysDataCache
{
    private $_name;
    private $_fetchcb;

    public function __construct($name,callable $fetch_fn)
    {
        $this->_name = trim($name);
        $this->_fetchcb = $fetch_fn;
    }

    public function get_name()
    {
        return $this->_name;
    }

    public function fetch()
    {
        return ($this->_fetchcb)();
    }
} // class
