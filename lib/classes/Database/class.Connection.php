<?php
/*
Class Connection: interaction with a MySQL database
Copyright (C) 2017-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace CMSMS\Database;

use CmsApp;
use DateTime;
use const CMS_DEBUG;
use function debug_bt_to_log;
use function debug_display;
use function debug_to_log;

/**
 * A class defining a database connection, and mechanisms for working with a database.
 *
 * This library is largely compatible with adodb_lite with the pear, extended, transaction plugins
 * and with a few notable differences:
 *
 * Differences:
 * <ul>
 *  <li>genId will not automatically create a sequence table.
 *    <p>Consider using auto-increment fields instead of sequence tables where there's no possibility of a race.</p>
 *  </li>
 * </ul>
 *
 * @author Robert Campbell
 *
 * @since 2.2
 */
abstract class Connection
{
    /**
     * Defines an error with connecting to the database.
     */
    const ERROR_CONNECT = 'CONNECT';

    /**
     * Defines an error related to statement execution.
     */
    const ERROR_EXECUTE = 'EXECUTE';

    /**
     * Defines an error with a transaction.
     */
    const ERROR_TRANSACTION = 'TRANSACTION';

    /**
     * Defines an error related to statement preparation.
     */
    const ERROR_PREPARE = 'PREPARE';

    /**
     * Defines an error in a datadictionary command.
     */
    const ERROR_DATADICT = 'DATADICTIONARY';

    /**
     * Defines a parameter-error in a method call.
     */
    const ERROR_PARAM = 'PARAMETER';

    /**
     * @param int $errno The error number from the last operation
     */
    public $errno = 0;

    /**
     * @param string $error The error descriptor from the last operation
     */
    public $error = '';

    /**
     * @ignore
     * callable Error-processing method
     */
    protected $_errorhandler = null;

    /**
     * @ignore
     * bool Whether debug mode is enabled
     */
    protected $_debug = false;

    /**
     * @ignore
     */
    private $_debug_cb = null;

    /**
     * @ignore
     */
    protected $_query_count = 0;

    /**
     * Accumulated sql query time.
     *
     * @internal
     *
     * @param float $query_time_total
     */
    protected $_query_time_total = 0;

    /**
     * @ignore
     */
//    private $_queries = [];

    /**
     * The last SQL command executed.
     *
     * @internal
     *
     * @param string $sql
     */
    protected $_sql;

    /**
     * @internal
     *
     * @param string $type  The database connection type
     */
    protected $_type;

    /**
     * Construct a new Connection.
     * @param array $config unused here, for subclass only
     */
    public function __construct($config = null)
    {
        $this->_debug = CMS_DEBUG;
        if ($this->_debug) {
            $this->_debug_cb = 'debug_buffer';
        }

        global $CMS_INSTALL_PAGE;
        if (!isset($CMS_INSTALL_PAGE)) {
            $this->_errorhandler = [$this, 'on_error'];
        }
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch ($key) {
         case 'query_time_total':
            return $this->_query_time_total;
         case 'query_count':
            return $this->_query_count;
         default:
            return null;
        }
    }

    /**
     * @ignore
     */
    public function __isset($key)
    {
        switch ($key) {
         case 'query_time_total':
         case 'query_count':
            return true;
         default:
           return false;
        }
    }

    /**
     * Return the database type.
     *
     * @return string
     */
    public function dbType()
    {
        return $this->_type;
    }

    /**
     * Open the database connection.
     *
     * @deprecated
     * @return bool Success or failure
     */
    final public function connect()
    {
        return true;
    }

    /**
     * An alias for close.
     */
    final public function Disconnect()
    {
        return $this->close();
    }

    /**
     * Test if the connection object is connected to the database.
     *
     * @return bool
     */
    abstract public function isConnected();

    /**
     * Close the database connection.
     */
    abstract public function close();

    //// utilities

    /**
     * An alias for the qStr method.
     *
     * @deprecated
     *
     * @param string $str
     *
     * @return string
     */
    public function QMagic($str)
    {
        return $this->qStr($str);
    }

    /**
     * Quote a string in a database agnostic manner.
     * Warning: This method may require two way traffic with the database depending upon the database.
     *
     * @param string $str
     *
     * @return string
     */
    abstract public function qStr($str);

    /**
     * qStr without surrounding quotes.
     *
     * @param string $str
     *
     * @return string
     */
    abstract public function addQ($str);

    /**
     * output the mysql expression for a string concatenation.
     * This function accepts a variable number of string arguments.
     *
     * @param $str   First string to concatenate
     * @param $str,. Any number of strings to concatenate
     *
     * @return string
     */
    abstract public function concat();

    /**
     * Output the mysql expression to test if an item is null.
     *
     * @param string $field  The field to test
     * @param string $ifNull The value to use if $field is null
     *
     * @return string
     */
    abstract public function ifNull($field, $ifNull);

    /**
     * Output the number of rows affected by the last query.
     *
     * @return int
     */
    abstract public function affected_rows();

    /**
     * Return the numeric ID of the last insert query into a table with an auto-increment field.
     *
     * @return int
     */
    abstract public function insert_id();

    //// primary query functions

    /**
     * The primary function for communicating with the database.
     *
     * @internal
     *
     * @param string $sql The SQL query
     */
    abstract protected function do_sql($sql);

    /**
     * Prepare (compile) @sql for parameterized and/or repeated execution.
     *
     * @param string $sql The SQL query
     *
     * @return a Statement object if @sql is valid, or false
     */
    abstract public function prepare($sql);

    /**
     * Parse and execute an SQL prepared statement or query.
     *
     * @param string or Statement object $sql
     * @param optional array             $valsarr Value-parameters to fill placeholders (if any) in @sql
     *  when a SELECT retrieves nothing or other command fails, default false
     *
     * @return <namespace>ResultSet or a subclass of that
     */
    abstract public function execute($sql, $valsarr = null);

    /**
     * As for execute, but non-blocking. Works as such only if the native driver
     * is present. Otherwise reverts to normal execution, and caches the result.
     */
    abstract public function async_execute($sql, $valsarr = null);

    /**
     * Get result from async SQL query. If the native driver is not present, this
     * just returns the cached result of the prior not-really-async command.
     */
    abstract public function reap();

    /**
     * Execute an SQL command, to retrieve (at most) @nrows records.
     *
     * @param string           $sql     The SQL to execute
     * @param optional int     $nrows   The number of rows to return, default all (0)
     * @param optional int     $offset  0-based starting-offset of rows to return, default 0
     * @param optional array   $valsarr Value-parameters to fill placeholders (if any) in @sql
     *  when a SELECT retrieves nothing or other command fails, default false
     *
     * @return mixed <namespace>ResultSet or a subclass
     */
    public function selectLimit($sql, $nrows = 0, $offset = 0, $valsarr = null)
    {
        if ($nrows > 0) {
            $xql = ' LIMIT '.$nrows;
        } else {
            $xql = '';
        }
        if ($offset > 0) {
            $xql .= ' OFFSET '.$offset;
        }
        if ($xql) {
            $sql .= $xql;
        }

        return $this->execute($sql, $valsarr);
    }

    /**
     * Execute an SQL statement and return all the results as an array.
     *
     * @param string $sql     The SQL to execute
     * @param array  $valsarr Value-parameters to fill placeholders (if any) in @sql
     * @param optional int   $nrows   The number of rows to return, default all (0)
     * @param optional int   $offset  0-based starting-offset of rows to return, default 0
     *
     * @return array Numeric-keyed matched results, or empty
     */
    public function getArray($sql, $valsarr = null, $nrows = 0, $offset = 0)
    {
        if ($nrows < 1 && $offset < 1) {
            $rs = $this->execute($sql, $valsarr);
        } else {
            $rs = $this->selectLimit($sql, $nrows, $offset, $valsarr);
        }
        if ($rs) {
            return $rs->getArray();
        }

        return [];
    }

    /**
     * An alias for the getArray method.
     *
     * @param string $sql     The SQL statement to execute
     * @param array  $valsarr Value-parameters to fill placeholders (if any) in @sql
     *
     * @return array Numeric-keyed matched results, or empty
     */
    public function getAll($sql, $valsarr = null, $nrows = 0, $offset = 0)
    {
        return $this->getArray($sql, $valsarr, $nrows, $offset);
    }

    /**
     * Execute an SQL statement and return all the results as an array, with
     * the value of the first-requested-column as the key for each row.
     *
     * @param string $sql         The SQL statement to execute
     * @param array  $valsarr     VAlue-parameters to fill placeholders (if any) in @sql
     * @param bool   $force_array Optionally force each element of the output to be an associative array
     * @param bool   $first2cols  Optionally output only the first 2 columns in an associative array.  Does not work with force_array
     * @param optional int   $nrows   The number of rows to return, default all (0)
     * @param optional int   $offset  0-based starting-offset of rows to return, default 0
     *
     * @return associative array of matched results, or empty
     */
    public function getAssoc($sql, $valsarr = null, $force_array = false, $first2cols = false, $nrows = 0, $offset = 0)
    {
        if ($nrows < 1 && $offset < 1) {
            $rs = $this->execute($sql, $valsarr);
        } else {
            $rs = $this->selectLimit($sql, $nrows, $offset, $valsarr);
        }
        if ($rs) {
            return $rs->getAssoc($force_array, $first2cols);
        }

        return [];
    }

    /**
     * Execute an SQL statement that returns one column, and return all of the
     * matches as an array.
     *
     * @param string $sql     The SQL statement to execute
     * @param array  $valsarr Value-parameters to fill placeholders (if any) in @sql
     * @param bool   $trim    Optionally trim the output results
     * @param optional int   $nrows   The number of rows to return, default all (0)
     * @param optional int   $offset  0-based starting-offset of rows to return, default 0
     *
     * @return array of results, one member per row matched, or empty
     */
    public function getCol($sql, $valsarr = null, $trim = false, $nrows = 0, $offset = 0)
    {
        if ($nrows < 1 && $offset < 1) {
            $rs = $this->execute($sql, $valsarr);
        } else {
            $rs = $this->selectLimit($sql, $nrows, $offset, $valsarr);
        }
        if ($rs) {
            return $rs->getCol($trim);
        }

        return [];
    }

    /**
     * Execute an SQL statement that returns one row of results, and return that row
     * as an associative array.
     *
     * @param string $sql     The SQL statement to execute
     * @param array  $valsarr Value-parameters to fill placeholders (if any) in @sql
     * @param optional int   $offset  0-based starting-offset of rows to return, default 0
     *
     * @return associative array representing a single ResultSet row, or empty
     */
    public function getRow($sql, $valsarr = null, $offset = 0)
    {
        if ($offset < 1) {
            if (stripos($sql, 'LIMIT') === false) {
                $sql .= ' LIMIT 1';
            }
            $rs = $this->execute($sql, $valsarr);
        } else {
            $rs = $this->selectLimit($sql, 1, $offset, $valsarr);
        }
        if ($rs) {
            return $rs->fields();
        }

        return [];
    }

    /**
     * Execute an SQL statement and return a single value.
     *
     * @param string $sql     The SQL statement to execute
     * @param array  $valsarr Parameters to fill placeholders (if any) in @sql
     * @param optional int   $offset  0-based starting-offset of rows to return, default 0
     *
     * @return mixed value or null
     */
    public function getOne($sql, $valsarr = null, $offset = 0)
    {
        if ($offset < 1) {
            if (stripos($sql, 'LIMIT') === false) {
                $sql .= ' LIMIT 1';
            }
            $rs = $this->execute($sql, $valsarr);
        } else {
            $rs = $this->selectLimit($sql, 1, $offset, $valsarr);
        }
        if ($rs) {
            return $rs->getOne();
        }

        return null;
    }

    /* *
     * Get the median value of data recorded in a table field.
     *
     * @param string          $table  The name of the table
     * @param string          $column The name of the column in @table
     * @param optional string $where  SQL condition, must include the
     *                                requested column e.g. “WHERE name > 'A'”
     *
     * @return mixed value or null
     */
/*    public function getMedian($table, $column, $where = '')
    {
        if ($where && stripos($sql, 'WHERE') === false) {
            return null;
        }
        $rs = $this->execute("SELECT COUNT(*) AS num FROM $table $where");
        if ($rs) {
            $mid = (int) $rs->fields('num') / 2;
            $rs = $this->selectLimit("SELECT $column FROM $table $where ORDER BY $column", 1, $mid);

            return $rs->fields($column);
        }

        return null;
    }
*/
    //// transactions

    /**
     * Begin a transaction.
     */
    abstract public function beginTrans();

    /**
     * Begin a smart transaction.
     */
    abstract public function startTrans();

    /**
     * Complete a smart transaction.
     * This method will either do a rollback or a commit depending upon if errors
     * have been detected.
     */
    abstract public function completeTrans($autoComplete = true);

    /**
     * Commit a simple transaction.
     *
     * @param bool $ok Indicates whether there is success or not
     */
    abstract public function commitTrans($ok = true);

    /**
     * Roll back a simple transaction.
     */
    abstract public function rollbackTrans();

    /**
     * Mark a transaction as failed.
     */
    abstract public function failTrans();

    /**
     * Test if a transaction has failed.
     *
     * @return bool
     */
    abstract public function hasFailedTrans();

    //// sequence tables

    /**
     * For use with sequence tables, this method will generate a new ID value.
     *
     * This function will not automatically create the sequence table if not specified.
     *
     * @param string $seqname The name of the sequence table
     *
     * @return int
     *
     * @deprecated
     */
    abstract public function genId($seqname);

    /**
     * Create a new sequence table.
     *
     * @param string $seqname the name of the sequence table
     * @param int    $startID
     *
     * @return bool
     *
     * @deprecated
     */
    abstract public function createSequence($seqname, $startID = 0);

    /**
     * Drop a sequence table.
     *
     * @param string $seqname The name of the sequence table
     *
     * @return bool
     */
    abstract public function dropSequence($seqname);

    //// time and date stuff

    /**
     * A utility method to convert a unix timestamp into a database-specific
     * string suitable for use in queries.
     *
     * @param mixed $time number, or string (e.g. from PHP Date()), or DateTime object
     *
     * @return quoted string representing server/local date & time, or 'NULL'
     */
    public function dbTimeStamp($time)
    {
        if (empty($time) && !is_numeric($time)) {
            return 'NULL';
        }

        if (is_numeric($time)) {
            $time = (int)($time + 0);
        } elseif (is_string($time)) {
            if (strcasecmp($time, 'NULL') == 0) {
                return 'NULL';
            }
            $lvl = error_reporting(0);
            $time = strtotime($time);
            error_reporting($lvl);
        } elseif ($time instanceof DateTime) {
            $time = $time->getTimestamp();
        }

        if ($time > 0) {
            return $this->qStr(date('Y-m-d H:i:s', $time));
        }
        return 'NULL';
    }

    /**
     * A convenience method for converting a database specific string representing a date and time
     * into a unix timestamp.
     *
     * @param string $str
     *
     * @return int
     */
    public function unixTimeStamp($str)
    {
        return strtotime($str);
    }

    /**
     * An alias for the unixTimestamp method.
     *
     * @return int
     */
    public function Time()
    {
        return $this->unixTimeStamp();
    }

    /**
     * Convert a date into something that is suitable for writing to a database.
     *
     * @param mixed $date A string date, or an integer timestamp, or a DateTime object
     *
     * @return quoted, locale-formatted string representing server/local date, or 'NULL'
     */
    public function dbDate($date)
    {
        if (empty($date) && !is_numeric($date)) {
            return 'NULL';
        }

        if (is_string($date) && !is_numeric($date)) {
            if (strcasecmp($date, 'NULL') == 0) {
                return 'NULL';
            }
            $lvl = error_reporting(0);
            $date = strtotime($date);
            error_reporting($lvl);
        } elseif ($date instanceof DateTime) {
            $date = $date->getTimestamp();
        }

        if ($date > 0) {
             return $this->qStr(strftime('%x', $date));
        }
        return 'NULL';
    }

    /**
     * Generate a unix timestamp representing the start of the current day.
     *
     * @deprecated
     *
     * @return int
     */
    public function unixDate()
    {
        return strtotime('today midnight');
    }

    /**
     * An alias for the unixDate method.
     *
     * @return int
     */
    public function Date()
    {
        return $this->unixDate();
    }

    //// error and debug message handling

    /**
     * Return a string describing the latest error (if any).
     *
     * @return string
     */
    abstract public function errorMsg();

    /**
     * Return the latest error number (if any).
     *
     * @return int
     */
    abstract public function ErrorNo();

    /**
     * Set an error handler function.
     *
     * @param callable $fn
     */
    public function SetErrorHandler($fn = null)
    {
        if ($fn && is_callable($fn)) {
            $this->_errorhandler = $fn;
        } else {
            $this->_errorhandler = null;
        }
    }

    /**
     * Toggle debug mode.
     *
     * @param bool     $flag          Enable or Disable debug mode
     * @param callable $debug_handler
     */
    public function SetDebugMode($flag = true, $debug_handler = null)
    {
        $this->_debug = (bool) $flag;
        $this->SetDebugCallback($debug_handler);
    }

    /**
     * Set the debug callback.
     *
     * @param callable $debug_handler
     */
    public function SetDebugCallback($debug_handler = null)
    {
        if ($debug_handler && is_callable($debug_handler)) {
            $this->_debug_cb = $debug_handler;
        } elseif (!$debug_handler) {
            $this->_debug_cb = null;
        }
    }

    /**
     * Add a query to the debug log.
     *
     * @internal
     *
     * @param string $sql the SQL statement
     */
    protected function add_debug_query($sql)
    {
        if ($this->_debug) {
            ++$this->_query_count;
            if ($this->_debug_cb) {
                call_user_func($this->_debug_cb, $sql);
            }
        }
    }

    /**
     * A callback that is called when a database error occurs.
     * This method will by default call the error handler if it has been set.
     *
     * @param string $errtype       The type of error
     * @param int    $error_number  The error number
     * @param string $error_message The error message
     */
    public function OnError($errtype, $error_number, $error_message)
    {
        $this->errno = $error_number;
        $this->error = $error_message;
        if ($this->_errorhandler && is_callable($this->_errorhandler)) {
            call_user_func($this->_errorhandler, $errtype, $error_number, $error_message);
        }
    }

    /**
     * Default error handler (except during site-installation)
     *
     * @internal
     *
     * @param string $errtype       The type of error
     * @param int    $error_number  The error number
     * @param string $error_message The error message
     */
    protected function on_error($errtype, $error_number, $error_msg)
    {
        if (function_exists('\\debug_to_log')) {
            debug_to_log("Database error: $errtype($error_number) - $error_msg");
            debug_bt_to_log();
            if ($this->_debug) {
                CmsApp::get_instance()->add_error(debug_display($error_msg, '', false, true));
            }
        }
    }

    //// initialization

    /**
     * Create a new database connection object.
     *
     * @deprecated Does nothing - use new <namespace>\mysqli\Connection()
     *
     */
    public static function Initialize()
    {
    }

    /**
     * Create a new data dictionary object.
     * Data Dictionary objects are used for manipulating tables, i.e: creating, altering and editing them.
     *
     * @return <namespace>DataDictionary
     */
    abstract public function NewDataDictionary();
}
