<?php
/*
-------------------------------------------------------------------------
Module: \CMSMS\Database\ConnectionSpec (C) 2017 Robert Campbell
        <calguy1000@cmsmadesimple.org>
A class to define how to connect to a database.
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

namespace CMSMS\Database;

/**
 * A class defining a ResultSet and how to interact with results from a database query.
 *
 * @author Robert Campbell
 * @copyright Copyright (C) 2017, Robert Campbell <calguy1000@cmsmadesimple.org>
 *
 * @since 2.2
 *
 * @property-read bool $EOF Test if we are at the end of the current ResultSet
 * @property-read array $fields Return the current row of the ResultSet
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
    protected $_native = ''; //for PHP 5.4+, the MySQL native driver is a php.net compile-time default

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
	abstract  public function fieldCount();

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
}
