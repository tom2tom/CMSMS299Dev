<?php
/*
Class ResultSet: represents a SQL-command result
Copyright (C) 2017-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

/**
 * A class defining a ResultSet and how to interact with results from a database query.
 *
 * @author Robert Campbell
 *
 * @since 2.2
 */
abstract class ResultSet
{
    /**
     * @ignore
     */
    protected $_errno = 0;
    protected $_error = '';

    /**
     * @ignore
     */
    public function __set($key, $val)
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
    public function __get($key)
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
        }
    }

    /**
     * Move to the first row in the ResultSet data.
     */
    abstract public function moveFirst();

    /**
     * Move to the next row of the ResultSet data.
     */
    abstract public function moveNext();

    /**
     * Move to a specified index in the ResultSet data.
     *
     * @param int $idx
     */
    abstract protected function move($idx);

    /**
     * Get all data in the ResultSet as an array.
     *
     * @return array
     */
    abstract public function getArray();

    /**
     * An alias for the getArray method.
     *
     * @see getArray()
     *
     * @return array
     *
     * @deprecated
     */
    public function getRows()
    {
        return $this->getArray();
    }

    /**
     * An alias for the getArray method.
     *
     * @see getArray()
     *
     * @return array
     *
     * @deprecated
     */
    public function getAll()
    {
        return $this->getArray();
    }

    /**
     * Get all data in the ResultSet as an array with first-selected values
     *  as keys.
     *
     * @return array
     */
    abstract public function getAssoc($force_array = false, $first2cols = false);

    /**
     * Return one value of one field
     *
     * @return mixed
     */
    abstract public function getOne();

    /**
     * Test if we are at the end of the ResultSet data, and there are no further matches.
     *
     * @return bool
     */
    abstract public function EOF();

    /**
     * Close the current ResultSet.
     */
    public function close()
    {
    }

    /* *
     * Get the current position in the ResultSet data.
     *
     * @return int, or false if no current position
     */
//    abstract public function currentRow();

    /**
     * Return the number of rows in the current ResultSet.
     *
     * @return int
     */
    abstract public function recordCount();

    /**
     * Alias for the recordCount() method.
     *
     * @see recordCount();
     *
     * @return int
     */
    public function NumRows()
    {
        return $this->recordCount();
    }

    /**
     * Return the number of columns in the current ResultSet.
     *
     * @return int
     */
	abstract public function fieldCount();

    /**
     * Return all the fields, or a single field, of the current row of the ResultSet.
     *
     * @param string $field An optional field name, if not specified, the entire row will be returned
     *
     * @return mixed|array Either a single value, or an array, or null
     */
    abstract public function fields($field = null);

    /**
     * Fetch the current row, and move to the next row.
     *
     * @return array
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

    /**
     * @internal
     */
    abstract protected function fetch_row();
}
