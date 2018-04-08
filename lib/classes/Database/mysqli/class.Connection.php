<?php
/*
Class Connection: represents a MySQL database connection
Copyright (C) 2017-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
This file is a component of CMS Made Simple <http:www.cmsmadesimple.org>

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

namespace CMSMS\Database\mysqli;

class Connection extends \CMSMS\Database\Connection
{
    protected $_mysql;
    protected $_in_transaction = 0;
    protected $_in_smart_transaction = 0;
    protected $_transaction_status = true;
    protected $_native = ''; //for PHP 5.4+, the MySQL native driver is a php.net compile-time default
    private $_asyncQ = []; // queue of cached results from prior pretend-async commands, pending pretend-reaps

    /*
     * @param array $config Optional assoc. array of db connection parameters etc,
     * including at least:
     *  'db_hostname'
     *  'db_username'
     *  'db_password'
     *  'db_name'
     *  'db_port'
     *  'set_names' (opt)
     *  'set_db_timezone' (opt)
     *  'timezone' used only if 'set_db_timezone' is true
     */
    public function __construct($config = null)  //installer-API
    {
        if (class_exists('\mysqli')) {
            if (!$config) $config =  \cms_config::get_instance(); //normal API
            mysqli_report(MYSQLI_REPORT_STRICT);
            try {
                $this->_mysql = new \mysqli(
                 $config['db_hostname'], $config['db_username'],
                 $config['db_password'], $config['db_name'],
                 (int)$config['db_port']);
                if (!$this->_mysql->connect_error) {
                    parent::__construct();
                    $this->_type = 'mysqli';
                    if (!empty($config['set_names'])) { //N/A during installation
                        $this->_mysql->set_charset('utf8');
                    }
                    if (!empty($config['set_db_timezone'])) { //ditto
                        try {
                            $dt = new \DateTime(new \DateTimeZone($config['timezone']));
                        } catch (\Exception $e) {
                            $this->_mysql = null;
                            $this->on_error(parent::ERROR_PARAM, $e->getCode(), $e->getMessage());
                        }
                        $offset = $dt->getOffset();
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
                    }
                } else {
                    $this->_mysql = null;
                    $this->on_error(parent::ERROR_CONNECT, mysqli_connect_errno(), mysqli_connect_error());
                }
            } catch (\Exception $e) {
                $this->_mysql = null;
                $this->on_error(parent::ERROR_CONNECT, mysqli_connect_errno(), mysqli_connect_error());
            }
        } else {
            $this->_mysql = null;
            $this->on_error(parent::ERROR_CONNECT, 98,
                'Configuration error: mysqli class is not available');
        }
    }

    public function isNative()
    {
        if ($this->_native === '') {
            $this->_native = function_exists('mysqli_fetch_all');
        }

        return $this->_native;
    }

    public function close()
    {
        if ($this->_mysql) {
            $this->_mysql->close();
            $this->_mysql = null;
        }
    }

    public function get_inner_mysql()
    {
        return $this->_mysql;
    }

    public function isConnected()
    {
        return is_object($this->_mysql);
    }

    public function errorMsg()
    {
        if ($this->_mysql) {
            return $this->_mysql->error;
        }

        return mysqli_connect_error();
    }

    public function ErrorNo()
    {
        if ($this->_mysql) {
            return $this->_mysql->errno;
        }

        return mysqli_connect_errno();
    }

    public function affected_rows()
    {
        return $this->_mysql->affected_rows;
    }

    public function insert_id()
    {
        return $this->_mysql->insert_id;
    }

    public function qStr($str)
    {
        // note... this could be a two way tcp/ip or socket communication
        return "'".$this->_mysql->escape_string($str)."'";
    }

    public function addQ($str)
    {
        return $this->_mysql->escape_string($str);
    }

    public function concat()
    {
        $arr = func_get_args();
        $list = implode(', ', $arr);

        if (strlen($list) > 0) {
            return "CONCAT($list)";
        }
    }

    public function ifNull($field, $ifNull)
    {
        return " IFNULL($field, $ifNull)";
    }

    /**
     * @internal
     * no error checking
     * no return data
     */
    protected function do_multisql($sql)
    {
        if ($this->_mysql->multi_query($sql)) {
            do {
                $res = $this->_mysql->store_result();
            } while ($this->_mysql->more_results() && $this->_mysql->next_result());
        }
    }

    /**
     * @param string sql SQL statment to be executed
     *
     * @return ResultSet object, or null
     */
    protected function do_sql($sql)
    {
        $this->_sql = $sql;
        if ($this->_debug) {
            $time_start = microtime(true);
            $result = $this->_mysql->query($sql); //mysqli_result or boolean
            $this->_query_time_total += microtime(true) - $time_start;
        } else {
            $result = $this->_mysql->query($sql);
        }
        if ($result) {
            if ($this->_debug) {
                $this->add_debug_query($sql);
            }
            $this->errno = 0;
            $this->error = '';

            return new ResultSet($result);
        }
        $this->failTrans();

        $errno = $this->_mysql->errno;
        $error = $this->_mysql->error;
        $this->OnError(parent::ERROR_EXECUTE, $errno, $error);

        return null;
    }

    public function prepare($sql)
    {
        $stmt = new Statement($this, $sql);
        if ($stmt->prepare($sql)) {
            $this->_sql = $sql;

            return $stmt;
        }

        return false;
    }

    public function execute($sql, $valsarr = null)
    {
        if ($valsarr) {
            if (!is_array($valsarr)) {
                $valsarr = [$valsarr];
            }
            if (is_string($sql)) {
                $stmt = new Statement($this, $sql);

                return $stmt->execute($valsarr);
            } elseif (is_object($sql) && $sql instanceof CMSMS\Database\mysqli\Statement) {
                return $sql->execute($valsarr);
            } else {
                $errno = 4;
                $error = 'Invalid bind-parameter(s) supplied to execute method';
                $this->OnError(parent::ERROR_PARAM, $errno, $error);

                return null;
            }
        }

        return $this->do_sql($sql);
    }

    public function async_execute($sql, $valsarr = null)
    {
        if ($this->isNative()) {
//TODO
        } else {
            $rs = $this->execute($sql, $valsarr);
            if ($rs) {
                $this->_asyncQ[] = $rs;
            } else {
//TODO arrange to handle error when 'reaped'
            }
        }
    }

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
            $this->processerror(parent::ERROR_EXECUTE, $errno, $error);

            return null;
        }
    }

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

    public function startTrans()
    {
        if ($this->_in_smart_transaction) {
            ++$this->_in_smart_transaction;

            return true;
        }

        if ($this->_in_transaction) {
            $this->OnError(parent::ERROR_TRANSACTION, -1, 'Bad Transaction: startTrans called within beginTrans');

            return false;
        }

        $this->_transaction_status = true;
        ++$this->_in_smart_transaction;
        $this->beginTrans();

        return true;
    }

    public function rollbackTrans()
    {
        if (!$this->_in_transaction) {
            $this->OnError(parent::ERROR_TRANSACTION, -1, 'beginTrans has not been called');

            return false;
        }

        if ($this->_mysql->rollback()) {
            --$this->_in_transaction;

            return true;
        }

        $this->OnError(parent::ERROR_TRANSACTION, $this->_mysql->errno, $this->_mysql->error);
        return false;
    }

    public function commitTrans($ok = true)
    {
        if (!$ok) {
            return $this->rollbackTrans();
        }

        if (!$this->_in_transaction) {
            $this->OnError(parent::ERROR_TRANSACTION, -1, 'beginTrans has not been called');

            return false;
        }

        if ($this->_mysql->commit()) {
            --$this->_in_transaction;

            return true;
        }

        $this->OnError(parent::ERROR_TRANSACTION, $this->_mysql->errno, $this->_mysql->error);
        return false;
    }

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

    public function failTrans()
    {
        $this->_transaction_status = false;
    }

    public function hasFailedTrans()
    {
        if ($this->_in_smart_transaction > 0) {
            return $this->_transaction_status == false;
        }

        return false;
    }

    //kinda-atomic update + select TODO CHECK thread-safety
    public function genId($seqname)
    {
        $this->_mysql->query("UPDATE $seqname SET id = LAST_INSERT_ID(id) + 1");
        $rs = $this->_mysql->query('SELECT LAST_INSERT_ID()');
        if ($rs) {
            $rs->data_seek(0);
            $valsarr = $rs->fetch_array(MYSQLI_NUM);
            return $valsarr[0] + 1;
        }
        //TODO handle error
        return -1;
    }

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

    public function dropSequence($seqname)
    {
        $rs = $this->do_sql("DROP TABLE $seqname");

        return $rs !== false;
    }

    public function NewDataDictionary()
    {
        return new DataDictionary($this);
    }
}
