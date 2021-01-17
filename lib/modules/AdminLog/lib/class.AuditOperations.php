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
namespace AdminLog;

use CMSMS\IAuditManager;
use LogicException;
use const TMP_CACHE_LOCATION;
use function get_userid;
use function get_username;

/**
 * Default message-recorder class. Records messages in a log-file.
 */
class HttpErrorLogAuditor implements IAuditManager
{
	private const LOGFILE = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'audit_log';

    public function audit(string $msg, string $subject = '', $itemid = null)
    {
        $userid = get_userid(FALSE);
        if ($userid < 1 ) $userid = '';
        else $userid = " ($userid)";
        $username = get_username(FALSE);

        $out = "CMSMS MSG: ADMINUSER=$username{$userid}, ITEMID=$itemid, SUBJECT=$subject, MSG=$msg";
        @error_log($out, 0, self::LOGFILE);
    }

    public function notice(string $msg, string $subject = '')
    {
        $msg = "CMSMS NOTICE: SUBJECT=$subject, $msg";
        @error_log($msg, 0, self::LOGFILE);
    }

    public function warning(string $msg, string $subject = '')
    {
        $msg = "CMSMS WARNING: SUBJECT=$subject, $msg";
        @error_log($msg, 0, self::LOGFILE);
    }

    public function error(string $msg, string $subject = '')
    {
        $msg = "CMSMS ERROR: SUBJECT=$subject, $msg";
        @error_log($msg, 0, self::LOGFILE);
    }
} // class

final class AuditOperations
{
    // static properties here >> StaticProperties class ?
    private static $_std_mgr = null;
    private static $_opt_mgr = null;

    protected function __construct() {}
    protected function __clone() {}

//    public static function init() {} //just ensures other stuff in this file esp. global namespace methods, is loaded

    /**
     * @param IAuditManager-compatible $mgr
     * @throws LogicException
     */
    public static function set_auditor(IAuditManager $mgr)
    {
        if (self::$_opt_mgr) throw new LogicException('Sorry only one audit manager can be set');
        self::$_opt_mgr = $mgr;
    }

    protected static function get_auditor()
    {
        if (self::$_opt_mgr) return self::$_opt_mgr;
        if (!self::$_std_mgr) self::$_std_mgr = new HttpErrorLogAuditor();
        return self::$_std_mgr;
    }

    // the following are static equivalents of IAuditManager methods

    public static function audit(string $msg, string $subject, $itemid = null)
    {
        if (!$itemid) $itemid = '0';
        self::get_auditor()->audit($msg, $subject, $itemid);
    }

    public static function notice(string $msg, string $subject = '')
    {
        self::get_auditor()->notice($msg, $subject);
    }

    public static function warning(string $msg, string $subject = '')
    {
        self::get_auditor()->warning($msg, $subject );
    }

    public static function error(string $msg, string $subject = '')
    {
        self::get_auditor()->error($msg, $subject);
    }
} // class
