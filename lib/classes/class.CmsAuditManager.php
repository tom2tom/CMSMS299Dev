<?php
#CMS Made Simple class
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS {

    interface IAuditManager
    {
        public function audit( $subject, $msg, $item_id = null );
        public function notice( $msg, $subject = null );
        public function warning( $msg, $subject = null );
        public function error( $msg, $subject = null );
    }

    class HttpErrorLogAuditor implements IAuditManager
    {
        public function audit( $subject, $msg, $itemid = null )
        {
            $userid = get_userid(FALSE);
            $username = get_username(FALSE);
            $ip_addr = null;
            if( $userid < 1 ) $userid = null;

            $out = "CMSMS MSG: ADMINUSER=$username ($userid), ITEMID=$itemid: SUBJECT=$subject, MSG=$msg";
            $this->notice( $out );
        }

        public function notice( $msg, $subject = null )
        {
            $msg = "CMSMS NOTICE: SUBJECT=$subject, $msg";
            @error_log( $msg, 0, TMP_CACHE_LOCATION.'/audit_log' );
        }

        public function warning( $msg, $subject = null )
        {
            $msg = "CMSMS WARNING: SUBJECT=$subject, $msg";
            @error_log( $msg, 0, TMP_CACHE_LOCATION.'/audit_log' );
        }

        public function error( $msg, $subject = null )
        {
            $msg = "CMSMS ERROR: SUBJECT=$subject, $msg";
            @error_log( $msg, 0, TMP_CACHE_LOCATION.'/audit_log' );
        }
    }

    final class CmsAuditManager
    {
        private static $_instance;
        private static $_std_mgr;
        private static $_opt_mgr;

        protected function __construct() {}

        public static function init() {} // does nothing... just so we can autoload the thing.

        public static function set_auditor( IAuditManager $mgr )
        {
            if( self::$_opt_mgr  ) throw new \LogicException('Sorry only one audit manager can be set');
            self::$_opt_mgr = $mgr;
        }

        protected static function get_auditor()
        {
            if( self::$_opt_mgr ) return self::$_opt_mgr;
            if( !self::$_std_mgr ) self::$_std_mgr = new HttpErrorLogAuditor();
            return self::$_std_mgr;
        }

        public static function audit( $item, $msg, $item_id )
        {
            self::get_auditor()->audit( $item, $msg, $item_id );
        }

        public static function notice( $msg )
        {
            self::get_auditor()->notice( $msg );
        }

        public static function warning( $msg )
        {
            self::get_auditor()->warning( $msg );
        }

        public static function error( $msg )
        {
            self::get_auditor()->error( $msg );
        }

    } // end of class
} // namespace


namespace  {
    function audit( $item_id, $item, $action )
    {
        CMSMS\CmsAuditManager::audit( $item, $action, $item_id );
    }

    function cms_notice( $msg, $subject = null )
    {
        CMSMS\CmsAuditManager::notice( $msg, $subject );
    }

    function cms_warning( $msg, $subject = null )
    {
        CMSMS\CmsAuditManager::warning( $msg, $subject );
    }

    function cms_error( $msg, $subject = null )
    {
        CMSMS\CmsAuditManager::error( $msg, $subject );
    }
} // namespace
