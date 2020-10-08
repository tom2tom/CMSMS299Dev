<?php
/*
Class Connection: interaction with a MySQL or compatible database
Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Database\DataDictionary;
use CMSMS\Database\ResultSet;
use CMSMS\Database\Statement;
use CMSMS\DeprecationNotice;
use DateTime;
use DateTimeZone;
use Exception;
use mysqli;
use const CMS_DEBUG;
use const CMS_DEPREC;
use function debug_bt_to_log;
use function debug_display;
use function debug_to_log;

/**
 * A class defining a MySQL database connection, and mechanisms for working with the database.
 * @since 2.9
 */
final class Connection
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
     * Defines an error in a data dictionary command.
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
    protected $_debug_cb = null;

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
//    protected $_queries = [];

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
     * @param string $database  The database we are using
     */
    protected $_database;

    /**
     * @internal
     *
     * @param string $type  The database connection type (mysqli)
     */
    protected $_type;

    /**
     * The actual PHP interface object.
     * @internal
     */
    protected $_mysql;

    /**
     * Whether $_mysql is the 'native driver' variant
     * @internal
     */
    protected $_native = ''; //for PHP 5.4+, the MySQL native driver is a php.net compile-time default

    /**
    * The offset (in seconds) between session timezone and UTC
     * @internal
     */
    protected $_time_offset;

    /**
     * @internal
     */
    protected $_in_transaction = 0;

    /**
     * @internal
     */
    protected $_in_smart_transaction = 0;

    /**
     * @internal
     */
    protected $_transaction_status = true;

    /**
     * Queue of cached results from prior pretend-async commands, pending pretend-reaps
     * @internal
     */
    protected $_asyncQ = [];

    /**
     * Constructor.
     * @param array $config Optional array | array-accessible object of
     * CMSMS settings including (among others) the ones used here:
     *  'db_hostname'
     *  'db_username'
     *  'db_password'
     *  'db_name'
     *  'db_port'
     *  'set_names' (opt)
     *  'set_db_timezone' (opt)
     *  'timezone' used only if 'set_db_timezone' is true (normally the case)
     */
    public function __construct($config = null)
    {
        if (class_exists('mysqli')) {
            if (!$config) $config = AppSingle::Config(); //normal API
            $parms = [
                $config['db_hostname'],
                $config['db_username'],
                $config['db_password'],
                $config['db_name'],
            ];
            if (!empty($config['db_port']) || is_numeric($config['db_port'])) {
                $parms[] = (int)$config['db_port'];
            }
            mysqli_report(MYSQLI_REPORT_STRICT);
            try {
                $this->_mysql = new mysqli(...$parms);
                if (!$this->_mysql->connect_error) {
                    $this->_database = $config['db_name'];
                    $this->_type = 'mysqli';
                    $this->_debug = CMS_DEBUG;
                    if ($this->_debug) {
                        $this->_debug_cb = 'debug_buffer';
                    }

                    if (!AppState::test_state(AppState::STATE_INSTALL)) {
                        $this->_errorhandler = [$this, 'on_error'];
                    }
                    if (!empty($config['set_names'])) {
                        $this->_mysql->set_charset($config['set_names']);
                    } else {
                        $this->_mysql->set_charset('utf8mb4');
                    }
                    if (!empty($config['set_db_timezone'])) {
                        //see also strftzone_adjuster() in misc.functions.php
                        try {
                            $dt = new DateTime('', new DateTimeZone($config['timezone']));
                        } catch (Exception $e) {
                            $this->_mysql = null;
                            $this->on_error(self::ERROR_PARAM, $e->getCode(), $e->getMessage());
                        }
                        $offset = $dt->getOffset();
                        $this->_time_offset = $offset;
                        if ($offset < 0) {
                            $offset = -$offset;
                            $symbol = '-';
                        } else {
                            $symbol = '+';
                        }
                        $hrs = (int)($offset / 3600);
                        $mins = (int)($offset % 3600 / 60);
                        $sql = sprintf("SET time_zone = '%s%02d:%02d'", $symbol, $hrs, $mins);
                        $this->execute($sql);
                    } else {
                        $now = time();
                        $s = $this->getOne('SELECT FROM_UNIXTIME('.$now.')');
                        $dt = new DateTime('@0', null);
                        $dt->modify($s);
                        $this->_time_offset = $dt->getTimestamp() - $now;
                    }
                } else {
                    $this->_database = null;
                    $this->_mysql = null;
                    $this->OnError(self::ERROR_CONNECT, mysqli_connect_errno(), mysqli_connect_error());
                }
            } catch (Exception $e) {
                $this->_database = null;
                $this->_mysql = null;
                $this->OnError(self::ERROR_CONNECT, mysqli_connect_errno(), mysqli_connect_error());
            }
        } else {
            $this->_database = null;
            $this->_mysql = null;
            $this->OnError(self::ERROR_CONNECT, 98,
                'Configuration error: mysqli class is not available');
        }
    }

    /**
     * @ignore
     */
    public function __get($key)
    {
        switch ($key) {
         case 'database':
            return $this->_database;
         case 'query_time_total':
            return $this->_query_time_total;
         case 'query_count':
            return $this->_query_count;
         case 'time_offset':
            if (isset($this->$_time_offset)) return $this->$_time_offset;
            //no break here
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
         case 'database':
            return !empty($this->_database);
         case 'query_time_total':
         case 'query_count':
            return true;
         case 'time_offset':
            return isset($this->$_time_offset);
         default:
           return false;
        }
    }

    //// session

    /**
     * Open the database connection.
     *
     * @deprecated
     * @return bool true always
     */
    public function connect()
    {
        return true;
    }

    /**
     * Create a new database connection object.
     *
     * @deprecated Does nothing - use new <namespace>\Connection()
     *
     */
    public static function Initialize()
    {
    }

    /**
     * Test if the connection object is connected to the database.
     *
     * @return bool
     */
    public function isConnected()
    {
        return is_object($this->_mysql);
    }

    /**
     * Close the database connection.
     */
    public function close()
    {
        if ($this->_mysql) {
            $this->_mysql->close();
            $this->_mysql = null;
        }
    }

    /**
     * An alias for close.
     */
    final public function Disconnect()
    {
        return $this->close();
    }

    /**
     * Return the database interface-type (mysqli).
     *
     * @return string
     */
    public function dbType()
    {
        return $this->_type;
    }

    /**
     * Return whether the database interface-type is the 'native driver' variant
     * @return bool
     */
    public function isNative()
    {
        if ($this->_native === '') {
            $this->_native = function_exists('mysqli_fetch_all');
        }
        return $this->_native;
    }

    /**
     * Return the PHP database-interface object.
     *
     * @return object | null
     */
    public function get_inner_mysql()
    {
        return $this->_mysql;
    }

    //// utilities

    /**
     * Return a single-quoted and escaped version of $str e.g. for use in a database command.
     * The characters processed are the MySQL standards: NUL (ASCII 0), \n, \r, \, ', ", \Z (ASCII 26),
     * plus '%' and '_' which are are particularly relevant for commands having 'LIKE',
     * plus '`' and ';', for injection mitigation.
     * Warning: This method may require two way traffic with the database depending upon the database.
     *
     * @param string $str
     *
     * @return string
     */
    public function qStr($str)
    {
        if ($str !== '') {
            return  "'".addcslashes($this->_mysql->real_escape_string($str), '%_`;')."'";
        }
        return '';
    }

    /**
     * An alias for the qStr method.
     *
     * @deprecated since 2.3
     *
     * @param string $str
     *
     * @return string
     */
    public function QMagic($str)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','qStr'));
        return $this->qStr($str);
    }

    /**
     * qStr without surrounding single-quotes.
     * @see Connection::qStr()
     *
     * @param string $str
     *
     * @return string
     */
    public function addQ($str)
    {
        if ($str !== '') {
            return addcslashes($this->_mysql->real_escape_string($str), '%_`;');
        }
        return '';
    }

    /**
     * Return the SQL for a string concatenation.
     * This function accepts a variable number of string arguments.
     *
     * @param $str   First string to concatenate
     * @param $str,. Any number of strings to concatenate
     *
     * @return string
     */
    public function concat()
    {
        $arr = func_get_args();
        $list = implode(', ', $arr);

        if (strlen($list) > 0) {
            return "CONCAT($list)";
        }
    }

    /**
     * Return the SQL for testing whether a value is null.
     *
     * @param string $field  The field to test
     * @param string $ifNull The value to use if $field is null
     *
     * @return string
     */
    public function ifNull($field, $ifNull)
    {
        return "IFNULL($field, $ifNull)";
    }

    /**
     * Return the number of rows affected by the last query.
     *
     * @return int
     */
    public function affected_rows()
    {
        return $this->_mysql->affected_rows;
    }

    /**
     * Return the numeric ID of the last insert query into a table with an auto-increment field.
     *
     * @return int
     */
    public function insert_id()
    {
        return $this->_mysql->insert_id;
    }

    //// primary query functions

    /**
     * The primary function for communicating with the database.
     *
     * @param string $sql SQL statement to be executed
     * @return mixed ResultSet object | integer (affected rows|last insert id) | boolean | null
     */
    protected function do_sql($sql)
    {
        $this->_sql = $sql;
        $dml = strncasecmp($sql, 'INSERT INTO', 11) == 0 ||
               strncasecmp($sql, 'UPDATE', 6) == 0 ||
               strncasecmp($sql, 'DELETE FROM', 11) == 0;
        if ($this->_debug) {
            $time_start = microtime(true);
            if ($dml) {
                $result = $this->_mysql->real_query($sql); //boolean
            } else {
                $result = $this->_mysql->query($sql); //mysqli_result or boolean
            }
            $this->_query_time_total += microtime(true) - $time_start;
        } elseif ($dml) {
            $result = $this->_mysql->real_query($sql);
        } else {
            $result = $this->_mysql->query($sql);
        }
        if ($result) {
            if ($this->_debug) {
                $this->add_debug_query($sql);
            }
            $this->errno = 0;
            $this->error = '';
            if ($dml) {
                $num = $this->_mysql->affected_rows;
                if ($num == 1 && ($sql[0] == 'I' || $sql[0] == 'i')) {
                    return (($num = $this->_mysql->insert_id) > 0) ? $num : 1;
                }
                //support strict false-checks by callers
                return ($num > 0) ? $num : false;
            } elseif (is_bool($result)) {
                return $result;
            }
            return new ResultSet($result);
        }
        $this->failTrans();

        $errno = $this->_mysql->errno;
        $error = $this->_mysql->error;
        $this->OnError(self::ERROR_EXECUTE, $errno, $error);
        return null;
    }

    /**
     * @internal
     * No error checking
     * @return array : individual statements' results, normally as many
     * of them as there were separate SQL commands, but any error will
     * abort the reaults hence truncate the array.
     * Array members may be boolean | mysqli_result object
     */
    protected function do_multisql($sql)
    {
        if ($this->_mysql->multi_query($sql)) {
            $result = [];
            do {
                $result[] = $this->_mysql->store_result(); //maybe bad, no actual data
            } while ($this->_mysql->more_results() && $this->_mysql->next_result());
            return $result;
        }
        return [];
    }


    /**
     * Interpret the varargs parameters supplied directly or indirectly
     * to execute methods.
     *
     * $param mixed $vals maybe-empty array | anything else
     * @return maybe-empty array
     */
    public function check_params($vals) : array
    {
        if (is_array($vals)) {
            if (count($vals) == 1 && is_array($vals[0])) {
                return $vals[0];
            }
            return $vals;
        }
        return [];
    }

    /**
     * Prepare (compile) @sql for parameterized and/or repeated execution.
     *
     * @param string $sql The SQL query
     *
     * @return a Statement object if @sql is valid, or false
     */
    public function prepare($sql)
    {
        $stmt = new Statement($this, $sql);
        if ($stmt->prepare($sql)) {
            $this->_sql = $sql;
            return $stmt;
        }
        return false;
    }

    /**
     * Parse and execute an SQL prepared statement or query.
     *
     * @param mixed $sql string | Statement object
     * @param varargs $bindvars array | series of command-parameter-value(s)
     *  to fill placeholders in $sql | nothing
     *
     * @return mixed <namespace>ResultSet or a subclass of that | num > 0 | bool | null
     */
    public function execute($sql, ...$bindvars)
    {
        $bindvars = $this->check_params($bindvars);
        if ($bindvars) {
            if (is_string($sql)) {
                $stmt = new Statement($this, $sql);
                return $stmt->execute($bindvars);
            } elseif (is_object($sql) && ($sql instanceof Statement)) {
                return $sql->execute($bindvars);
            } else {
                $errno = 4;
                $error = 'Invalid bind-parameter(s) supplied to execute method';
                $this->OnError(self::ERROR_PARAM, $errno, $error);
                return null;
            }
        }

        return $this->do_sql($sql);
    }

    /**
     * As for execute, but non-blocking. Works as such only if the native driver
     * is present. Otherwise reverts to normal execution, and caches the result.
     *
     * @param mixed $sql string | Statement object
     * @param varargs $bindvars array | series of command-parameter-value(s)
     *  to fill placeholders in $sql | nothing
     */
    public function async_execute($sql, ...$bindvars)
    {
/* NOT YET IMPLEMENTED
        if ($this->isNative()) {
//TODO
        } else {
            $rs = $this->execute($sql, ...$bindvars);
            if ($rs) {
                $this->_asyncQ[] = $rs;
            } else {
//TODO arrange to handle error when 'reaped'
            }
        }
*/
        return null;
    }

    /**
     * Get result from async SQL query. If the native driver is not present, this
     * just returns the cached result of the prior not-really-async command.
     */
    public function reap()
    {
        if ($this->isNative()) {
            $rs = $this->_mysql->reap_async_query();
        } else {
            $rs = array_shift($this->_asyncQ);
        }
        if ($rs) { // && $rs is not some error-data
            $this->_conn->errno = 0;
            $this->_conn->error = '';
            return new ResultSet($rs);
        } else {
            $errno = 98;
            $error = 'No async result available';
            $this->processerror(self::ERROR_EXECUTE, $errno, $error);
            return null;
        }
    }

    /**
     * Parse and execute multiple ';'-joined parameterized or plain SQL statements or queries.
     *
     * @param mixed $sql string | Statement object
     * @param varargs $bindvars array | series of command-parameter-value(s)
     *  to fill placeholders in $sql | nothing
     */
    public function multi_execute($sql, ...$bindvars)
    {
/* NOT YET IMPLEMENTED
        $bindvars = $this->check_params($bindvars);
        if ($bindvars) {
            //TODO parse and process, $this->do_multisql($sql) etc
        } else {
            $result = $this->do_multisql($sql);
            //TODO deal with $result[]
        }
*/
        return null;
    }

    /**
     * Execute an SQL command, to retrieve (at most) @nrows records.
     *
     * @param string $sql    The SQL to execute
     * @param int   $nrows   Optional number of rows to return, default all (0)
     * @param int   $offset  Optional 0-based starting-offset of rows to return, default 0
     * @param varargs $bindvars array | series of parameter-value(s) to
     *  fill placeholders in $sql | nothing
     *
     * @return mixed <namespace>ResultSet or a subclass
     *  when a SELECT retrieves nothing or other command fails, default false
     */
    public function selectLimit($sql, $nrows = 0, $offset = 0, ...$bindvars)
    {
        if ($nrows > 0) {
            $xql = ' LIMIT '.$nrows;
        } else {
            $xql = '';
        }
        if ($offset > 0) {
            $xql .= ' OFFSET '.$offset;
        } elseif ($offset < 0) {
            //TODO N = SELECT COUNT results, then use OFFSET = N + $offset ...
        }
        if ($xql) {
            $sql .= $xql;
        }

        return $this->execute($sql, ...$bindvars);
    }

    /**
     * Execute an SQL statement and return all the results as an array.
     *
     * @param mixed $sql string | Statement object
     * @param mixed $bindvars array | falsy Optional value-parameters to fill placeholders (if any) in @sql
     * @param int   $nrows   Optional number of rows to return, default all (0)
     * @param int   $offset  Optional 0-based starting-offset of rows to return, default 0
     *
     * @return array Numeric-keyed matched results, or empty
     */
    public function getArray($sql, $bindvars = false, $nrows = 0, $offset = 0)
    {
        if (!$bindvars) { $bindvars = []; } // don't mistake a single falsy parameter
        if ($nrows == 0 && $offset == 0) {
            $rs = $this->execute($sql, $bindvars);
        } else {
            $rs = $this->selectLimit($sql, $nrows, $offset, $bindvars);
        }
        if ($rs) {
            return $rs->getArray();
        }

        return [];
    }

    /**
     * An alias for the getArray method.
     * @deprecated since 2.9 instead use getArray()
     *
     * @param string $sql     The SQL statement to execute
     * @param mixed  $bindvars array | falsy Optional value-parameters to fill placeholders (if any) in @sql
     *
     * @return array Numeric-keyed matched results, or empty
     */
    public function getAll($sql, $bindvars = false, $nrows = 0, $offset = 0)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','getArray'));
        return $this->getArray($sql, $bindvars, $nrows, $offset);
    }

    /**
     * Execute an SQL statement and return all the results as an array, with
     * the value of the first-requested-column as the key for each row.
     *
     * @param string $sql     The SQL statement to execute
     * @param mixed  $bindvars array | falsy Optional Value-parameters to fill placeholders (if any) in @sql
     * @param bool   $force_array Optionally force each element of the Return to be an associative array
     * @param bool   $first2cols  Optionally Return only the first 2 columns in an associative array.  Does not work with force_array
     * @param int    $nrows   Optional number of rows to return, default all (0)
     * @param int    $offset  Optional 0-based starting-offset of rows to return, default 0
     *
     * @return associative array of matched results, or empty
     */
    public function getAssoc($sql, $bindvars = false, $force_array = false, $first2cols = false, $nrows = 0, $offset = 0)
    {
        if (!$bindvars) { $bindvars = []; } // don't mistake a single falsy parameter
        if ($nrows == 0 && $offset == 0) {
            $rs = $this->execute($sql, $bindvars);
        } else {
            $rs = $this->selectLimit($sql, $nrows, $offset, $bindvars);
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
     * @param mixed  $bindvars array | falsy Optional Value-parameters to fill placeholders (if any) in @sql
     * @param bool   $trim    Optionally trim the returned values
     * @param int    $nrows   Optional number of rows to return, default all (0)
     * @param int    $offset  Optional 0-based starting-offset of rows to return, default 0
     *
     * @return array of results, one member per row matched, or empty
     */
    public function getCol($sql, $bindvars = false, $trim = false, $nrows = 0, $offset = 0)
    {
        if (!$bindvars) { $bindvars = []; } // don't mistake a single falsy parameter
        if ($nrows == 0 && $offset == 0) {
            $rs = $this->execute($sql, $bindvars);
        } else {
            $rs = $this->selectLimit($sql, $nrows, $offset, $bindvars);
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
     * @param mixed $sql string | Statement object
     * @param mixed $bindvars array | falsy Optional value-parameters to fill placeholders (if any) in @sql
     * @param int   $offset  Optional 0-based starting-offset of rows to return, default 0
     *
     * @return associative array representing a single ResultSet row, or empty
     */
    public function getRow($sql, $bindvars = false, $offset = 0)
    {
        if (!$bindvars) { $bindvars = []; } // don't mistake a single falsy parameter
        if ($offset == 0) {
            if (stripos($sql, 'LIMIT') === false) {
                $sql .= ' LIMIT 1';
            }
            $rs = $this->execute($sql, $bindvars);
        } else {
            $rs = $this->selectLimit($sql, 1, $offset, $bindvars);
        }
        if ($rs) {
            return $rs->fields();
        }

        return [];
    }

    /**
     * Execute an SQL statement and return a single value.
     *
     * @param mixed $sql string | Statement object
     * @param mixed $bindvars array | falsy Optional values to fill placeholders (if any) in @sql
     * @param int   $offset  Optional 0-based starting-offset of rows to return, default 0
     *
     * @return mixed value | null
     */
    public function getOne($sql, $bindvars = false, $offset = 0)
    {
        if (!$bindvars) { $bindvars = []; } // don't mistake a single falsy parameter
        if ($offset == 0) {
            if (stripos($sql, 'LIMIT') === false) {
                $sql .= ' LIMIT 1';
            }
            $rs = $this->execute($sql, $bindvars);
        } else {
            $rs = $this->selectLimit($sql, 1, $offset, $bindvars);
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
     * @return mixed value | null
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
    public function beginTrans()
    {
        if (!$this->_in_smart_transaction) {
            // allow nesting in this case.
            ++$this->_in_transaction;
            $this->_transaction_failed = false;
            $this->_mysql->query('START TRANSACTION');
//          $this->_mysql->begin_transaction(); PHP5.5+
        }
        return true;
    }

    /**
     * Begin a smart transaction.
     */
    public function startTrans()
    {
        if ($this->_in_smart_transaction) {
            ++$this->_in_smart_transaction;
            return true;
        }

        if ($this->_in_transaction) {
            $this->OnError(self::ERROR_TRANSACTION, -1, 'Bad Transaction: startTrans called within beginTrans');
            return false;
        }

        $this->_transaction_status = true;
        ++$this->_in_smart_transaction;
        $this->beginTrans();
        return true;
    }

    /**
     * Complete a smart transaction.
     * This method will either do a rollback or a commit depending upon if errors
     * have been detected.
     */
    public function completeTrans($autoComplete = true)
    {
        if ($this->_in_smart_transaction > 0) {
            --$this->_in_smart_transaction;
            return true;
        }

        if ($this->_transaction_status && $autoComplete) {
            if (!$this->commitTrans()) {
                $this->_transaction_status = false;
            }
        } else {
            $this->rollbackTrans();
        }
        $this->_in_smart_transaction = 0;

        return $this->_transaction_status;
    }

    /**
     * Commit a simple transaction.
     *
     * @param bool $ok Indicates whether there is success or not
     */
    public function commitTrans($ok = true)
    {
        if (!$ok) {
            return $this->rollbackTrans();
        }

        if (!$this->_in_transaction) {
            $this->OnError(self::ERROR_TRANSACTION, -1, 'beginTrans has not been called');
            return false;
        }

        if ($this->_mysql->commit()) {
            --$this->_in_transaction;
            return true;
        }

        $this->OnError(self::ERROR_TRANSACTION, $this->_mysql->errno, $this->_mysql->error);
        return false;
    }

    /**
     * Roll back a simple transaction.
     */
    public function rollbackTrans()
    {
        if (!$this->_in_transaction) {
            $this->OnError(self::ERROR_TRANSACTION, -1, 'beginTrans has not been called');
            return false;
        }

        if ($this->_mysql->rollback()) {
            --$this->_in_transaction;
            return true;
        }

        $this->OnError(self::ERROR_TRANSACTION, $this->_mysql->errno, $this->_mysql->error);
        return false;
    }

    /**
     * Mark a transaction as failed.
     */
    public function failTrans()
    {
        $this->_transaction_status = false;
    }

    /**
     * Test if a transaction has failed.
     *
     * @return bool
     */
    public function hasFailedTrans()
    {
        if ($this->_in_smart_transaction > 0) {
            return $this->_transaction_status == false;
        }
        return false;
    }

    /* *
     * Create a row- (and if specified, column-) lock for the duration of
     * a transaction. If no transaction has been started, one is begun.
     * As in ADOdb v.5
     *
     * @param string $tablelist Name(s) of table(s) to be processed. Comma-separated if < 1.
     * @param string $where     Optional row identifier, effectively an SQL WHERE string but without actual 'WHERE'
     * @param string $colname   Optional name of column to be locked
     * @return bool indicating success
     */
/*  public function rowLock(string $tablelist, string $where = '', string $colname = '') : bool
    {
        //TODO MySQL row locks work only with InnoDB engine, others lock whole table
    }
*/

    //// sequence tables

    /**
     * For use with sequence tables, this method will generate a new ID value.
     *
     * @param string $seqname The name of the sequence table
     *
     * @return int
     */
    public function genId($seqname)
    {
        //kinda-atomic update + select TODO CHECK thread-safety
        $query = "UPDATE $seqname SET id = LAST_INSERT_ID(id + 1);SELECT LAST_INSERT_ID()";
        if ($this->_mysql->multi_query($query)) {
            $this->_mysql->next_result();
            $rs = $this->_mysql->use_result(); //block while we're working
            $vals = $rs->fetch_row();
            $rs->close();
            return $vals[0];
        }
        //TODO handle error
        return -1;
    }

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
    public function createSequence($seqname, $startID = 0)
    {
        //TODO ensure this is really an upsert, cuz' can be repeated during failed installation
        $rs = $this->do_sql("CREATE TABLE $seqname (id INT(4) UNSIGNED) ENGINE=MYISAM COLLATE ascii_general_ci");
        if ($rs) {
            $v = (int) $startID;
            $rs = $this->do_sql("INSERT INTO $seqname VALUES ($v)");
        }
        return $rs !== false;
    }

    /**
     * Drop a sequence table.
     *
     * @param string $seqname The name of the sequence table
     *
     * @return bool
     */
    public function dropSequence($seqname)
    {
        $rs = $this->do_sql("DROP TABLE $seqname");
        return $rs !== false;
    }

    //// time and date stuff

    /**
     * Get the difference (in seconds) between server session-timezone and UTC
     *
     * @return mixed int | null if N/A
     */
    public function dbTimeOffset()
    {
        return isset($this->$_time_offset) ? $this->$_time_offset : null;
    }

    /**
     * A utility method to convert a *NIX timestamp into a database-specific
     * string suitable for use in queries.
     *
     * @param mixed $time int timestamp | string (e.g. from PHP Date()), or DateTime object
     * @param bool $quoted optional flag whether to quote the returned string
     *
     * @return mixed optionally quoted string representing server/local date & time, or NULL
     */
    public function dbTimeStamp($time, $quoted = true)
    {
        if (empty($time) && !is_numeric($time)) {
            return ($quoted) ? 'NULL' : null;
        }

        if (is_numeric($time)) {
            $time = (int)($time + 0);
        } elseif (is_string($time)) {
            if (strcasecmp($time, 'NULL') == 0) {
                return ($quoted) ? 'NULL' : null;
            }
            $lvl = error_reporting(0);
            $time = strtotime($time);
            error_reporting($lvl);
        } elseif ($time instanceof DateTime) {
            $time = $time->getTimestamp();
        }

        if ($time > 0) {
            $date = date('Y-m-d H:i:s', $time);
            return ($quoted) ? $this->qStr($date) : $this->addQ($date);
        }
        return ($quoted) ? 'NULL' : null;
    }

    /**
     * A convenience method for converting a database specific string representing a date and time
     * into a *NIX timestamp. Merely executes PHP strtotime(). No error processing.
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
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','unixTimeStamp'));
        return $this->unixTimeStamp();
    }

    /**
     * Convert a date into something that is suitable for writing to a database.
     *
     * @param mixed $date string date | integer timestamp | DateTime object
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
     * Generate a *NIX timestamp representing the start of the current day.
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
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','unixDate'));
        return $this->unixDate();
    }

    //// error and debug message handling

    /**
     * Return a string describing the latest error (if any).
     *
     * @return string
     */
    public function errorMsg()
    {
        if ($this->_mysql) {
            return $this->_mysql->error;
        }
        return mysqli_connect_error();
    }

    /**
     * Return the latest error number (if any).
     *
     * @return int
     */
    public function ErrorNo()
    {
        if ($this->_mysql) {
            return $this->_mysql->errno;
        }
        return mysqli_connect_errno();
    }

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
                AppSingle::App()->add_error(debug_display($error_msg, '', false, true));
            }
        }
    }

    //// dictionary

    /**
     * Create a new data dictionary object.
     * Data Dictionary objects are used for manipulating tables, i.e: creating, altering and editing them.
     * @deprecated since 2.3 use new DataDictionary()
     *
     * @return <namespace>DataDictionary
     */
    public function NewDataDictionary()
    {
        return new DataDictionary($this);
    }
} //class
