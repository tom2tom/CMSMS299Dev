<?php
/*
Admin log management class
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\DeprecationNotice;
use CMSMS\ILogManager;
use CMSMS\Log\dbstorage;
use CMSMS\Log\logfilter;
use CMSMS\Log\logger;
use const CMS_DEPREC;

/**
 * A singleton class for logging data using a plugged-in data recorder.
 * Supports using a custom recorder in place of the default.
 *
 * @final
 * @since 2.99
 * @package CMS
 * @license GPL
 */
final class LogOperations implements ILogManager
{
    /**
     * The message-recorder object in use.
     * @ignore
     */
    private $_data_mgr = null;

    /**
     * @ignore
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * @ignore
     */
    private function __clone() {}

    /**
     * Get the singleton instance of this class.
     * @return LogOperations
     */
    public static function get_instance() : self
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\SingleItem::LogOperations()'));
        return SingleItem::LogOperations();
    }

    /**
     * Setup the default data-recorder backend
     */
    public function init()
    {
        $store = new dbstorage();
        $this->_data_mgr = new logger($store);
    }

    /**
     * Replace the backend data-recorder
     * @param ILogManager-compatible $mgr
     */
    public function set_recorder(ILogManager $mgr)
    {
        $this->_data_mgr = $mgr;
    }

    private function get_recorder()
    {
        if (!$this->_data_mgr) {
            $this->init();
        }
        return $this->_data_mgr;
    }

    // interface methods

    public function info(string $msg, string $subject = '', $itemid = null)
    {
        if (!$itemid) { $itemid = '0'; }
        $this->get_recorder()->info($msg, $subject, $itemid);
    }

    public function notice(string $msg, string $subject = '')
    {
        $this->get_recorder()->notice($msg, $subject);
    }

    public function warning(string $msg, string $subject = '')
    {
        $this->get_recorder()->warning($msg, $subject);
    }

    public function error(string $msg, string $subject = '')
    {
        $this->get_recorder()->error($msg, $subject);
    }

    public function clear()
    {
        if ($this->_data_mgr) { $this->_data_mgr->clear(); }
    }

    public function clear_older_than(int $time)
    {
        if ($this->_data_mgr) { $this->_data_mgr->clear_older_than($time); }
    }

    public function query(logfilter $filter)
    {
        if ($this->_data_mgr) {
            return $this->_data_mgr->query($filter);
        }
    }
}
