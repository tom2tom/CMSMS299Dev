<?php
/*
Class for ...
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\Log;

use CMSMS\AppSingle;
use CMSMS\Log\logfilter;
use CMSMS\Log\logrecord;
use CMSMS\Log\dbquery;
use const CMS_DB_PREFIX;

class dbstorage
{
    const TABLENAME = CMS_DB_PREFIX.'adminlog';

    public function save(logrecord $rec)
    {
        $db = AppSingle::Db();
        $sql = 'INSERT INTO '.self::TABLENAME.' (timestamp, severity, user_id, username, item_id, subject, message, ip_addr) VALUES (?,?,?,?,?,?,?,?)';
        $db->Execute($sql, [$rec->timestamp, $rec->severity, $rec->user_id, $rec->username, $rec->item_id, $rec->subject, $rec->message, $rec->ip_addr]);
    }

    public function query(logfilter $filter)
    {
        return new dbquery($filter);
    }

    public function clear()
    {
        $db = AppSingle::Db();
        $sql = 'TRUNCATE '.self::TABLENAME;
        $db->Execute($sql);
    }

    public function clear_older_than(int $time)
    {
        $db = AppSingle::Db();
        $sql = 'DELETE FROM '.self::TABLENAME.' WHERE timestamp < ?';
        $db->Execute( $sql, [$time]);
    }
}
