<?php
/*
Audit management classes
Copyright (C) 2017-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\AppSingle;
use CMSMS\IAuditManager;
use LogicException;
use const CMS_DEPREC;
use const TMP_CACHE_LOCATION;
use function get_userid;
use function get_username;

/**
 * Default message-recorder class.
 * Per config.ini setting, records into PHP's system-logger or a site-specific log-file.
 * @since 2.99
 */
class DefaultAuditLogger implements IAuditManager
{
    private const LOGFILE = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'audit_log';

    public function audit(string $msg, string $subject = '', $itemid = null)
    {
        $userid = get_userid(FALSE);
        if ($userid < 1) $userid = '';
        else $userid = " ($userid)";
        $username = get_username(FALSE);

        $out = "CMSMS MSG: ADMINUSER=$username{$userid}, ITEMID=$itemid, SUBJECT=$subject, MSG=$msg";
        error_log($out, 0, self::LOGFILE);
    }

    public function notice(string $msg, string $subject = '')
    {
        $out = "CMSMS NOTICE: SUBJECT=$subject, $msg";
        error_log($out, 0, self::LOGFILE);
    }

    public function warning(string $msg, string $subject = '')
    {
        $out = "CMSMS WARNING: SUBJECT=$subject, $msg";
        error_log($out, 0, self::LOGFILE);
    }

    public function error(string $msg, string $subject = '')
    {
        $out = "CMSMS ERROR: SUBJECT=$subject, $msg";
        error_log($out, 0, self::LOGFILE);
    }
} // class

/**
 * A singleton class for logging data using a plugged-in data recorder.
 *
 * @final
 * @since 2.99
 * @package CMS
 * @license GPL
 */
final class AuditOperations implements IAuditManager
{
    /* *
     * @ignore
     */
//  private static $_instance = null;

    /**
     * The message-recorder object to use if $_opt_mgr hasn't been set (yet).
     * @ignore
     */
    private $_std_mgr = null;

    /**
     * The message-recorder object specified by a caller.
     * @ignore
     */
    private $_opt_mgr = null;

    /**
     * @ignore
     */
//    protected function __construct() {}
    protected function __clone() {}

//    public static function init() {} // just ensures other stuff in this file is loaded

    /**
     * Get the singleton instance of this class.
     * @deprecated since 2.99 instead use CMSMS\AppSingle::AuditOperations()
     * @return AuditOperations
     */
    public static function get_instance() : self
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\AppSingle::AuditOperations()'));
        return AppSingle::AuditOperations();
    }

    /**
     * @param IAuditManager-compatible $mgr
     * @throws LogicException
     */
    public function set_auditor(IAuditManager $mgr)
    {
        if ($this->_opt_mgr) throw new LogicException('Only one audit-data processor can be set');
        $this->_opt_mgr = $mgr;
    }

    protected function get_auditor()
    {
        if ($this->_opt_mgr) return $this->_opt_mgr;
        if (!$this->_std_mgr) {
            $this->_std_mgr = new DefaultAuditLogger(); // plug in the default
        }
        return $this->_std_mgr;
    }

    public function audit(string $msg, string $subject = '', $itemid = null)
    {
        if (!$itemid) $itemid = '0';
        $this->get_auditor()->audit($msg, $subject, $itemid);
    }

    public function notice(string $msg, string $subject = '')
    {
        $this->get_auditor()->notice($msg, $subject);
    }

    public function warning(string $msg, string $subject = '')
    {
        $this->get_auditor()->warning($msg, $subject);
    }

    public function error(string $msg, string $subject = '')
    {
        $this->get_auditor()->error($msg, $subject);
    }
} // class
