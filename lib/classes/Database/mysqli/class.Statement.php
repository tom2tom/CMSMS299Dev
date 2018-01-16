<?php
/*
-------------------------------------------------------------------------
Module: \CMSMS\Database\mysqli\Statement (C) 2017 Robert Campbell
         <calguy1000@cmsmadesimple.org>
A class to represent a prepared SQL statement
-------------------------------------------------------------------------
CMS Made Simple (C) 2004-2017 Ted Kulp <wishy@cmsmadesimple.org>
Visit our homepage at: http:www.cmsmadesimple.org
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
Or read it online: http:www.gnu.org/licenses/licenses.html#GPL
END_LICENSE
-------------------------------------------------------------------------
*/

namespace CMSMS\Database\mysqli;

/**
 * A class defining a prepared database statement.
 *
 * @author Robert Campbell
 * @copyright Copyright (C) 2017, Robert Campbell <calguy1000@cmsmadesimple.org>
 *
 * @since 2.2
 *
 * @property-read Connection $db The database connection
 * @property-read string $sql The SQL query
 */
class Statement
{
    /**
     * @ignore
     */
    protected $_conn; // Connection object
    /**
     * @ignore
     */
    protected $_stmt; // mysqli_stmt object
    /**
     * @ignore
     */
    protected $_sql;
    /**
     * @ignore
     */
    protected $_prep = false; // whether prepare() succeeded
    protected $_bound = false; // whether bind() succeeded
    /**
     * @ignore
     */
    protected $_native = ''; //for PHP 5.4+, the MySQL native driver is a php.net compile-time default

    /**
     * Constructor.
     *
     * @param Connection      $conn The database connection
     * @param optional string $sql  The SQL query, default null
     */
    public function __construct(Connection &$conn, $sql = null)
    {
        $this->_conn = $conn;
        $this->_sql = $sql;
    }

/* BAD !!
    public function __destruct()
    {
        if ($this->_stmt) {
            if ($this->_bound) {
                $this->_stmt->free_result();
            }
            if ($this->_prep) {
                $this->_stmt->close();
            }
        }
    }
*/
    /**
     * @ignore
     */
    public function __get($key)
    {
        switch ($key) {
         case 'db':
         case 'conn':
            return $this->_conn;
         case 'sql':
            return $this->_sql;
        }
    }

    /**
     * @internal
     */
    protected function isNative()
    {
        if ($this->_native === '') {
            $this->_native = function_exists('mysqli_fetch_all');
        }

        return $this->_native;
    }

    protected function processerror ($type, $errno, $error)
    {
        $this->_conn->OnError($type, $errno, $error);
    }

    /**
     * Prepare the query.
     *
     * @param optional string $sql parameterized SQL command default null
     *
     * @return bool indicating success
     */
    public function prepare($sql = null)
    {
        $mysql = $this->_conn->get_inner_mysql();
        if (!$mysql || !$this->_conn->isConnected()) {
            $errno = 5;
            $error = 'Attempt to create prepared statement when database is not connected';
            $this->processerror(\CMSMS\Database\Connection::ERROR_CONNECT, $errno, $error);
            $this->_prep = false;

            return false;
        } elseif (!($sql || $this->_sql)) {
            $errno = 1;
            $error = 'No SQL to prepare';
            $this->processerror(\CMSMS\Database\Connection::ERROR_PARAM, $errno, $error);
            $this->_prep = false;

            return false;
        }

        if (!$sql) {
            $sql = $this->_sql;
        } else {
            $this->_sql = $sql;
        }
        $this->_stmt = $mysql->stmt_init();
        $this->_prep = $this->_stmt->prepare((string) $sql);
        if ($this->_prep) {
            $this->_conn->errno = 0;
            $this->_conn->error = '';

            return true;
        }

        $errno = $this->_stmt->errno;
        $error = $this->_stmt->error;
        $this->processerror (\CMSMS\Database\Connection::ERROR_PREPARE, $errno, $error);
        $this->_stmt = null;

        return false;
    }

    /**
     * Bind parameters in $valsarr to the sql statement.
     *
     * @return bool indicating success
     */
    public function bind($valsarr)
    {
        if (!$this->_stmt) {
            if ($this->_sql) {
                $this->prepare($this->_sql);
                if (!$this->_prep) {
                    $this->_bound = false;

                    return false;
                }
            } else {
                $errno = 1;
                $error = 'No SQL to bind to';
                $this->processerror(\CMSMS\Database\Connection::ERROR_PARAM, $errno, $error);
                $this->_bound = false;

                return false;
            }
        }

        if (is_array($valsarr) && count($valsarr) == 1 && is_array($valsarr[0])) {
            $valsarr = $valsarr[0];
        }
        $types = '';
        $bound = [''];
        foreach ($valsarr as $k => &$val) {
            switch (gettype($val)) {
             case 'double': //i.e. float
//          $val = strtr($val, ',', '.');
                $types .= 'd';
                break;
             case 'boolean':
                $valsarr[$k] = $val ? 1 : 0;
             case 'integer':
                $types .= 'i';
                break;
//             case 'string':
//TODO handle blobs for data > max_allowed_packet, send them using ::send_long_data()
// to get the max_allowed_packet
//$mysql = $this->_conn->get_inner_mysql();
//$maxp = $mysql->query('SELECT @@global.max_allowed_packet')->fetch_array();
//             case 'array':
//             case 'object':
//             case 'resource':
//                $val = serialize($val);
//                $types .= 's';
//                break;
//             case 'NULL':
//             case 'unknown type':
             default:
                $types .= 's';
                break;
            }
            $bound[] = &$valsarr[$k];
        }
        unset($val);
        $bound[0] = $types;

        if ($this->_bound) {
            $this->_stmt->free_result();
        }

        if (call_user_func_array([$this->_stmt, 'bind_param'], $bound)) {
            $this->_conn->errno = 0;
            $this->_conn->error = '';
            $this->_bound = true;

            return true;
        }

        $errno = 6;
        $error = 'Failed to bind paramers to prepared statement';
        $this->processerror(\CMSMS\Database\Connection::ERROR_PARAM, $errno, $error);
        $this->_bound = false;

        return false;
    }

    /**
     * Execute the query, using supplied $valsarr (if any) as bound values.
     *
     * @return object: ResultSet or EmptyResultSet or PrepResultSet
     */
    public function execute($valsarr)
    {
        if (!$this->_stmt) {
            if ($this->_sql) {
                $this->prepare($this->_sql);
                if (!$this->_prep) {
                    $this->_bound = false;

                    return null;
                }
            } else {
                $errno = 1;
                $error = 'No SQL to prepare';
                $this->processerror(\CMSMS\Database\Connection::ERROR_PARAM, $errno, $error);

                return null;
            }
        }

        $pc = $this->_stmt->param_count;
        if ($valsarr) {
            if (is_array($valsarr) && count($valsarr) == 1 && is_array($valsarr[0])) {
                $valsarr = $valsarr[0];
            }
            if ($pc == count($valsarr)) {
                $this->bind($valsarr);
                if (!$this->_bound) {
                    return null;
                }
            } else {
                $errno = 2;
                $error = 'Incorrect number of bound parameters - should be '.$pc;
                $this->processerror(\CMSMS\Database\Connection::ERROR_PARAM, $errno, $error);

                return null;
            }
        } elseif ($pc > 0 && !$this->_bound) {
            $errno = 3;
            $error = 'No bound parameters, and no arguments passed';
            $this->processerror(\CMSMS\Database\Connection::ERROR_PARAM, $errno, $error);

            return null;
        }

        if (!$this->_stmt->execute()) {
            $errno = $this->_stmt->errno;
            $error = $this->_stmt->error;
            $this->processerror(\CMSMS\Database\Connection::ERROR_EXECUTE, $errno, $error);

            return null;
        }

        if ($this->_stmt->field_count > 0) {
            if ($this->isNative()) {
                $rs = $this->_stmt->get_result(); //mysqli_result or false
                if ($rs) {
                    $this->_conn->errno = 0;
                    $this->_conn->error = '';

                    return new ResultSet($rs);
                } elseif (($n = $this->_stmt->errno) > 0) {
                    $error = $this->_stmt->error;
                    $this->processerror(\CMSMS\Database\Connection::ERROR_EXECUTE, $n, $error);

                    return null;
                } else { //should never happen
                    $errno = 99;
                    $error = 'No result (reason unknown)';
                    $this->processerror(\CMSMS\Database\Connection::ERROR_EXECUTE, $errno, $error);

                    return null;
                }
            } else {
                $this->_conn->errno = 0;
                $this->_conn->error = '';

                return new PrepResultSet($this->_stmt);
            }
        } else { //INSERT,UPDATE,DELETE etc
            $this->_conn->errno = 0;
            $this->_conn->error = '';

            return true;
        }
    }
}
