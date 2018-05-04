<?php
#Audit mamagement classes and interface
#Copyright (C) 2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
        public function audit( string $subject, string $msg, $item_id = null );
        public function notice( string $msg, string $subject = '' );
        public function warning( string $msg, string $subject = '' );
        public function error( string $msg, string $subject = '' );
    }

    class HttpErrorLogAuditor implements IAuditManager
    {
        public function audit( string $subject, string $msg, $itemid = null )
        {
            $userid = get_userid(FALSE);
            $username = get_username(FALSE);
            $ip_addr = null;
            if( $userid < 1 ) $userid = null;

            $out = "CMSMS MSG: ADMINUSER=$username ($userid), ITEMID=$itemid: SUBJECT=$subject, MSG=$msg";
            $this->notice( $out );
        }

        public function notice( string $msg, string $subject = '' )
        {
            $msg = "CMSMS NOTICE: SUBJECT=$subject, $msg";
            @error_log( $msg, 0, TMP_CACHE_LOCATION.'/audit_log' );
        }

        public function warning( $msg, string $subject = '' )
        {
            $msg = "CMSMS WARNING: SUBJECT=$subject, $msg";
            @error_log( $msg, 0, TMP_CACHE_LOCATION.'/audit_log' );
        }

        public function error( string $msg, string $subject = '' )
        {
            $msg = "CMSMS ERROR: SUBJECT=$subject, $msg";
            @error_log( $msg, 0, TMP_CACHE_LOCATION.'/audit_log' );
        }
    }

    final class AuditManager
    {
        private static $_instance;
        private static $_std_mgr;
        private static $_opt_mgr;

        protected function __construct() {}

        public static function init() {} // does nothing... just so we can audoload the thing.

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

        public static function audit( string $item, string $msg, $item_id )
        {
            if( !empty($item_id) ) $item_id = (int) $item_id;
            self::get_auditor()->audit( $item, $msg, $item_id );
        }

        public static function notice( string $msg, string $subject = null )
        {
            self::get_auditor()->notice( $msg, $subject );
        }

        public static function warning( string $msg, string $subject = null )
        {
            self::get_auditor()->warning( $msg, $subject );
        }

        public static function error( $msg, $subject = null )
        {
            self::get_auditor()->error( $msg, $subject );
        }

    } // class
} // namespace


namespace  {
    function audit( $item_id, string $item, string $action ) {
        \CMSMS\AuditManager::audit( $item, $action, $item_id );
    }

    function cms_notice( string $msg, string $subject = '' ) {
        \CMSMS\AuditManager::notice( $msg, $subject );
    }

    function cms_warning( string $msg, string $subject = '' ) {
        \CMSMS\AuditManager::warning( $msg, $subject );
    }

    function cms_error( string $msg, string $subject = '' ) {
        \CMSMS\AuditManager::error( $msg, $subject );
    }
} // namespace
