<?php
/*
This file is part of CMS Made Simple module: AdminLog
Copyright (C) 2017-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file AdminLog.module.php
More info at http://dev.cmsmadesimple.org/projects/adminlog
*/
namespace AdminLog;

use AdminLog;
use AdminLog\event;
use const CMS_DB_PREFIX;

class storage
{
    const TABLENAME = 'mod_adminlog';

    private $_mod;

    public function __construct(AdminLog $mod)
    {
        $this->_mod = $mod;
    }

    public function clear()
    {
        $db = $this->_mod->GetDb();
        $sql = 'TRUNCATE '.CMS_DB_PREFIX.self::TABLENAME;
        $db->Execute( $sql );
    }

    public function save( event $ev )
    {
        $db = $this->_mod->GetDb();
        $sql = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (timestamp, severity, uid, ip_addr, username, subject, msg, item_id) VALUES (?,?,?,?,?,?,?,?)';
        $db->Execute( $sql, [ $ev->timestamp, $ev->severity, $ev->uid, $ev->ip_addr, $ev->username, $ev->subject, $ev->msg, $ev->item_id ] );
    }

    public function clear_older_than( $time )
    {
        $db = $this->_mod->GetDb();
        $sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE timestamp < ?';
        $db->Execute( $sql, [ (int)$time ] );
    }
}
