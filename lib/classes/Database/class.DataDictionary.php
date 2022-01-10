<?php
/*
Methods for creating, modifying a database or its components
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\Database;

use CMSMS\Database\Connection;

/**
 * A class of methods for creating and modifying database tables.
 *
 * This file is based on the DataDictionary base class from the adodb_lite
 * library which was in turn a fork of the ADOdb library in 2004 or thereabouts.
 *
 * Credits and kudos to the authors of those packages.
 *
 * @since 2.99
 */
class DataDictionary
{
    /**
     * SQL sub-string to use for the start of an alter table command
     *
     * @internal
     */
    private const ALTERTABLE = 'ALTER TABLE ';

    /**
     * SQL command template for creating a drop table command.
     *
     * @internal
     */
    private const DROPTABLE = 'DROP TABLE IF EXISTS %s'; // requires MySQL 3.22+

    /**
     * SQL command template for dropping an index.
     *
     * @internal
     */
    private const DROPINDEX = 'DROP INDEX %s ON %s';

    /**
     * SQL sub-string to use (in the alter table command) when adding a column.
     *
     * @internal
     */
    private const ADDCOLUMN = ' ADD COLUMN ';

    /**
     * SQL sub-string to use (in the alter table command) when altering a column.
     *
     * @internal
     */
    private const ALTERCOLUMN = ' MODIFY COLUMN ';

    /**
     * SQL sub-string to use (in the alter table command) when dropping a column.
     *
     * @internal
     */
    private const DROPCOLUMN = ' DROP COLUMN ';

    /**
     * Distinctive drop-column instructor
     *
     * @internal
     */
    private const DROPSIG = '<<DROPPIT!';

    /**
     * The database connection object.
     *
     * @internal
     */
    protected $connection;

    /**
     * Whether the table includes auto-increment field(s)
     *
     * @ignore
     */
    protected $autoIncrement = false;

    /**
     * Array-rows indexer, to support 'adding' returned arrays
     * static properties here >> SingleItem property|ies ?
     * @ignore
     */
    private static $ctr = 1;

    /**
     * Constructor.
     *
     * @param Connection $conn
     */
    public function __construct(Connection $conn)
    {
        $this->connection = $conn;
    }

    /**
     * Return the database interface-type (mysqli).
     *
     * @internal
     *
     * @return string
     */
    protected function dbType()
    {
        return $this->connection->dbType();
    }

    /**
     * Return array of tables in the currently-connected database.
     *
     * @return array possibly empty
     */
    public function MetaTables()
    {
        $list = $this->connection->getCol('SHOW TABLES');
        if ($list) {
            return $list;
        }
        return [];
    }

    /**
     * Return array of column-data (for columns for which the user has
     * some privilege) in the specified table in the currently-connected
     * database.
     *
     * @param string $table The table name
     * @return mixed false if no such table, or array of privileged fieldname(s),
     *  if not empty then each member like
     *  'FieldName'=>1
     *  or if $full is true, like
     *  'FieldName'=>[
     *   'Type'=>e.g.varchar(25),
     *   'Collation'=>string|null,
     *   'Null'=>YES|NO,
     *   'Key'=>PRI|MUL|UNI|empty,
     *   'Default'=>val|NULL|empty,
     *   'Extra'=>various e.g. auto_increment, on update | empty
     *  ]
     */
    public function MetaColumns($table, $full = false)
    {
        $table = trim($table);
        if ($table) {
            $sql = 'SHOW FULL COLUMNS FROM '.$this->NameQuote($table);
            $list = $this->connection->getArray($sql);
            if ($list !== false) {
                $out = [];
                foreach ($list as &$row) {
                    $key = $row['Field'];
                    if ($full) {
                        unset($row['Field'], $row['Privileges'], $row['Comment']);
                        $out[$key] = $row;
                    } else {
                        $out[$key] = 1;
                    }
                }
                unset($row);
                return $out; // possibly empty
            }
        }
        return false; // non-existent table
    }

    /**
     * Return the database-specific column-type of a data-dictionary meta-column type.
     *
     * @internal
     *
     * @param string $meta The datadictionary column type
     * @return string
     */
    protected function ActualType($meta)
    {
        switch ($meta) {
        case 'C':
        case 'C2': return 'varchar';

        case 'D': return 'date';
        case 'DT': return 'datetime';
        case 'T': return 'time';
        case 'TS': return 'timestamp';

        case 'R':
        case 'I4':
        case 'I': return 'integer';
        case 'L':
        case 'TI':
        case 'I1': return 'tinyint';
        case 'SI':
        case 'I2': return 'smallint';
        case 'MI':
        case 'I3': return 'mediumint';
        case 'BI':
        case 'I8': return 'bigint';

        case 'F': return 'double';
        case 'N': return 'numeric';

        case 'X':
        case 'X2': return 'text';
        case 'LX':
        case 'XL': return 'longtext';
        case 'MX':
        case 'XM': return 'mediumtext';
        case 'TX':
        case 'XT': return 'tinytext';

        case 'B': return 'blob';
        case 'LB':
        case 'BL': return 'longblob';
        case 'MB':
        case 'BM': return 'mediumblob';
        case 'TB':
        case 'BT': return 'tinyblob';

        default: return $meta;
        }
    }

    /**
     * Return $name quoted (if necessary) in a manner suitable for the database.
     * Arguably this method is counter-productive. Any correction here will
     * probably not be replicated at runtime, and better to fail during installation.
     * Name content is not checked here for reserved-words.
     * From MySQL documentation:
     * Permitted characters in unquoted identifiers:
     * ASCII: [0-9,a-z,A-Z$_] (basic Latin letters, digits 0-9, dollar, underscore)
     * Extended: U+0080 .. U+FFFF
     * Permitted characters in quoted identifiers include the full Unicode Basic Multilingual Plane (BMP), except U+0000:
     * ASCII: U+0001 .. U+007F
     * Extended: U+0080 .. U+FFFF
     * ASCII NUL (U+0000) and supplementary characters (U+10000 and higher) are not permitted in quoted or unquoted identifiers.
     * Identifiers may begin with a digit but unless quoted may not consist solely of digits.
     * Database, table, and column names cannot end with space characters.
     *
     * @internal
     *
     * @param mixed  $name          The input name
     * @param bool   $allowBrackets Optional flag whether $name with embedded brackets
     *  should be quoted. Default false
     * @return string
     */
    protected function NameQuote($name = null, $allowBrackets = false)
    {
        if (!is_string($name)) {
            return '';
        }

        // if name is already quoted, just trim
        if (preg_match('/^\s*`.+`\s*$/', $name)) {
            return trim($name);
        }

        $name = rtrim($name);
        // if name contains special characters, quote it
        $patn = ($allowBrackets) ? '\w$()\x80-\xff' : '\w$\x80-\xff';
        if (preg_match('/[^'.$patn.']/', $name)) {
            return '`'.$name.'`';
        }
        // if name contains only digits, quote it
        if (preg_match('/^\s*\d+$/', $name)) {
            return '`'.$name.'`';
        }
        return $name;
    }

    /**
     * Quote a table name if appropriate.
     *
     * @internal
     *
     * @param string $name
     * @return string
     */
    protected function TableName($name)
    {
        return $this->NameQuote($name);
    }

    /**
     * Generate a name for a (non-PRIMARY) index.
     *
     * @param mixed $fieldnames string (optionally ','-separated) | string(s)[].
     *  May be empty.
     * @param string $prefix Optional name-prefix. Default 'i_'.
     * @return string
     */
    public function IndexName($fieldnames, $prefix = 'i_')
    {
        static $anon = 0;
        static $truncs = [];

        if ($fieldnames) {
            if (!is_array($fieldnames)) {
                if (strpos($fieldnames, ',') === false) {
                    $fieldnames = [$fieldnames];
                } else {
                    $fieldnames = explode(',', $fieldnames);
                }
            }
            if (count($fieldnames) == 1) {
                return $prefix.strtr($fieldnames[0], ['_'=>'', '-'=>'', ' '=>'']);
            }
            $maxlen = (int)strlen($fieldnames[0].$fieldnames[1]) / 2;
            $fieldnames = array_map(function($name) use ($maxlen, $truncs)
            {
                $s = strtr($name, ['_'=>'', '-'=>'', ' '=>'']);
                $len = strlen($s);
                if ($len <= $maxlen) {
                    return $s;
                }
                $s = substr($s, 0, $maxlen);
                if (isset($truncs[$s])) {
                    $truncs[$s]++;
                    return $s.'_'.$truncs[$s];
                } else {
                    $truncs[$s] = 1;
                    return $s;
                }
            }, $fieldnames);
            return $prefix .implode('_',$fieldnames);
        }
        return $prefix.++$anon;
    }

    /**
     * Generate the SQL to create a database.
     *
     * @param string $dbname
     * @param array  An associative array of database options
     * @return array String suitable for use with the ExecuteSQLArray method
     */
    public function CreateDatabase($dbname, $options = false)
    {
        $options = $this->Options($options);

        $s = 'CREATE DATABASE '.$this->NameQuote($dbname);
        if (isset($options[$this->upperName])) {
            $s .= ' '.$options[$this->upperName];
        }
        return [self::$ctr++ => $s];
    }

    /**
     * Generate the SQL to create an index.
     *
     * @param string $idxname Index name
     * @param string $tabname Table name
     * @param mixed  $flds    Table field(s) to be indexed. Array of strings or
     *  a comma-separated series in one string
     * @param mixed  $idxoptions Optional associative array of options. Default false.
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function CreateIndexSQL($idxname, $tabname, $flds, $idxoptions = false)
    {
        if (strcasecmp($idxname, 'PRIMARY') == 0) {
            $idxname = '`PRIMARY`'; // quote reserved word
        }
        if (!is_array($flds)) {
            $flds = explode(',', $flds);
            $s = true;
        } else {
            $s = false;
        }
        foreach ($flds as $key => $fld) {
            if ($s) $fld = trim($fld);
            // some indices can use partial fields, eg. index first 32 chars of "name" with NAME(32)
            $flds[$key] = $this->NameQuote($fld, true);
        }
        return $this->IndexSQL($this->NameQuote($idxname), $this->TableName($tabname), $flds, $this->Options($idxoptions));
    }

    /**
     * Generate the SQL to drop an index.
     *
     * @param string $idxname Index name
     * @param string $tabname Table name
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function DropIndexSQL($idxname, $tabname)
    {
        if (strcasecmp($idxname, 'PRIMARY') == 0) {
            $idxname = '`PRIMARY`'; // quote reserved word
        }
        return [self::$ctr++ => sprintf(self::DROPINDEX, $this->NameQuote($idxname), $this->TableName($tabname))];
    }

    /**
     * Generate the SQL to add column(s) to a table.
     * @see DataDictionary::CreateTableSQL()
     *
     * @param string $tabname The table name
     * @param string $defn    The column definitions (using DataDictionary meta types)
     *  May include FIRST or 'AFTER colname' to position the field.
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function AddColumnSQL($tabname, $defn)
    {
        $out = [];
        list($lines, $pkeys, $ukeys, $xkeys) = $this->GenFields($defn);
        if ($lines) {
            $v = self::ALTERTABLE.$this->TableName($tabname).self::ADDCOLUMN.reset($lines);
            if ($pkeys) {
                $fname = $this->NameQuote(reset($pkeys));
                // TODO safely PRE-DROP PRIMARY KEY if EXISTS
                $v .= ', ADD PRIMARY KEY ('.$fname.')';
            } elseif ($ukeys) {
                $iname = $this->NameQuote($this->IndexName($ukeys));
                $fname = $this->NameQuote(reset($ukeys));
                // TODO safely PRE-DROP $iname INDEX OF ANY SORT if EXISTS
                $v .= ', ADD UNIQUE INDEX '.$iname.' ('.$fname.')';
            } elseif ($xkeys) {
                $iname = $this->NameQuote($this->IndexName($xkeys));
                $fname = $this->NameQuote(reset($xkeys));
                // TODO safely PRE-DROP $iname INDEX OF ANY SORT if EXISTS
                $v .= ', ADD INDEX '.$iname.' ('.$fname.')';
            }
            $out[self::$ctr++] = $v;
        }
        return $out;
    }

    /**
     * Generate the SQL to change the definition of one column.
     *
     * @param string $tabname The table-name
     * @param string $defn    The column-name and definition for the changed column
     *  May include FIRST or 'AFTER other-colname' to re-order the field.
     */
/*     @param string $tableflds    UNUSED optional complete columns-definition
*  of the revised table
* @param mixed  $tableoptions array | string UNUSED optional table-options
*  for the table creation command. If an array, each member like
*    database type => its options as a string. Default ''.
* @return array Strings suitable for use with the ExecuteSQLArray method
*/
    public function AlterColumnSQL($tabname, $defn) //, $tableflds = '', $tableoptions = '')
    {
        $out = [];
        list($lines, $pkeys, $ukeys, $xkeys) = $this->GenFields($defn);
        if ($lines) {
            $v = self::ALTERTABLE.$this->TableName($tabname).self::ALTERCOLUMN.reset($lines);
            if ($pkeys) {
                $fname = $this->NameQuote(reset($pkeys));
                // TODO safely PRE-DROP PRIMARY KEY if EXISTS
                $v .= ', ADD PRIMARY KEY ('.$fname.')';
            } elseif ($ukeys) {
                $iname = $this->NameQuote($this->IndexName($ukeys));
                $fname = $this->NameQuote(reset($ukeys));
                // TODO safely PRE-DROP $iname INDEX OF ANY SORT if EXISTS
                $v .= ', ADD UNIQUE INDEX '.$iname.' ('.$fname.')';
            } elseif ($xkeys) {
                $iname = $this->NameQuote($this->IndexName($xkeys));
                $fname = $this->NameQuote(reset($xkeys));
                // TODO safely PRE-DROP $iname INDEX OF ANY SORT if EXISTS
                $v .= ', ADD INDEX '.$iname.' ('.$fname.')';
            }
            $out[self::$ctr++] = $v;
        }
        return $out;
    }

    /**
     * Generate the SQL to rename and optionally redefine one column.
     * MySQL identifies this as a 'change', if redefinition is involved.
     *
     * @param string $tabname Table-name
     * @param string $oldname Current column-name
     * @param string $newname New column-name, or full column-definition with
     *  the new name at its start
     * @param string $defn    Renamed-column definition (using DataDictionary meta types).
     * NOTE: for back-compatibility. $defn is optional. Recent server-versions
     *  (e.g. MySQL 8+) will work without one, but for older versions, without
     *  a definition the rename will fail.
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function RenameColumnSQL($tabname, $oldname, $newname, $defn = '')
    {
        $defn = trim($defn);
        if (!$defn) {
            if (($p = strpos($newname, ' ', 1)) != false) {
                $defn = $newname;
                $newname = '';
            }
        } elseif (strpos($defn, $newname) !== 0) {
            $defn = $newname.' '.$defn;
        }
        if ($defn) {
            list($lines, $pkeys, $ukeys, $xkeys) = $this->GenFields($defn); // index(es) ignored, can't change em via rename
            $first = reset($lines);
            list($name, $column_def) = preg_split('/\s+/', $first, 2);
            if (!$newname) {
                $newname = $name;
            }
        } else {
            $column_def = '';
        }

        if ($column_def) {
            return [self::$ctr++ => sprintf('ALTER TABLE %s CHANGE COLUMN %s %s %s',
                $this->TableName($tabname),
                $this->NameQuote($oldname),
                $this->NameQuote($newname),
                $column_def)];
        }
        // recent db-server versions support this
        return [self::$ctr++ => sprintf('ALTER TABLE %s RENAME COLUMN %s TO %s',
            $this->TableName($tabname),
            $this->NameQuote($oldname),
            $this->NameQuote($newname))];
    }

    /**
     * Generate the SQL to drop one or more columns (and all of their indices).
     *
     * @param string $tabname table-name
     * @param mixed  $colname column-name string or comma-separated series of them or array of them
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function DropColumnSQL($tabname, $colname)
    {
        if (!is_array($colname)) {
            $colname = explode(',', $colname);
        }

        $alter = self::ALTERTABLE.$this->TableName($tabname).self::DROPCOLUMN;
        $out = [];
        foreach ($colname as $v) {
            $out[self::$ctr++] = $alter.$this->NameQuote(trim($v));
        }
        return $out;
    }

    /**
     * Generate the SQL to drop one table (and all of its indices).
     *
     * @param string $tabname The table name to drop
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function DropTableSQL($tabname)
    {
        return [self::$ctr++ => sprintf(self::DROPTABLE, $this->TableName($tabname))];
    }

    /**
     * Generate the SQL to rename a table.
     *
     * @param string $tabname Table name
     * @param string $newname New table name
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function RenameTableSQL($tabname, $newname)
    {
        return [self::$ctr++ => sprintf('RENAME TABLE %s TO %s', $this->TableName($tabname), $this->TableName($newname))];
    }

    /**
     * Return the data-dictionary meta type for a database column type.
     *
     * @internal
     *
     * @param mixed $t        string | object Database column type
     * @param int   $len      Optional length of the field. Default -1.
     * @param mixed $fieldobj Optional field object. Default false.
     * @return string
     */
    protected function MetaType($t, $len = -1, $fieldobj = false)
    {
        static $typeMap = null;

        if (is_object($t)) { // never for MySQL ?
            $fieldobj = $t;
            $t = $fieldobj->type;
            $len = $fieldobj->max_length;
        }
        $t = strtolower($t);

        switch ($t) {
        case 'string':
        case 'char':
        case 'varchar':
        case 'enum':
        case 'set':
            if ($len <= $this->blobSize) {
                return 'C';
            }

        case 'text':
        case 'longtext':
        case 'mediumtext':
        case 'tinytext':
            return 'X';

        // php mysql extension always returns 'blob' even if 'text'
        // so we check whether binary...
        case 'image':
        case 'blob':
        case 'longblob':
        case 'mediumblob':
        case 'tinyblob':
            return !empty($fieldobj->binary) ? 'B' : 'X';

        case 'year':
        case 'date': return 'D';

        case 'datetime': return 'DT';

        case 'time':
        case 'timestamp': return 'T';

        case 'int':
        case 'integer':
        case 'bigint':
        case 'tinyint':
        case 'mediumint':
        case 'smallint':
            if (!empty($fieldobj->primary_key)) {
                return 'R'; // primary-key value is never shortened
            }
            switch ($t) {
                case 'int':
                case 'integer':
                    return 'I';
                case 'bigint':
                    return 'I8'; // OR 'BI'
                case 'tinyint':
                    return 'I1'; // OR 'TI'
                case 'smallint':
                    return 'I2'; // OR 'SI'
                case 'mediumint':
                    return 'I3'; // OR 'MI';
            }
        default:
            if (!$typeMap) {
              $typeMap = [
                'varchar' => 'C',
                'varchar2' => 'C',
                'char' => 'C',
                'c' => 'C',
                'string' => 'C',
                'nchar' => 'C',
                'nvarchar' => 'C',
                'varying' => 'C',
                'bpchar' => 'C',
                'character' => 'C',

                'longchar' => 'X',
                'text' => 'X',
                'ntext' => 'X',
                'm' => 'X',
                'x' => 'X',
                'clob' => 'X',
                'nclob' => 'X',
                'lvarchar' => 'X',

                'blob' => 'B',
                'image' => 'B',
                'binary' => 'B',
                'varbinary' => 'B',
                'longbinary' => 'B',
                'tiny' => 'B',
                'b' => 'B',

                'year' => 'D',
                'date' => 'D',
                'd' => 'D',

                'datetime' => 'DT',
                'time' => 'T',
                'timestamp' => 'T',
                'timestamptz' => 'T',
                't' => 'T',

                'bool' => 'L',
                'boolean' => 'L',
                'bit' => 'L',
                'l' => 'L',

                'counter' => 'R',
                'r' => 'R',
                'serial' => 'R', // ifx
                'int identity' => 'R',

                'int' => 'I',
                'integer' => 'I',
                'integer unsigned' => 'I',
                'int2' => 'I2',
                'int4' => 'I',
                'int8' => 'I8',
                'short' => 'I2',
                'tinyint' => 'I1', // 8-bit
                'smallint' => 'I2',
                'mediumint' => 'I3', // 24-bit
                'bigint' => 'I8', // 64-bit
                'i' => 'I',

                'long' => 'N', // interbase is numeric, oci8 is blob
                'decimal' => 'N',
                'dec' => 'N',
                'real' => 'N',
                'double' => 'N',
                'double precision' => 'N',
                'smallfloat' => 'N',
                'float' => 'N',
                'number' => 'N',
                'num' => 'N',
                'numeric' => 'N',
                'n' => 'N',
                'money' => 'N',
              ];
            }

            $tmap = (isset($typeMap[$t])) ? $typeMap[$t] : 'N';
            return $tmap;
        }
    }

    /**
     * Generate the SQL to add, change and/or remove column(s) in bulk.
     *
     * This automatically deals with new columns. Any column to be dropped
     * must be explicitly indicated as such. Columns not present in $defn
     * are ignored.
     *
     * @param string $tablename    Table name
     * @param mixed  $defn         Table field definitions strings array or
     *  comma-separated series of such defn's in one string. Or empty if
     *  the intent is to change only the whole-table parameters (not yet supported)
     * @param mixed  $tableoptions array | string optional table-options
     *  for the table creation command. If an array, each member like
     *    database type => its options as a string. Default ''.
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function ChangeTableSQL($tablename, $defn, $tableoptions = '')
    {
        if ($defn) {
            // check table exists (or at least, some of its fields are manipulable by the current user)
            $cols = $this->MetaColumns($tablename);
            if ($cols === false) {
                return $this->CreateTableSQL($tablename, $defn, $tableoptions);
            } elseif (!$cols) {
// TODO handle case where table exists but user is not permitted to play with it
                return [];
            }
        } else {
            $tabname = $this->TableName($tabname);
            $taboptions = $this->Options($tableoptions);
//TODO return akin to $this->TableSQL($tabname, [], [], [], [], $taboptions); with ALTER, not CREATE
            return [];
        }

        // table exists and some|all fields may be altered, proceed to do so
        $out = [];
        list($lines, $pkeys, $ukeys, $xkeys) = $this->GenFields($defn);
        $alter = self::ALTERTABLE.$this->TableName($tablename);

        foreach ($lines as $id => $v) {
            $ln = strtolower($id);
            if (isset($cols[$ln])) { // TODO caseless isset()
// TODO handle case where field exists but user is not permitted to play with it
                if (($p = strpos($v, self::DROPSIG)) === false) {
                    $out[self::$ctr++] = $alter.self::ALTERCOLUMN.$v;
                } else {
                    $out[self::$ctr++] = $alter.self::DROPCOLUMN.substring($v, 0, $p);
                }
            } elseif (strpos($v, self::DROPSIG) === false) {
                $out[self::$ctr++] = $alter.self::ADDCOLUMN.$v;
            }
        }
        if ($pkeys) {
            // TODO safely PRE-DROP PRIMARY KEY if EXISTS
            $v = $alter.' ADD PRIMARY KEY (`'.implode('`,`', $pkeys).'`)';
            $out[self::$ctr++] = $v;
        }
        if ($ukeys) {
            $iname = $this->NameQuote($this->IndexName($ukeys));
            // TODO safely PRE-DROP $iname INDEX OF ANY SORT if EXISTS
            $v = $alter.' ADD UNIQUE INDEX '.$iname.' (`'.implode('`,`', $ukeys).'`)';
            $out[self::$ctr++] = $v;
        }
        if ($xkeys) {
            $iname = $this->NameQuote($this->IndexName($xkeys));
            // TODO safely PRE-DROP $iname INDEX OF ANY SORT if EXISTS
            $v = $alter.' ADD INDEX '.$iname.' (`'.implode('`,`', $xkeys).'`)';
            $out[self::$ctr++] = $v;
        }
//TODO if () { $v = $alter.' ADD FULLTEXT INDEX ..... $out[self::$ctr++] = $v; }
        return $out;
    }

    /**
     * Generate the SQL to create a table.
     *
     * @param string $tabname Table name
     * @param string $defn    Comma-separated series of field definitions using
     * datadictionary syntax i.e. each definition is of the form:
     * fieldname type columnsize otheroptions
     *
     * The type values are codes that map to real database types as follows:
     * <dl>
     *  <dt>C or C2</dt>
     *  <dd>Varchar, capped to 65535 characters.</dd>
     *  <dt>X or X2</dt>
     *  <dd>Text</dd>
     *  <dt>X(bytesize)</dt>
     *  <dd>Text or MediumText or LongText sufficient for bytesize</dd>
     *  <dt>XM or MX</dt>
     *  <dd>MediumText</dd>
     *  <dt>XL or LX</dt>
     *  <dd>LongText</dd>
     *  <dt>B</dt>
     *  <dd>Blob</dd>
     *  <dt>B(bytesize)</dt>
     *  <dd>Blob or MediumBlob or LongBlob sufficient for bytesize</dd>
     *  <dt>BM or MB</dt>
     *  <dd>MediumBlob</dd>
     *  <dt>BL or LB</dt>
     *  <dd>LongBlob</dd>
     *  <dt>D</dt>
     *  <dd>Date</dd>
     *  <dt>DT</dt>
     *  <dd>DateTime</dd>
     *  <dt>T</dt>
     *  <dd>Time</dd>
     *  <dt>TS</dt>
     *  <dd>Timestamp</dd>
     *  <dt>L</dt>
     *  <dd>TinyInt</dd>
     *  <dt>R / I4 / I</dt>
     *  <dd>Integer</dd>
     *  <dt>I1</dt>
     *  <dd>TinyInt</dd>
     *  <dt>I2</dt>
     *  <dd>SmallInt</dd>
     *  <dt>I4</dt>
     *  <dd>BigInt</dd>
     *  <dt>F</dt>
     *  <dd>Double</dd>
     *  <dt>N</dt>
     *  <dd>Numeric</dd>
     *</dl>
     *
     * The otheroptions values may include the following:
     *<dl>
     *  <dt>AUTO</dt>
     *  <dd>Auto increment. Also sets NOT NULL.</dd>
     *  <dt>AUTOINCREMENT</dt>
     *  <dd>Same as AUTO</dd>
     *  <dt>KEY or PRIMARY</dt>
     *  <dd>Primary key field. Also sets NOT NULL. Compound keys are supported.</dd>
     *  <dt>UKEY or UNIQUE</dt>
     *  <dd>Unique key field. Compound keys are supported with UKEY.</dd>
     *  <dt>FTKEY or FULLTEXT</dt>
     *  <dd>Fulltext key field.</dd>
     *  <dt>XKEY or INDEX</dt>
     *  <dd>Untyped key field. Compound keys are supported with XKEY.</dd>
     *  <dt>DEFAULT</dt>
     *  <dd>The default value.  Character strings are auto-quoted unless the string begins with a space.  i.e: ' SYSDATE '.</dd>
     *  <dt>DEF</dt>
     *  <dd>Same as DEFAULT</dd>
     *  <dt>CONSTRAINTS</dt>
     *  <dd>Additional constraints defined at the end of the field definition.</dd>
     *  <dt>INDEX</dt>
     *  <dd>Create an index on this field. Index-type may be specified as INDEX(type). MySQL types are UNIQUE etc. See also the specific KEY-types, above</dd>
     *  <dt>FOREIGN</dt>
     *  <dd>Create a foreign key on this field. Specify reference-table parameters as FOREIGN(tblname,tblfield).</dd>
     *</dl>
     *
     * @param mixed  $tableoptions array | string Optional table-options
     *  for the table creation command. If an array, each member like
     *    database type => its options as a string. Default ''
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function CreateTableSQL($tabname, $defn, $tableoptions = '')
    {
        list($lines, $pkeys, $ukeys, $xkeys) = $this->GenFields($defn, true);
        if ($lines) {
            $tabname = $this->TableName($tabname);
            $dbtype = $this->dbType();
            // clean up supplied table-options
            if (!$tableoptions) {
                // use default table options
                $tableoptions = [$dbtype =>
                'ENGINE=MyISAM CHARACTER SET utf8mb4']; // default ..._ci collation
            } elseif (is_string($tableoptions)) {
                $tableoptions = [$dbtype => $tableoptions];
            } elseif (is_array($tableoptions) && !isset($tableoptions[$dbtype])) {
                foreach (['mysql', 'MYSQL', 'Maria'] as $key) {
                    if (isset($tableoptions[$key])) {
                        $tableoptions[$dbtype] = $tableoptions[$key];
                        break;
                    }
                }
            }
            foreach ($tableoptions as $key => &$val) {
                if (stripos($val, 'TYPE') !== false) {
                    $val = str_replace(['TYPE=','TYPE ='], ['ENGINE=','ENGINE='], $val);
                }
            }
            unset($val);

            if (isset($tableoptions[$dbtype]) &&
                stripos($tableoptions[$dbtype], 'CHARACTER') === false &&
                stripos($tableoptions[$dbtype], 'COLLATE') === false) {
                // if no character set or collate options specified, force 4-byte UTF8
                $tableoptions[$dbtype] .= ' CHARACTER SET utf8mb4';
            }
            $taboptions = $this->Options($tableoptions);

            return $this->TableSQL($tabname, $lines, $pkeys, $ukeys, $xkeys, $taboptions);
        }
        return [];
    }

    /**
     * Execute the members of the given array of SQL commands.
     *
     * @param array $lines           Array of sql command string(s)
     * @param bool  $continueOnError Optional flag whether to continue after error. Default true
     * @return int 0 immediately after error, 1 if continued after an error, 2 for no errors
     */
    public function ExecuteSQLArray($lines, $continueOnError = true)
    {
        $res = 2;
        foreach ($lines as $line) {
            $rs = $this->connection->execute($line);
            if (!$rs || $this->connection->errno > 0) {
                if (!$continueOnError) {
                    return 0;
                }
                $res = 1;
            }
        }
        return $res;
    }

    /**
     * Parse $defn into an array
     * @since 2.99 imported global function
     * @ignore
     * @param string $defn (needs extra trailing ' ' char after a final quote char)
     * @param string $endstmtchar optional separator character. Default ','
     * @param string $tokenchars  optional series of ? characters. default '_.-'
     * @return array
     */
    protected function ParseDefn($defn, $endstmtchar = ',', $tokenchars = '_.-')
    {
        $pos = 0;
        $stmtno = 0;
        $intoken = false;
        $endquote = false;
        $quoted = false;
        $tokens = [];
        $tokens[] = []; //for $stmtno=0
        $max = strlen($defn);

        while ($pos < $max) {
            $ch = $defn[$pos];
            switch ($ch) {
             case ' ':
             case "\t":
             case "\n":
             case "\r":
                if (!$quoted) {
                    if ($intoken) {
                        $intoken = false;
                        $tokens[$stmtno][] = implode('', $tokarr);
                    }
                    break;
                }
                $tokarr[] = $ch;
                break;
             case '`':
                if ($intoken) {
                    $tokarr[] = $ch;
                }
             case '(':
             case ')':
             case '"':
             case "'":
                if ($intoken) {
                    if (empty($endquote)) {
                        $tokens[$stmtno][] = implode('', $tokarr);
                        if ($ch == '(') {
                            $endquote = ')';
                        } else {
                            $endquote = $ch;
                        }
                        $quoted = true;
                        $intoken = true;
                        $tokarr = [];
                    } elseif ($ch == $endquote) {
                        //check next char, if any
                        if ($pos < $max - 1 && $defn[$pos + 1] == $endquote) {
                            //a sequential endquote
                            ++$pos;
                            $tokarr[] = $endquote;
                        } else {
                            $quoted = false;
                            $intoken = false;
                            $tokens[$stmtno][] = implode('', $tokarr);
                            $endquote = '';
                        }
                    } else {
                        $tokarr[] = $ch;
                    }
                } else {
                    if ($ch == '(') {
                        $endquote = ')';
                    } else {
                        $endquote = $ch;
                    }
                    $quoted = true;
                    $intoken = true;
                    $tokarr = [];
                    if ($ch == '`') {
                        $tokarr[] = '`';
                    }
                }
                break;
             default:
                if (!$intoken) {
                    if ($ch == $endstmtchar) {
                        ++$stmtno;
                        $tokens[$stmtno] = [];
                        break;
                    }
                    $intoken = true;
                    $quoted = false;
                    $endquote = false;
                    $tokarr = [];
                }
                if ($quoted) {
                    $tokarr[] = $ch;
                } elseif (ctype_alnum($ch) || strpos($tokenchars, $ch) !== false) {
                    $tokarr[] = $ch;
                } else {
                    if ($ch == $endstmtchar) {
                        $tokens[$stmtno][] = implode('', $tokarr);
                        ++$stmtno;
                        $tokens[$stmtno] = [];
                        $intoken = false;
                        $tokarr = [];
                        break;
                    }
                    $tokens[$stmtno][] = implode('', $tokarr);
                    $tokens[$stmtno][] = $ch;
                    $intoken = false;
                }
            } //switch
            ++$pos;
        }
        if ($intoken) {
            $tokens[$stmtno][] = implode('', $tokarr);
        }
        return $tokens;
    }

    /**
     * Return array matching the one supplied, but with all keys upper-case
     * @since 2.99 imported global function
     * @ignore
     * @param array $an_array
     * @return array
     */
    protected function UpperKeys($an_array)
    {
        if (is_array($an_array)) {
            $new_array = [];
            foreach ($an_array as $key => $value) {
                $new_array[strtoupper($key)] = $value;
            }

            return $new_array;
        }
        return $an_array;
    }

    /**
     * Parse data-dictionary format definition into MySQL-format field definition(s).
     *
     * @internal
     * @param mixed $defn array of strings or comma-separated series in one string, or empty
     * @param bool  $widespacing optional flag whether to pad the field-name, default false
     * @return 4-member array
     *  [0] = assoc. array or empty. Each member ucase-fieldname=>field-defn, maybe empty
     *  [1] = array of primary-key-field name(s), maybe empty
     *  [2] = array of unique-key-field name(s), maybe empty
     *  [3] = array of untyped-key-field name(s), maybe empty
     */
    protected function GenFields($defn, $widespacing = false)
    {
        if (!$defn) {
            return [[], [], [], []];
        }

        if (is_string($defn)) {
            $flds = [];
            $parts = $this->ParseDefn($defn);
            $hasparam = false;
            foreach ($parts as $f0) {
                if ($f0) {
                    $f1 = [];
                    foreach ($f0 as $token) {
                        switch (strtoupper($token)) {
                        case 'CONSTRAINT':
                        case 'DEFAULT':
                            $hasparam = $token;
                            break;
                        default:
                            if ($hasparam) {
                                $f1[$hasparam] = $token;
                            } else {
                                $f1[] = $token;
                            }
                            $hasparam = false;
                            break;
                        }
                    }
                    $flds[] = $f1;
                }
            }
        } else {
            $flds = $defn;
        }

        $this->autoIncrement = false;
        $lines = [];
        $pkeys = [];
        $ukeys = [];
        $xkeys = [];

        foreach ($flds as $fld) {
            $fld = $this->UpperKeys($fld);
            $fafter = false;
            $fautoinc = false;
            $fchars = false;
            $fcoll = false;
            $fconstraint = false;
            $fdefault = false;
            $fdefdate = false;
            $fdefts = false;
            $fdrop = false;
            $fforeign = false;
            $findex = false; // any of several index-types
            $fname = false;
            $fnoquote = false;
            $fnot = false;
            $fnotnull = false;
            $fprec = false;
            $fprimary = false;
            $fsize = false;
            $ftextkey = false;
            $ftype = false;
            $funikey = false;
            $funsigned = false;
            $fxtrakey = false;

            // PARSE ATTRIBUTES
            foreach ($fld as $i => $v) {
                if ($i == 2 && is_numeric($v)) {
                    $attr = 'SIZE';
                } elseif (is_numeric($i) && $i > 1 && !is_numeric($v)) {
                    $attr = strtoupper($v);
                } else {
                    $attr = $i;
                }
                switch ($attr) {
                    case '0':
                    case 'NAME':
                        $fname = $v;
                        break;
                    case '1':
                    case 'TYPE':
                        $ty = $v;
                        $ftype = $this->ActualType(strtoupper($v));
                        break;
                    case 'SIZE':
                        $at = strpos($v, '.');
                        if ($at === false) {
                            $at = strpos($v, ',');
                        }
                        if ($at === false) {
                            $fsize = $v;
                        } else {
                            $fsize = substr($v, 0, $at);
                            $fprec = substr($v, $at + 1);
                        }
                        break;
                    case 'UNSIGNED':
                        $funsigned = true;
                        break;
                    case 'AUTO':
                    case 'AUTOINC':
                    case 'AUTOINCREMENT':
                        $fautoinc = true;
                        $fnotnull = true;
                        break;
                    case 'KEY':
                    case 'PRIMARY':
                        $fprimary = $v;
                        $fnotnull = true;
                        break;
                    case 'XKEY':
                        $fxtrakey = $v;
                        break;
                    case 'UKEY':
                        $funikey = $v;
                        break;
                    case 'DEF':
                    case 'DEFAULT':
                        $fdefault = $v;
                        break;
                    case 'NOT':
                        $fnot = true;
                        break;
                    case 'NULL':
                        if ($fnot) {
                             $fnot = false ;
                             $fnotnull = true;
                        } else {
                             $fnotnull = false; //probably useless
                        }
                        break;
                    case 'NOTNULL':
                    case 'NOT NULL':
                        $fnotnull = $v;
                        break;
                    case 'NOQUOTE':
                        $fnoquote = $v;
                        break;
                    case 'DEFDATE':
                        $fdefdate = $v;
                        break;
                    case 'DEFTIMESTAMP':
                        $fdefts = $v;
                        break;
                    case 'CONSTRAINT':
                        $fconstraint = $v;
                        break;
                    case 'FTKEY':
                        if (empty($ftype) || $ftype == 'varchar' || substr_compare($ftype,'text',-4,4,true) == 0) {
                            $ftextkey = true;
                        }
                        break;
                    case 'FULLTEXT':
                        if (!(empty($ftype) || $ftype == 'varchar' || substr_compare($ftype,'text',-4,4,true) == 0)) {
                            break;
                        }
                        // no break here
                    case 'INDEX':
                    case 'UNIQUE':
                        $findex = $attr;  //only 1 type allowed, last-used prevails
                        break;
                    case 'CHARACTER':
                        $z = true;
                        break;
                    case 'SET':
                        if (empty($z)) {
                            break;
                        }
                        $z = false;
                        // no break here
                    case 'CHARSET':
                        $fchars = $fld[$i+1] ?? false;
                        if ($fchars !== false) {
                            $fld[$i+1] = '';
                        }
                        break;
                    case 'COLLATE':
                    case 'COLLATION':
                        $fcoll = $fld[$i+1] ?? false;
                        if ($fcoll !== false) {
                            $fld[$i+1] = '';
                        }
                        break;
                    case 'AFTER':
                        $fafter = $fld[$i+1] ?? false;
                        if ($fafter !== false) {
                            $fld[$i+1] = '';
                        }
                        break;
                    case 'FOREIGN':
                        $fforeign = $v;
                        break;
                    case 'DROP':
                        $fdrop = true;
                        $ftype = 'DROP'; // don't abort prematurely
                        break;
                    default:
                        continue 2; // don't clear unprocessed field
                }
                $fld[$i] = '';
            }

            // VALIDATE FIELD INFO
            if (!$fname) {
                die('failed');
            }

            if (!$ftype) {
                return [[], [], [], []];
            }

            $fid = strtoupper(preg_replace('/^`(.+)`$/', '$1', $fname));
            $fname = $this->NameQuote($fname);

            if ($fdrop) {
                $lines[$fid] = $fname.self::DROPSIG; // signal to upstream
                continue;
            }

            $ftype = $this->GetSize($ftype, $ty, $fsize, $fprec);

            switch ($ty) {
                case 'B':
                case 'LB':
                case 'BL':
                case 'MB':
                case 'BM':
                case 'TB':
                case 'BT':
                    $fchars = false; //BLOBs have no charset or collation
                    $fcoll = false;
                // no break here
                case 'X':
                case 'X2':
                case 'LX':
                case 'XL':
                case 'MX':
                case 'XM':
                case 'TX':
                case 'XT':
                    $fdefault = false; //TEXT and BLOB fields have no DEFAULT value
                    $fnotnull = false;
            }

            if ($fprimary) {
                $pkeys[] = $fname;
            }
            if ($funikey) {
                $ukeys[] = $fname;
            }
            if ($fxtrakey) {
                $xkeys[] = $fname;
            }

            // CONSTRUCT FIELD SQL
            if ($fdefts) {
                $fdefault = 'TIMESTAMP';
            } elseif ($fdefdate) {
                $fdefault = 'DATE';
            } elseif ($fdefault !== false && !$fnoquote) {
                if ($ty == 'C' || $ty[0] == 'X' ||
                    ($fdefault !== '' && $fdefault[0] != "'" && !is_numeric($fdefault))) {
                    $len = strlen($fdefault);
                    if ($len > 1 && $fdefault[0] == ' ' && $fdefault[$len - 1] == ' ') {
                        $fdefault = trim($fdefault);
                    } else {
                        switch (strtolower($fdefault)) {
                            case 'null':
                            case 'current_timestamp':
                            case 'local_timestamp':
                            case 'localtimestamp':
                            case 'localtime':
                            case 'now':
                                $fdefault = strtoupper($fdefault);
                                break;
                            case 'true':
                                $fdefault = 1;
                                break;
                            case 'false':
                                $fdefault = 0;
                                break;
                            default:
                                $fdefault = $this->connection->qStr($fdefault);
                                break;
                        }
                    }
                }
            }

            $suffix = $this->CreateSuffix($fnotnull, $fdefault, $fautoinc, $fchars, $fcoll, $fconstraint, $funsigned);

            $s = implode(' ', array_filter($fld));
            if ($s) {
                $suffix .= ' '.$s;
            }

            $s = ($widespacing) ? str_pad($fname, 24) : $fname;
            $s .= ' '.$ftype.$suffix;

            if ($fafter) {
                $s .= ' AFTER '.$this->NameQuote($fafter);
            }

            if ($ftextkey) {
                $iname = $this->NameQuote($this->IndexName($fname));
                $s .= ", FULLTEXT INDEX $iname (`$fname`)";
            }

            if ($findex) {
                if (!$ftextkey || $findex != 'FULLTEXT') {
                    $iname = $this->NameQuote($this->IndexName($fname));
                    $fname = $this->NameQuote($fname);
                    $s .= ", $findex $iname ($fname)";
                }
            }

            if ($fforeign) {
                $at = array_search($fforeign, $fld);
                if ($at !== false && isset($fld[++$at])) {
                    list($table, $field) = explode(',', trim($fld[$at]), 2);
                    if ($table && $field) {
                        $s .= ", FOREIGN KEY($fname) REFERENCES $table($field)";
                    }
                }
            }

            $lines[$fid] = $s;

            if ($fautoinc) {
                $this->autoIncrement = true;
            }
        } // foreach $flds
        return [$lines, $pkeys, $ukeys, $xkeys];
    }

    /**
     * Generate the (lowercase) size-part of the field defn.
     *
     * @ignore
     * @internal
     *
     * @param string $ftype
     * @param string $ty type-indicator I,C,B,X ...
     * @param int $fsize field byte-size
     * @param mixed $fprec string | false Optional 'fraction' component of field-size
     * @return string lowercase $ftype or variant of or substitute for that
     */
    protected function GetSize($ftype, $ty, $fsize, $fprec = '')
    {
        $ftype = strtolower($ftype);
        if ($fsize) {
            if ($ty == 'B' || $ty == 'X') {
                if ($fsize <= 256) {
                    return 'tiny'.$ftype;
                } elseif ($fsize <= 65536) {
                    return $ftype;
                } elseif ($fsize <= 1 << 24) {
                    return 'medium'.$ftype;
                } else {
                    return 'long'.$ftype;
                }
            } elseif (strpos($ftype, '(') === false) {
                $ftype .= '('.$fsize;
                if ($fprec || is_numeric($fprec)) {
                    $ftype .= ','.$fprec;
                }
                $ftype .= ')';
            }
        }
        return $ftype;
    }

    /**
     * Create a suffix.
     *
     * @internal
     * @ignore
     *
     * @return string
     */
    protected function CreateSuffix($fnotnull, $fdefault, $fautoinc, $fchars, $fcoll, $fconstraint, $funsigned)
    {
        $suffix = '';
        if ($fchars) {
            $suffix .= " CHARACTER SET $fchars";
        }
        if ($fcoll) {
            $suffix .= " COLLATE $fcoll";
        }
        if ($funsigned) {
            $suffix .= ' UNSIGNED';
        }
        if ($fnotnull) {
            $suffix .= ' NOT NULL';
        }
        if ($fdefault !== false) { // anything else falsy e.g. '', 0, '0' is valid
            if ($fdefault === '') { $fdefault = "''"; }
            $suffix .= " DEFAULT $fdefault";
        }
        if ($fautoinc) {
            $suffix .= ' AUTO_INCREMENT';
        }
        if ($fconstraint) {
            $suffix .= " $fconstraint";
        }
        return $suffix;
    }

    /**
     * Generate SQL to create an index.
     *
     * @internal
     *
     * @param mixed $flds array of strings or comma-separated series in one string, or falsy
     * @return array
     */
    protected function IndexSQL($idxname, $tabname, $flds, $idxoptions)
    {
        $out = [];

        if (isset($idxoptions['REPLACE']) || isset($idxoptions['DROP'])) {
//            if (1) { //this->alterTableAddIndex was always true
                $out[self::$ctr++] = self::ALTERTABLE."$tabname DROP INDEX $idxname";
//            } else {
//                $out[self::$ctr++] = sprintf(self::DROPINDEX, $idxname, $tabname);
//            }

            if (isset($idxoptions['DROP'])) {
                return $out;
            }
        }

        if (empty($flds)) {
            return $out;
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
            $s = self::ALTERTABLE."$tabname ADD{$unique} INDEX $idxname";
//        } else {
//            $s = "CREATE{$unique} INDEX $idxname ON $tabname";
//        }

        $s .= ' ('.$flds.')';

        if (($opts = $this->get_dbtype_options($idxoptions))) {
            $s .= $opts;
        }

        $out[self::$ctr++] = $s;
        return $out;
    }

    /**
     * Drop the auto increment column on a table.
     *
     * @deprecated, does nothing
     * @internal
     * @ignore
     *
     * @return false always
     */
    protected function DropAutoIncrement($tabname)
    {
        return false;
    }

    /**
     * Get a list of database type-specific options for a command.
     *
     * @internal
     * @ignore
     *
     * @return mixed string | null
     */
    protected function get_dbtype_options($opts, $suffix = null)
    {
        $dbtype = $this->dbType();
        $list = [$dbtype.$suffix, strtoupper($dbtype).$suffix, strtolower($dbtype).$suffix];

        foreach ($list as $one) {
            if (isset($opts[$one]) && is_string($opts[$one]) && strlen($opts[$one])) {
                return $opts[$one];
            }
        }
    }

    /**
     * Build string for generating table. Backend for CreateTableSQL()
     * @internal
     *
     * @param string $tabname Table name
     * @param array $lines Field definition(s) from field-parsing
     * @param array $pkeys Primary-key-field name(s) from field-parsing. Maybe empty.
     * @param array $ukeys Unique-key-field name(s) from field-parsing. Maybe empty.
     * @param array $xkeys Untyped-key-field name(s) from field-parsing. Maybe empty.
     * @param array $tableoptions Whole-table definitions
     * @return array SQL string(s)
     */
    protected function TableSQL($tabname, $lines, $pkeys, $ukeys, $xkeys, $tableoptions)
    {
        $out = [];

        if (isset($tableoptions['REPLACE']) || isset($tableoptions['DROP'])) {
            $out[self::$ctr++] = sprintf(self::DROPTABLE, $tabname);
            if ($this->autoIncrement) {
                $sInc = $this->DropAutoIncrement($tabname); // always false ATM
                if ($sInc) {
                    $out[self::$ctr++] = $sInc;
                }
            }
            if (isset($tableoptions['DROP'])) {
                return $out;
            }
        }
        $s = "CREATE TABLE $tabname (\n";
        $s .= implode(",\n", $lines);
        if ($pkeys) {
            $s .= ",\nPRIMARY KEY (`".implode('`,`', $pkeys).'`)';
        }
        if ($ukeys) {
            $iname = $this->NameQuote($this->IndexName($ukeys));
            $s .= ",\nUNIQUE INDEX $iname (`".implode('`,`', $ukeys).'`)';
        }
        if ($xkeys) {
            $iname = $this->NameQuote($this->IndexName($xkeys));
            $s .= ",\nINDEX $iname (`".implode('`,`', $xkeys).'`)';
        }
        if (isset($tableoptions['CONSTRAINTS'])) {
            $s .= "\n".$tableoptions['CONSTRAINTS'];
        }

        $str = $this->get_dbtype_options($tableoptions, '_CONSTRAINTS');
        if ($str) {
            $s .= "\n".$str;
        }

        $s .= "\n)";
        $str = $this->get_dbtype_options($tableoptions);
        if ($str) {
            $s .= ' '.$str;
        }
        $out[self::$ctr++] = $s;
        return $out;
    }

    /**
     * Sanitize options.
     *
     * @internal
     *
     * @param array $opts
     * @return array
     */
    protected function ProcessOptions($opts)
    {
        // fixes for old TYPE= stuff in tabopts.
        if ($opts) {
            foreach ($opts as $key => &$val) {
                if (strncasecmp($key, 'mysql', 5) == 0) {
                    $val = preg_replace('/TYPE\s?=/i', 'ENGINE=', $val);
                }
            }
            unset($val);
        }
        return $opts;
    }

    /**
     * Convert options into a format usable by the system.
     *
     * @internal
     *
     * @return array, maybe empty
     */
    protected function Options($opts)
    {
        $opts = $this->ProcessOptions($opts);
        if (!is_array($opts)) {
            return [];
        }
        $newopts = [];
        foreach ($opts as $k => $v) {
            if (is_numeric($k)) {
                $newopts[strtoupper($v)] = $v;
            } else {
                $newopts[strtoupper($k)] = $v;
            }
        }
        return $newopts;
    }
}
