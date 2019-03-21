<?php

namespace AdminLog;

use AdminLog\filter;
use AdminLog\storage;
use CmsDbQueryBase;
use CMSMS\Database\Connection;
use LogicException;

class resultset extends CmsDbQueryBase
{
    private $_db;

    public function __construct( Connection $db, filter $filter )
    {
        $this->_db = $db;
        $this->_args = $filter;
        $this->_offset = $filter->offset;
        $this->_limit = $filter->limit;
    }

    public function execute()
    {
        if( $this->_rs ) return;
        $filter = $this->_args;

        $sql = 'SELECT * FROM '.CMS_DB_PREFIX.storage::TABLENAME;
        $where = $parms = [];
        $severity = $filter->severity;
        if( !is_null($severity) && $severity > -1 ) {
            $where[] = 'severity >= ?';
            $parms[] = $severity;
        }
        if( ($val = $filter->username) ) {
            $where[] = 'username = ?';
            $parms[] = $val;
        }
        if( ($val = $filter->msg) ) {
            $where[] = 'msg LIKE ?';
            $parms[] = '%'.$val.'%';
        }
        if( ($val = $filter->subject) ) {
            $where[] = 'subject LIKE ?';
            $parms[] = '%'.$val.'%';
        }
        if( $where ) {
            $sql .= ' WHERE '.implode(' AND ',$where);
        }
        $sql .= ' ORDER BY timestamp DESC';

        $this->_rs = $this->_db->SelectLimit( $sql, $this->_limit, $this->_offset, $parms );
        if( !$this->_rs || $this->_rs->errno !== 0 ) $this->_totalmatchingrows = 0;
        else $this->_totalmatchingrows = $this->_db->GetOne( 'SELECT FOUND_ROWS()' );
    }

    public function &GetObject()
    {
        $row = $this->fields;
        return $row;
    }

    public function GetMatches()
    {
        $this->execute();
        if( !$this->_rs ) throw new LogicException('Invalid query generated');

        $out = [];
        while( !$this->EOF() ) {
            $out[] = $this->GetObject();
            $this->MoveNext();
        }
        return $out;
    }
}
