<?php
/*
Class ResultSet: methods for interacting with MySQL or compatible selection-command result
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
namespace CMSMS\Database;

use CMSMS\DeprecationNotice;
use mysqli_result;
use const CMS_DEPREC;

/**
 * A class for interacting with the results from a database selection.
 *
 * @since 3.0
 */
class ResultSet
{
    /**
     * @ignore
     */
    protected $_errno = 0;
    protected $_error = '';
    protected $_native = ''; //for PHP 5.4+, the MySQL native driver is a php.net compile-time default
    protected $_nrows = 0;
    protected $_pos = -1;
    protected $_result = null; //mysqli_result object (for query which returns data), or boolean, or int
    protected $_row = [];

    /**
     * Constructor.
     * @param mixed $result mysqli_result object (for queries which return data), or boolean or int no. of affected rows
     */
    public function __construct($result)
    {
        if ($result instanceof mysqli_result) {
            $this->_nrows = $result->num_rows;
            $this->_row = $result->fetch_array(MYSQLI_ASSOC);
            if ($this->_row) {
                $this->_pos = 0;
            }
            $this->_result = &$result;
        } else {
            $this->_result = $result;
        }
    }

    public function __destruct()
    {
        if (is_object($this->_result)) {
            $this->_result->free();
        }
    }

    /**
     * @ignore
     */
    public function __set(string $key, $val)
    {
        switch ($key) {
         case 'errno':
            $this->_errno = $val;
            break;
         case 'error':
         case 'errmsg':
            $this->_error = $val;
            break;
        }
    }

    /**
     * @ignore
     */
    public function __get(string $key)
    {
        switch ($key) {
         case 'errno':
            return $this->_errno;
         case 'error':
         case 'errmsg':
            return $this->_error;
         case 'EOF':
            return $this->EOF();
         case 'count':
            return $this->recordCount();
         case 'fields':
            return $this->fields();
         case 'result':
            return $this->_result;
        }
    }

    /**
     * Close the current ResultSet.
     * Does nothing.
     */
    public function close()
    {
    }

    /**
     * Check whether we're working with the mysqli native driver
     * @internal
     * @return bool
     */
    protected function isNative()
    {
        if ($this->_native === '') {
            $this->_native = function_exists('mysqli_fetch_all');
        }
        return $this->_native;
    }

    /**
     * Move to a specified index in the ResultSet data.
     * @internal
     *
     * @param int $idx
	 * @return bool
     */
    protected function move($idx)
    {
        if ($idx == $this->_pos) {
            return true;
        }
        if ($this->_result->data_seek($idx)) {
            $this->_pos = $idx;
            $this->_row = $this->_result->fetch_array(MYSQLI_ASSOC);
            return true;
        }
        $this->_pos = -1;
        $this->_row = [];
        return false;
    }

    /**
     * Move to the first row in the ResultSet data.
	 * @return bool
     */
    public function moveFirst()
    {
        return $this->move(0);
    }

    /**
     * Move to the next row of the ResultSet data, if possible.
	 * @return bool
     */
    public function moveNext()
    {
        if (($idx = $this->_pos) < $this->_nrows && $idx >= 0) {
            return $this->move($idx + 1);
        }
        return false;
    }

    /**
     * Get all data in the ResultSet.
     *
     * @return array, maybe empty
     */
    public function getArray()
    {
        if ($this->isNative()) {
           $this->_result->data_seek(0);
           return $this->_result->fetch_all(MYSQLI_ASSOC);
        } else {
            $results = [];
            if (($c = $this->_nrows) > 0) {
                for ($i = 0; $i < $c; ++$i) {
                    if ($this->move($i)) {
                        $results[] = $this->_row;
                    } else {
                        break; //TODO handle error
                    }
                }
            }
            return $results;
        }
    }

    /**
     * An alias for the getArray method.
     *
     * @see ResultSet::getArray()
     * @deprecated
     *
     * @return array
     */
    public function getRows()
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','getArray'));
        return $this->getArray();
    }

    /**
     * An alias for the getArray method.
     *
     * @see ResultSet::getArray()
     * @deprecated
     *
     * @return array
     */
    public function getAll()
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','getArray'));
        return $this->getArray();
    }

    /**
     * Get all data in the ResultSet as an array with first-selected values
     *  as keys.
     *
     * @return array, maybe empty
     */
    public function getAssoc($force_array = false, $first2cols = false)
    {
        $results = [];
        $n = $this->_result->field_count;
        $c = $this->_nrows;
        if ($c > 0 && $n > 1) {
            $first = key($this->_row);
            $short = ($n == 2 || $first2cols) && !$force_array;
            if ($this->isNative()) {
                $this->_result->data_seek(0);
                $data = $this->_result->fetch_all(MYSQLI_ASSOC);
                if ($short) {
                    for ($i = 0; $i < $c; ++$i) {
                        $row = $data[$i];
                        $results[trim($row[$first])] = next($row);
                        unset($data[$i]); //preserve memory footprint
                    }
                } else {
                    for ($i = 0; $i < $c; ++$i) {
                        $val = $data[$i][$first];
                        unset($data[$i][$first]);
                        $results[trim($val)] = $data[$i]; //not duplicated
                    }
                }
            } else {
                for ($i = 0; $i < $c; ++$i) {
                    if ($this->move($i)) {
                        $row = $this->_row;
                        $results[trim($row[$first])] = ($short) ? next($row) : array_slice($row, 1);
                    } else {
                        break; //TODO handle error
                    }
                }
            }
        }
        return $results;
    }

    /**
     *
     * @param bool $trim Optional flag whether to trim() each value. Default false.
     * @return array, maybe empty
     */
    public function getCol($trim = false)
    {
        $results = [];
        if (($c = $this->_nrows) > 0) {
            if ($this->isNative()) {
                $this->_result->data_seek(0);
                $data = $this->_result->fetch_all(MYSQLI_NUM);
                if (!$trim && function_exists('array_column')) {
                    return array_column($data, 0);
                }
                for ($i = 0; $i < $c; ++$i) {
                    $results[] = ($trim) ? trim($data[$i][0]) : $data[$i][0];
                    unset($data[$i]); //preserve memory footprint
                }
            } else {
                $key = key($this->_row);
                for ($i = 0; $i < $c; ++$i) {
                    if ($this->move($i)) {
                        $results[] = ($trim) ? trim($this->_row[$key]) : $this->_row[$key];
                    } else {
                        break; //TODO handle error
                    }
                }
            }
        }
        return $results;
    }

    /**
     * Return one value of one field
     *
     * @return mixed value | null
     */
    public function getOne()
    {
        if (!$this->EOF()) {
            return reset($this->_row);
        }
        return null;
    }

    /**
     * Test if we are at the end of the ResultSet data, and there are no further matches.
     *
     * @return bool
     */
    public function EOF()
    {
        return $this->_nrows == 0 || $this->_pos < 0 || $this->_pos >= $this->_nrows;
    }

    /* *
     * Get the current position in the ResultSet data.
     *
     * @return int, or false if no current position
     */
/*  public function currentRow()
    {
        if (!$this->EOF()) {
            return $this->_pos;
        }

        return false;
    }
*/
    /**
     * Return the number of rows in the current ResultSet.
     *
     * @return int
     */
    public function recordCount()
    {
        return $this->_nrows;
    }

    /**
     * Alias for the recordCount() method.
     *
     * @see ResultSet::recordCount()
     *
     * @return int
     */
    public function NumRows()
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','recordCount'));
        return $this->_nrows;
    }

    /**
     * Return the number of columns in the current ResultSet.
     *
     * @return int
     */
    public function fieldCount()
    {
        return $this->_result->field_count;
    }

    /**
     * Return all the fields, or a single field, of the current row of the ResultSet.
     *
     * @param string $key An optional field name, if not specified, the entire row will be returned
     * @return mixed single value | values-array | null
     */
    public function fields($key = null)
    {
        if ($this->_row && !$this->EOF()) {
            if (empty($key)) {
                return $this->_row;
            }
            $key = (string) $key;
            if (isset($this->_row[$key])) {
                return $this->_row[$key];
            }
        }
        return null;
    }

    /**
     * Fetch the current row, and move to the next row.
     *
     * @return array, maybe empty
     */
    public function FetchRow()
    {
        $out = $this->fields();
        if ($out !== null) {
            $this->moveNext();
            return $out;
        }
        return [];
    }
} //class
