<?php
/*
Class ResultSet: represents a SQL-command result
Copyright (C) 2017-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
For CMS Made Simple <http:www.cmsmadesimple.org>
Copyright (C) 2004-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

class ResultSet extends \CMSMS\Database\ResultSet
{
    private $_result = null; //mysqli_result object (for query which returns data), or boolean
    private $_row = [];
    private $_nrows = 0;
    private $_pos = -1;
    private $_native = ''; //for PHP 5.4+, the MySQL native driver is a php.net compile-time default

    /**
     * @param $result mysqli_result object (for queries which return data), or boolean
     */
    public function __construct($result)
    {
        if ($result instanceof \mysqli_result) {
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

    protected function isNative()
    {
        if ($this->_native === '') {
            $this->_native = function_exists('mysqli_fetch_all');
        }

        return $this->_native;
    }

    public function fields($key = null)
    {
        if ($this->_row && !$this->EOF()) {
            if (empty($key)) {
                return $this->_row;
            }
            $key = (string) $key;
            if (array_key_exists($key, $this->_row)) {
                return $this->_row[$key];
            }
        }

        return null;
    }

    public function fieldCount()
    {
        return $this->_result->field_count;
    }

/*  public function currentRow()
    {
        if (!$this->EOF()) {
            return $this->_pos;
        }

        return false;
    }
*/
    public function recordCount()
    {
        return $this->_nrows;
    }

    public function EOF()
    {
        return $this->_nrows == 0 || $this->_pos < 0 || $this->_pos >= $this->_nrows;
    }

    protected function move($idx)
    {
        if ($idx == $this->_pos) {
            return true;
        }
        if ($this->_result->data_seek($idx)) {
            $this->_pos = $idx;
            $this->fetch_row();

            return true;
        }
        $this->_pos = -1;
        $this->_row = [];

        return false;
    }

    public function moveFirst()
    {
        return $this->move(0);
    }

    public function moveNext()
    {
        if ($this->_pos < $this->_nrows) {
            return $this->move($this->_pos + 1);
        }

        return false;
    }

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

    public function getOne()
    {
        if (!$this->EOF()) {
            return reset($this->_row);
        }

        return null;
    }

    protected function fetch_row()
    {
        if (!$this->EOF()) {
            $this->_row = $this->_result->fetch_array(MYSQLI_ASSOC);
        }
    }
}
