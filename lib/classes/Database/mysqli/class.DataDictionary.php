<?php
/*
Methods for creating or modifying a MySQL database or its components
Copyright (C) 2017-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS\Database\mysqli;

class DataDictionary extends \CMSMS\Database\DataDictionary
{
    protected function ActualType($meta)
    {
        switch ($meta) {
        case 'C':
        case 'C2': return 'VARCHAR';

        case 'D': return 'DATE';
        case 'DT': return 'DATETIME';
        case 'T': return 'TIME';
        case 'TS': return 'TIMESTAMP';
        case 'L': return 'TINYINT';

        case 'R':
        case 'I4':
        case 'I': return 'INTEGER';
        case 'I1': return 'TINYINT';
        case 'I2': return 'SMALLINT';
        case 'I8': return 'BIGINT';

        case 'F': return 'DOUBLE';
        case 'N': return 'NUMERIC';

        case 'X':
        case 'X2': return 'TEXT';
        case 'LX':
        case 'XL': return 'LONGTEXT';
        case 'MX':
        case 'XM': return 'MEDIUMTEXT';

        case 'B': return 'BLOB';
        case 'LB':
        case 'BL': return 'LONGBLOB';
        case 'MB':
        case 'BM': return 'MEDIUMBLOB';

        default: return $meta;
        }
    }

    protected function MetaType($t, $len = -1, $fieldobj = false)
    {
        // $t can be mixed...
        if (is_object($t)) {
            $fieldobj = $t;
            $t = $fieldobj->type;
            $len = $fieldobj->max_length;
        }

        $len = -1; // mysql max_length is not accurate
        switch (strtoupper($t)) {
        case 'STRING':
        case 'CHAR':
        case 'VARCHAR':
        case 'TINYBLOB':
        case 'TINYTEXT':
        case 'ENUM':
        case 'SET':
            if ($len <= $this->blobSize) {
                return 'C';
            }

        case 'TEXT':
        case 'LONGTEXT':
        case 'MEDIUMTEXT':
            return 'X';

            // php_mysql extension always returns 'blob' even if 'text'
            // so we have to check whether binary...
        case 'IMAGE':
        case 'BLOB':
        case 'LONGBLOB':
        case 'MEDIUMBLOB':
            return !empty($fieldobj->binary) ? 'B' : 'X';

        case 'YEAR':
        case 'DATE': return 'D';

        case 'TIME':
        case 'DATETIME':
        case 'TIMESTAMP': return 'T';

        case 'INT':
        case 'INTEGER':
        case 'BIGINT':
        case 'TINYINT':
        case 'MEDIUMINT':
        case 'SMALLINT':
            if (!empty($fieldobj->primary_key)) {
                return 'R';
            }
            return 'I';

        default:
            static $typeMap = [
                'VARCHAR' => 'C',
                'VARCHAR2' => 'C',
                'CHAR' => 'C',
                'C' => 'C',
                'STRING' => 'C',
                'NCHAR' => 'C',
                'NVARCHAR' => 'C',
                'VARYING' => 'C',
                'BPCHAR' => 'C',
                'CHARACTER' => 'C',

                'LONGCHAR' => 'X',
                'TEXT' => 'X',
                'NTEXT' => 'X',
                'M' => 'X',
                'X' => 'X',
                'CLOB' => 'X',
                'NCLOB' => 'X',
                'LVARCHAR' => 'X',

                'BLOB' => 'B',
                'IMAGE' => 'B',
                'BINARY' => 'B',
                'VARBINARY' => 'B',
                'LONGBINARY' => 'B',
                'B' => 'B',

                'YEAR' => 'D',
                'DATE' => 'D',
                'D' => 'D',

                'TIME' => 'T',
                'TIMESTAMP' => 'T',
                'DATETIME' => 'T',
                'TIMESTAMPTZ' => 'T',
                'T' => 'T',

                'BOOL' => 'L',
                'BOOLEAN' => 'L',
                'BIT' => 'L',
                'L' => 'L',

                'COUNTER' => 'R',
                'R' => 'R',
                'SERIAL' => 'R', // ifx
                'INT IDENTITY' => 'R',

                'INT' => 'I',
                'INT2' => 'I',
                'INT4' => 'I',
                'INT8' => 'I',
                'INTEGER' => 'I',
                'INTEGER UNSIGNED' => 'I',
                'SHORT' => 'I',
                'TINYINT' => 'I',
                'SMALLINT' => 'I',
                'I' => 'I',

                'LONG' => 'N', // interbase is numeric, oci8 is blob
                'BIGINT' => 'N', // this is bigger than PHP 32-bit integers
                'DECIMAL' => 'N',
                'DEC' => 'N',
                'REAL' => 'N',
                'DOUBLE' => 'N',
                'DOUBLE PRECISION' => 'N',
                'SMALLFLOAT' => 'N',
                'FLOAT' => 'N',
                'NUMBER' => 'N',
                'NUM' => 'N',
                'NUMERIC' => 'N',
                'MONEY' => 'N',
                ];

            $t = strtoupper($t);
            $tmap = (isset($typeMap[$t])) ? $typeMap[$t] : 'N';

            return $tmap;
        }
    }

    public function MetaTables()
    {
        $sql = 'SHOW TABLES';
        $list = $this->connection->getCol($sql);
        if ($list) {
            return $list;
        }
    }

    public function MetaColumns($table)
    {
        $table = trim($table);
        if ($table) {
            $sql = 'SHOW COLUMNS FROM '.$this->NameQuote($table);
            $list = $this->connection->getArray($sql);
            if ($list) {
                $out = [];
                foreach ($list as &$row) {
                    $out[] = $row['Field'];
                }
                unset($row);

                return $out;
            }
        }
    }

    protected function _CreateSuffix($fname, $ftype, $fnotnull, $fdefault, $fautoinc, $fconstraint, $funsigned)
    {
        $suffix = '';
        if ($funsigned) {
            $suffix .= ' UNSIGNED';
        }
        if ($fnotnull) {
            $suffix .= ' NOT NULL';
        }
        if (strlen($fdefault)) {
            $suffix .= " DEFAULT $fdefault";
        }
        if ($fautoinc) {
            $suffix .= ' AUTO_INCREMENT';
        }
        if ($fconstraint) {
            $suffix .= ' '.$fconstraint;
        }

        return $suffix;
    }

    public function _ProcessOptions($opts)
    {
        // fixes for old TYPE= stuff in tabopts.
        if ($opts) {
            foreach ($opts as $key => &$val) {
                if (startswith(strtolower($key), 'mysql')) {
                    $val = preg_replace('/TYPE\s?=/i', 'ENGINE=', $val);
                }
            }
        }

        return $opts;
    }

    public function _IndexSQL($idxname, $tabname, $flds, $idxoptions)
    {
        $sql = [];

        if (isset($idxoptions['REPLACE']) || isset($idxoptions['DROP'])) {
//            if (1) { //this->alterTableAddIndex was always true
                $sql[] = parent::alterTable."$tabname DROP INDEX $idxname";
//            } else {
//                $sql[] = sprintf(parent::dropIndex, $idxname, $tabname);
//            }

            if (isset($idxoptions['DROP'])) {
                return $sql;
            }
        }

        if (empty($flds)) {
            return $sql;
        }

        if (isset($idxoptions['FULLTEXT'])) {
            $unique = ' FULLTEXT';
        } elseif (isset($idxoptions['UNIQUE'])) {
            $unique = ' UNIQUE';
        } else {
            $unique = '';
        }

        if (is_array($flds)) {
            $flds = implode(', ', $flds);
        }

//        if (1) { //$this->alterTableAddIndex was always true
            $s = parent::alterTable."$tabname ADD{$unique} INDEX $idxname";
//        } else {
//            $s = "CREATE{$unique} INDEX $idxname ON $tabname";
//        }

        $s .= ' ('.$flds.')';

        if (($opts = $this->get_dbtype_options($idxoptions))) {
            $s .= $opts;
        }

        $sql[] = $s;

        return $sql;
    }

    public function CreateTableSQL($tabname, $flds, $tableoptions = false)
    {
        $str = 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci';
        $dbtype = $this->_dbType();

        // clean up input tableoptions
        if (!$tableoptions) {
            $tableoptions = [$dbtype => $str];
        } elseif (is_string($tableoptions)) {
            $tableoptions = [$dbtype => $tableoptions];
        } elseif (is_array($tableoptions) && !isset($tableoptions[$dbtype]) && isset($tableoptions['mysql'])) {
            $tableoptions[$dbtype] = $tableoptions['mysql'];
        } elseif (is_array($tableoptions) && !isset($tableoptions[$dbtype]) && isset($tableoptions['MYSQL'])) {
            $tableoptions[$dbtype] = $tableoptions['MYSQL'];
        }

        foreach ($tableoptions as $key => &$val) {
            if (strpos($val, 'TYPE') !== false) {
                $val = str_replace(['TYPE=','TYPE ='], ['ENGINE=','ENGINE='], $val);
            }
        }
        if (isset($tableoptions[$dbtype]) && strpos($tableoptions[$dbtype], 'CHARACTER') === false &&
            strpos($tableoptions[$dbtype], 'COLLATE') === false) {
            // if no character set and collate options specified, force UTF8
            $tableoptions[$dbtype] .= ' CHARACTER SET utf8 COLLATE utf8_general_ci';
        }

        return parent::CreateTableSQL($tabname, $flds, $tableoptions);
    }
}
