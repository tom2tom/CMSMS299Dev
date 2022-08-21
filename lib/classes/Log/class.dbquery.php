<?php
/*
Class ...
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\Log;

use CMSMS\DbQueryBase;
use CMSMS\Log\dbstorage;
use CMSMS\Log\logfilter;
use CMSMS\Lone;
use LogicException;

class dbquery extends DbQueryBase
{
    public function __construct(logfilter $filter)
    {
        $this->_args = $filter;
        $this->_offset = $filter->offset;
        $this->_limit = $filter->limit;
    }

    public function execute()
    {
        if ($this->_rs) return;
        $filter = $this->_args;
        $db = Lone::get('Db');
        $sql = 'SELECT * FROM '.dbstorage::TABLENAME;
        $wheres = []; $parms = [];
        $severity = $filter->severity;

        if (!is_null($severity) && $severity > -1) {
            $wheres[] = 'severity >= ?';
            $parms[] = $severity;
        }
        if (($val = $filter->username)) {
            $wheres[] = 'username = ?';
            $parms[] = $val;
        }
        if (($val = $filter->message)) {
            $wheres[] = 'message LIKE ?';
            $parms[] = '%' . $db->escStr($val) . '%';
        }
        if (($val = $filter->subject)) {
            $wheres[] = 'subject LIKE ?';
            $parms[] = '%' . $db->escStr($val) . '%';
        }
        if ($wheres) {
            $sql .= ' WHERE '.implode(' AND ', $wheres);
        }
        $sql .= ' ORDER BY timestamp DESC';
        $this->_rs = $db->SelectLimit($sql, $this->_limit, $this->_offset, $parms);
        if ($this->_rs && $this->_rs->errno == 0) {
            $this->_totalmatchingrows = $this->_rs->recordCount();
        } else {
            $this->_totalmatchingrows = 0;
        }
    }

    public function GetObject()
    {
        return $this->fields;
    }

    public function GetMatches()
    {
        $this->execute();
        if (!$this->_rs) {
            throw new LogicException('Invalid query generated');
        }

        $out = [];
        while (!$this->EOF()) {
            $out[] = $this->GetObject();
            $this->MoveNext();
        }
        return $out;
    }
}
