<?php
/*
A collection of compatibility tools for the database connectivity layer.
Copyright (C) 2018-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\Database {

use CMSMS\Database\Connection;
use CMSMS\DeprecationNotice;
use const CMS_DEPREC;

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
         * @return Connection
         *
         * @deprecated
         */
        public static function init()
        {
            assert(empty(CMS_DEPREC), new DeprecationNotice('Upgrade to current database API'));
            return new Connection();
        }

        public static function on_error()
        {
            // do nothing
        }

        /**
         * No-op function that allows the autoloader to load this file.
         */
        public static function noop()
        {
            // do nothing
        }

        /**
         * For parameterized SQL commands which cannot be natively prepared.
         * Interpret '?'-parameterized $sql and corresponding $bindvars
         * into a non-parameterized command, i.e. emulate parameterization.
         *
         * @param object $conn the database-connection object
         * @param string $sql the command
         * @param mixed  $bindvars array of command-parameter value[s], or a single scalar value
         * @return mixed replacment command or null
         *
         * @since 3.0
         */
        public static function interpret(Connection &$conn, $sql, $bindvars)
        {
            if ($bindvars) {
                if (!is_array($bindvars)) {
                    $bindvars = [$bindvars];
                }

                $sqlarr = explode('?', $sql);
                $i = 0;
                $sql = '';
                foreach ($bindvars as $v) {
                    $sql .= $sqlarr[$i];
                    switch (gettype($v)) {
                        case 'string':
                            $sql .= $conn->qstr($v); //or after FILTER_SANITIZE_* filtering ?
                            break;
                        case 'boolean':
                            $sql .= $v ? '1' : '0';
                            break;
                        case 'integer':
                            $sql .= $v;
                            break;
                        case 'double': //a.k.a. float
                            $sql .= strtr($v, ',', '.');
                            break;
                        default:
                            if ($v === null) {
                                $sql .= 'NULL';
                            } else {
                                return null;
                            }
                    }
                    ++$i;
                }
                if (count($sqlarr) != $i+1) {
                    return null;
                }
                $sql .= $sqlarr[$i];
            }
            return $sql;
        }
    } // class

    // ex-DataDictionary methods

    /**
     * @deprecated since 3.0 does nothing
     * @ignore
     * @return array
     */
    function _array_change_key_case($an_array)
    {
        return $an_array;
    }

    /**
     * @deprecated since 3.0 does nothing
     * @ignore
     * @return array
     */
    function Lens_ParseArgs($defn, $endstmtchar = ',', $tokenchars = '_.-')
    {
        return [];
    }
} // namespace

namespace {

    use CMSMS\Database\Connection;
    use CMSMS\Database\DataDictionary;
    use CMSMS\DeprecationNotice;
    use CMSMS\Lone;

    // root namespace stuff
    /*
     * A constant to assist with date and time flags in the data dictionary.
     *
     * @name CMS_ADODB_DT
     */
    const CMS_ADODB_DT = 'DT'; // backwards compatibility.

    /**
     * Create a new data dictionary object.
     * called by module installation routines.
     *
     * @param mixed Connection|null $conn Optional existing database connection object
     *
     * @return DataDictionary
     *
     * @deprecated since 3.0
     */
    function NewDataDictionary(Connection $conn)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('class','CMSMS\Database\DataDictionary'));
        return new DataDictionary($conn);
    }

    /**
     * Create a new database connection.
     *
     * @deprecated since 2.2
     *
     * @param string $dbms
     * @param string $flags
     *
     * @return Connection
     */
    function ADONewConnection($dbms, $flags)
    {
        // now that our connection object is stateless... this is just a wrapper
        // for our global db instance.... but should not be called.
        return Lone::get('Db');
    }

    /**
     * A function formerly used to load the adodb library.
     * Does nothing.
     *
     * @deprecated since 2.2
     */
    function load_adodb()
    {
        // this should only have been called by the core
        // but now does nothing, just in case it is called.
    }

    /**
     * A function formerly used to ensure that we were re-connected to the proper database.
     * Does nothing.
     *
     * @deprecated since 2.2
     */
    function adodb_connect()
    {
        // this may be called by UDT's etc. that are talking to other databases
        // or using manual mysql methods.
    }

    /**
     * A function formerly used for handling a database error.
     * Does nothing.
     *
     * @param string $dbtype
     * @param string $function_performed
     * @param int    $error_number
     * @param string $error_message
     * @param string $host
     * @param string $database
     * @param mixed  $connection_obj
     *
     * @deprecated since 2.2
     */
    function adodb_error($dbtype, $function_performed, $error_number, $error_message,
                         $host, $database, &$connection_obj)
    {
    }
} //namespace
