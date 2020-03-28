<?php
#Audit management classes and interface
#Copyright (C) 2017-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

    use LogicException;

    interface AuditManager
    {
        public function audit( string $msg, string $subject, $item_id = null );
        public function notice( string $msg, string $subject = '' );
        public function warning( string $msg, string $subject = '' );
        public function error( string $msg, string $subject = '' );
    }

    class HttpErrorLogAuditor implements AuditManager
    {
        public function audit( string $msg, string $subject, $itemid = null )
        {
            $userid = get_userid(FALSE);
            if( $userid < 1 ) $userid = '';
            else $userid = " ($userid)";
            $username = get_username(FALSE);

            $out = "CMSMS MSG: ADMINUSER=$username{$userid}, ITEMID=$itemid: SUBJECT=$subject, MSG=$msg";
            $this->notice( $out );
        }

        public function notice( string $msg, string $subject = '' )
        {
            $msg = "CMSMS NOTICE: SUBJECT=$subject, $msg";
            @error_log( $msg, 0, TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'audit_log' );
        }

        public function warning( string $msg, string $subject = '' )
        {
            $msg = "CMSMS WARNING: SUBJECT=$subject, $msg";
            @error_log( $msg, 0, TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'audit_log' );
        }

        public function error( string $msg, string $subject = '' )
        {
            $msg = "CMSMS ERROR: SUBJECT=$subject, $msg";
            @error_log( $msg, 0, TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'audit_log' );
        }
    }

    final class AuditOperations
    {
        // static properties here >> StaticProperties class ?
        private static $_std_mgr = null;
        private static $_opt_mgr = null;

        protected function __construct() {}
        protected function __clone() {}

        public static function init() {} // just ensures other stuff in this file is loaded

        /**
         * @param AuditManager $mgr
         * @throws LogicException
         */
        public static function set_auditor( AuditManager $mgr )
        {
            if( self::$_opt_mgr  ) throw new LogicException('Sorry only one audit manager can be set');
            self::$_opt_mgr = $mgr;
        }

        protected static function get_auditor()
        {
            if( self::$_opt_mgr ) return self::$_opt_mgr;
            if( !self::$_std_mgr ) self::$_std_mgr = new HttpErrorLogAuditor();
            return self::$_std_mgr;
        }

        public static function audit( string $msg, string $subject, $itemid )
        {
            if( !$itemid ) $itemid = '0';
            self::get_auditor()->audit( $msg, $subject, $itemid );
        }

        public static function notice( string $msg, string $subject = '' )
        {
            self::get_auditor()->notice( $msg, $subject );
        }

        public static function warning( string $msg, string $subject = '' )
        {
            self::get_auditor()->warning( $msg, $subject );
        }

        public static function error( string $msg, string $subject = '' )
        {
            self::get_auditor()->error( $msg, $subject );
        }

    } // class
} // namespace


namespace {

    use CMSMS\AuditOperations;

    function audit( $itemid, string $subject, string $msg = '' )
    {
        AuditOperations::audit( $msg, $subject, $itemid );
    }

    function cms_notice( string $msg, string $subject = '' )
    {
        AuditOperations::notice( $msg, $subject );
    }

    function cms_warning( string $msg, string $subject = '' )
    {
        AuditOperations::warning( $msg, $subject );
    }

    function cms_error( string $msg, string $subject = '' )
    {
        AuditOperations::error( $msg, $subject );
    }
} // namespace
