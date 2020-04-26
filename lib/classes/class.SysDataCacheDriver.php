<?php

namespace CMSMS;

/**
 * SysDataCache values-set populator
 * @since 2.0  as CMSMS\internal\global_cachable
 * @since 2.9
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

\class_alias(SysDataCacheDriver::class, 'CMSMS\internal\global_cachable', false);
