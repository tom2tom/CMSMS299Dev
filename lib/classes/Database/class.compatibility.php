<?php
/*
-------------------------------------------------------------------------
Module: CMSMS\Database\compatibility (C) 2017 Robert Campbell
        <calguy1000@cmsmadesimple.org>
A collection of compatibility tools for the database connectivity layer.
-------------------------------------------------------------------------
CMS Made Simple (C) 2004-2017 Ted Kulp <wishy@cmsmadesimple.org>
Visit our homepage at: http://www.cmsmadesimple.org
-------------------------------------------------------------------------
BEGIN_LICENSE
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

However, as a special exception to the GPL, this software is distributed
as an addon module to CMS Made Simple.  You may not use this software
in any Non GPL version of CMS Made simple, or in any version of CMS
Made simple that does not indicate clearly and obviously in its admin
section that the site was built with CMS Made simple.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
END_LICENSE
-------------------------------------------------------------------------
*/

namespace CMSMS\Database {

    /**
     * A class for providing some compatibility functionality with older module code.
     *
     * @todo: Move this class to a different function and rename.
     */
    final class compatibility
    {
        /**
         * Initialize the database connection according to config settings.
         *
         * @return \CMSMS\Database\mysqli\Connection
         *
         * @deprecated
         */
        public static function init()
        {
            return new mysqli\Connection();
        }

        public static function on_error()
        {
            // do nothing
        }

        /**
         * A static no-op function  that allows the autoloader to load this file.
         */
        public static function noop()
        {
            // do nothing
        }
    }
} // end of namespace

namespace {
    // root namespace stuff

    /*
     * A constant to assist with date and time flags in the data dictionary.
     *
     * @name CMS_ADODB_DT
     */
    define('CMS_ADODB_DT', 'DT'); // backwards compatibility.

    /**
     * A method to create a new data dictionary object.
     *
     * @param \CMSMS\Database\Connection $conn The existing database connection
     *
     * @return \CMSMS\Database\DataDictionary
     *
     * @deprecated
     */
    function NewDataDictionary(\CMSMS\Database\Connection $conn)
    {
        // called by module installation routines.
        return $conn->NewDataDictionary();
    }

    /**
     * A function co create a new adodb database connection.
     *
     * @param string $dbms
     * @param string $flags
     *
     * @return \CMSMS\Database\Connection
     *
     * @deprecated
     */
    function ADONewConnection($dbms, $flags)
    {
        // now that our connection object is stateless... this is just a wrapper
        // for our global db instance.... but should not be called.
        return \CmsApp::get_instance()->GetDb();
    }

    /**
     * A function formerly used to load the adodb library.
     * This method currently has no functionality.
     *
     * @deprecated
     */
    function load_adodb()
    {
        // this should only have been called by the core
        // but now does nothing, just in case it is called.
    }

    /**
     * An old method formerly used to ensure that we were re-connected to the proper database.
     * This method currently has no functionality.
     *
     * @deprecated
     */
    function adodb_connect()
    {
        // this may be called by UDT's etc. that are talking to other databases
        // or using manual mysql methods.
    }

    /**
     * An old function for handling a database error.
     *
     * @param string $dbtype
     * @param string $function_performed
     * @param int    $error_number
     * @param string $error_message
     * @param string $host
     * @param string $database
     * @param mixed  $connection_obj
     *
     * @deprecated
     */
    function adodb_error($dbtype, $function_performed, $error_number, $error_message,
                         $host, $database, &$connection_obj)
    {
        // does nothing.... remove me later.
    }
}
