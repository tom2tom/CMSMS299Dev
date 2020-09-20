<?php
/*
Class PrepResultSet: methods for interacting with MySQL or compatible 
 prepared selection-command result
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

use CMSMS\Database\ResultSet;
use mysqli_stmt;

/**
 * A class for interacting with the results from a prepared-selection from the database.
 *
 * @since 2.9
 */
class PrepResultSet extends ResultSet
{
    /**
     * @ignore
     */
    private $_stmt; // mysqli_stmt object reference

    /**
     * @param object $statmt mysqli_stmt
     * @param bool   $buffer optional flag whether to buffer results. Default true
     */
    public function __construct(mysqli_stmt &$statmt, $buffer = true)
    {
        $this->_stmt = $statmt;
        if ($buffer) {
            if ($statmt->store_result()) { //grab the complete result-set
                $this->_nrows = $statmt->num_rows;
                //TODO ASAP $this->_stmt->clear_result();
            } elseif ($statmt->errno > 0) {
                $this->_nrows = 0;
                //TODO handle error
            } else {
                $this->_nrows = 0;
            }
            $this->_pos = ($this->_nrows > 0) ? 0 : -1;
        } else {
            $this->_nrows = PHP_INT_MAX;
            $this->_pos = -1;
        }

        if ($this->_nrows > 0) {
            //setup for row-wise data fetching
            $i = 0;
            $fields = [];
            $rs = $statmt->result_metadata();
            while ($field = $rs->fetch_field()) {
                $nm = $field->name;
                $val = 'F'.$i++;
                $fields[$nm] = &$$val;
            }
            if ($i) {
                if (call_user_func_array([$statmt, 'bind_result'], $fields)) {
                    $statmt->fetch(); //always populates $fields with references, not actual values
                    $this->_stmt = $statmt;
                    $this->_row = $fields;
                    $this->_pos = 0;
                    return;
                }
            }
        }
    }

    public function __destruct()
    {
        $this->_stmt->free_result();
    }

    protected function move($idx)
    {
        if ($idx == $this->_pos) {
            return true;
        }
        if ($idx >= 0 && $idx < $this->_nrows) {
            $this->_stmt->data_seek($idx);
            if ($this->_stmt->fetch()) {
                $this->_pos = $idx;

                return true;
            }
        }
        $this->_pos = -1;
        $this->_row = [];

        return false;
    }

    public function getArray()
    {
        $results = [];
        if (($c = $this->_nrows) > 0) {
            for ($i = 0; $i < $c; ++$i) {
                if ($this->move($i)) {
                    //dereference the values
                    $row = [];
                    foreach ($this->_row as $key=>$val) {
                        $row[$key] = $val;
                    }
                    $results[] = $row;
                } else {
                    //TODO handle error
                    $this->_nrows = $i;
                    break;
                }
            }
        }

        return $results;
    }

    public function getAssoc($force_array = false, $first2cols = false)
    {
        $results = [];
        $c = $this->_nrows;
        $n = $this->_stmt->field_count;
        if ($c > 0 && $n > 1) {
            $short = ($n == 2 || $first2cols) && !$force_array;
            if (!$short) {
                $first = key($this->_row);
            }
            for ($i = 0; $i < $c; ++$i) {
                if ($this->move($i)) {
                    $key = trim(reset($this->_row));
                    if ($short) {
                        $results[$key] = next($this->_row);
                    } else {
                        //dereference the values
                        $row = [];
                        while (($val = next($this->_row)) !== false) {
                            $k = key($this->_row);
                            $row[$k] = $val;
                        }
                        $results[$key] = $row;
                    }
                } else {
                    //TODO handle error
                    $this->_nrows = $i;
                    break;
                }
            }
        }

        return $results;
    }

    public function getCol($trim = false)
    {
        $results = [];
        if (($c = $this->_nrows) > 0) {
            $key = key($this->_row);
            for ($i = 0; $i < $c; ++$i) {
                if ($this->move($i)) {
                    $results[] = ($trim) ? trim($this->_row[$key]) : $this->_row[$key]; //copy on write
                } else {
                    //TODO handle error
                    $this->_nrows = $i;
                    break;
                }
            }
        }

        return $results;
    }

    public function getOne()
    {
        if (!$this->EOF()) {
            //avoid returning a reference
            reset($this->_row);
            $key = key($this->_row);
            return $this->_row[$key];
        }

        return null;
    }

    public function fieldCount()
    {
        return $this->_stmt->field_count;
    }

    public function fields($key = null)
    {
        if ($this->_row) {
            if (empty($key)) {
                //dereference the values
                $row = [];
                foreach ($this->_row as $key=>$val) {
                    $row[$key] = $val;
                }
                return $row;
            }
            $key = (string) $key;
            if (isset($this->_row[$key])) {
                return $this->_row[$key];
            }
        }

        return null;
    }
} //class
