<?php

namespace CMSMS;

/**
 * SysDataCache values-set populator
 * @since 2.99
 * @since 2.0  as CMSMS\internal\global_cachable
 */
class SysDataCacheDriver
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
