<?php
/*
Methods for creating, modifying a database or its components
Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
 * library which was in turn a fork of the adodb library in 2004 or thereabouts.
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
     * static properties here >> StaticProperties class ?
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
     * Return array of tables in the currently connected database.
     *
     * @return array
     */
    public function MetaTables()
    {
        $sql = 'SHOW TABLES';
        $list = $this->connection->getCol($sql);
        if ($list) {
            return $list;
        }
    }

    /**
     * Return list of columns in a table in the currently connected database.
     *
     * @param string $table The table name
     * @return array of strings
     */
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

    /**
     * Return $name quoted (if necessary) in a manner suitable for the database.
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
            return false;
        }

        $name = trim($name);

        // if name is already quoted, do nothing
        if (preg_match('/^`.+`$/', $name)) {
            return $name;
        }
        // if name contains special characters, quote it
        $patn = ($allowBrackets) ? '\w\(\)' : '\w';
        if (preg_match('/[^'.$patn.']/', $name)) {
            return '`'.$name.'`';
        }
       // TODO if name is a reserved word, quote it

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
     *
     * @param string $tabname The table name
     * @param string $defn    The column definitions (using DataDictionary meta types)
     *  May include FIRST or 'AFTER colname' to position the field.
     * @return array Strings suitable for use with the ExecuteSQLArray method
     *
     * @see DataDictionary::CreateTableSQL()
     */
    public function AddColumnSQL($tabname, $defn)
    {
        $sql = [];
        list($lines, $pkey) = $this->GenFields($defn);
        if ($lines) {
            $v = self::ALTERTABLE.$this->TableName($tabname).self::ADDCOLUMN.reset($lines);
            if ($pkey) {
                $v .= ', ADD PRIMARY KEY ('.reset($pkey).')';
            }
            $sql[self::$ctr++] = $v;
        }

        return $sql;
    }

    /**
     * Generate the SQL to change the definition of one column.
     *
     * @param string $tabname The table-name
     * @param string $defn    The column-name and definition for the changed column
     *  May include FIRST or 'AFTER other-colname' to re-order the field.
     * @param string $tableflds    UNUSED optional complete columns-definition of the revised table
     * @param array/string $tableoptions UNUSED optional options for the revised table see CreateTableSQL, default ''
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function AlterColumnSQL($tabname, $defn, $tableflds = '', $tableoptions = '')
    {
        $sql = [];
        list($lines, $pkey) = $this->GenFields($defn);
        if ($lines) {
            $v = self::ALTERTABLE.$this->TableName($tabname).self::ALTERCOLUMN.reset($lines);
            if ($pkey) {
                $v .= ', ADD PRIMARY KEY ('.reset($pkey).')';
            }
            $sql[self::$ctr++] = $v;
        }

        return $sql;
    }

    /**
     * Generate the SQL to rename one column.
     *
     * @param string $tabname Table-name
     * @param string $oldname Current column-name
     * @param string $newname New column-name, or full column-definition with
     *  the new name at its start
     * @param string $defn    Renamed-column definition (using DataDictionary meta types).
     *
     * NOTE: for back-compatibility a definition is optional, and recent
     *  server-versions will work without one, but for older versions, without a
     *  definition the rename will fail.
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
            list($lines, $pkey) = $this->GenFields($defn); // primary-key ignored, can't change that via rename
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
     * Generate the SQL to drop one or more columns.
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
        $sql = [];
        foreach ($colname as $v) {
            $sql[self::$ctr++] = $alter.$this->NameQuote(trim($v));
        }

        return $sql;
    }

    /**
     * Generate the SQL to drop one table, and all of its indices.
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
     * @param string $t        Database column type
     * @param int    $len      Optional length of the field. Default -1. UNUSED
     * @param mixed  $fieldobj Optional field object. Default false.
     * @return string
     */
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

        // php mysql extension always returns 'blob' even if 'text'
        // so we check whether binary...
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

    /**
     * Generate the SQL to add, drop or change column(s).
     *
     * This function changes/adds new fields to your table. You don't
     * have to know if the col is new or not. It will check on its own.
     *
     * @param string $tablename    Table name
     * @param mixed  $defn         Table field definitions strings array or
     *  comma-separated series of in one string
     * @param mixed  $tableoptions Table options. Default false.
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function ChangeTableSQL($tablename, $defn, $tableoptions = false)
    {
        // check table exists
        $cols = $this->MetaColumns($tablename);

        if (empty($cols)) {
            return $this->CreateTableSQL($tablename, $defn, $tableoptions);
        }

        if (is_array($defn)) {
            // walk through the update fields, comparing existing fields to fields to update.
            // if the metatype and size are exactly the same, ignore - by Mark Newham
            $holdflds = [];
            foreach ($defn as $k => $v) {
                if (isset($cols[$k]) && is_object($cols[$k])) {
                    $c = $cols[$k];
                    $ml = $c->max_length;
                    $mt = &$this->MetaType($c->type, $ml); //$ml unused = ok?
                    if ($ml == -1) {
                        $ml = '';
                    }
                    if ($mt == 'X') {
                        $ml = $v['SIZE'];
                    }
                    if (($mt != $v['TYPE']) || $ml != $v['SIZE']) {
                        $holdflds[$k] = $v;
                    }
                } else {
                    $holdflds[$k] = $v;
                }
            }
            $defn = $holdflds;
        }

        // already exists, alter table instead
        list($lines, $pkey) = $this->GenFields($defn);
        $alter = self::ALTERTABLE.$this->TableName($tablename);
        $sql = [];
        $fixedsizetypes = ['CLOB', 'BLOB', 'TEXT', 'DATE', 'TIME'];

        foreach ($lines as $id => $v) {
            if (isset($cols[$id]) && is_object($cols[$id])) {
                // we are trying to change the field-size, but maybe not valid
                $parts = $this->ParseDefn($v);
                if ($parts && in_array(strtoupper(substr($parts[0][1], 0, 4)), $fixedsizetypes)) { //TODO BLOB,TEXT are valid
                    continue;
                }
                $sql[self::$ctr++] = $alter.self::ALTERCOLUMN.$v;
            } else {
                $sql[self::$ctr++] = $alter.self::ADDCOLUMN.$v;
            }
        }
        if ($pkey) {
            $v = $alter.' ADD PRIMARY KEY(';
            $v .= implode(', ', $pkey).')';
            $sql[self::$ctr++] = $v;
        }
        return $sql;
    }

    /**
     * Generate the SQL to create a table.
     *
     * @param string $tabname Table name
     * @param string $defn    Comma-separated series of field definitions using datadictionary syntax
     * i.e. each definition is of the form: fieldname type columnsize otheroptions
     *
     * The type values are codes that map to real database types as follows:
     * <dl>
     *  <dt>C or C2</dt>
     *  <dd>Varchar, capped to 255 characters.</dd>
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
     *  <dd>Auto increment. Also sets NOTNULL.</dd>
     *  <dt>AUTOINCREMENT</dt>
     *  <dd>Same as AUTO</dd>
     *  <dt>KEY</dt>
     *  <dd>Primary key field.  Also sets NOTNULL. Compound keys are supported.</dd>
     *  <dt>PRIMARY</dt>
     *  <dd>Same as KEY</dd>
     *  <dt>DEFAULT</dt>
     *  <dd>The default value.  Character strings are auto-quoted unless the string begins with a space.  i.e: ' SYSDATE '.</dd>
     *  <dt>DEF</dt>
     *  <dd>Same as DEFAULT</dd>
     *  <dt>CONSTRAINTS</dt>
     *  <dd>Additional constraints defined at the end of the field definition.</dd>
     *  <dt>INDEX</dt>
     *  <dd>Create an index on this field. Index-type may be specified as INDEX(type). MySQL types are UNIQUE etc</dd>
     *  <dt>FOREIGN</dt>
     *  <dd>Create a foreign key on this field. Specify reference-table parameters as FOREIGN(tblname,tblfield).</dd>
     *</dl>
     *
     * @param mixed  $tableoptions optional table options for the table creation command.
     *  Or an associative array of table options, keys being the database type (as available)
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function CreateTableSQL($tabname, $defn, $tableoptions = false)
    {
        $dbtype = $this->dbType();
        // clean up input tableoptions
        if (!$tableoptions) {
            $tableoptions = [$dbtype =>
            'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci']; //default table options
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

        list($lines, $pkey) = $this->GenFields($defn, true);
        $taboptions = $this->Options($tableoptions);
        $tabname = $this->TableName($tabname);
        $sql = $this->TableSQL($tabname, $lines, $pkey, $taboptions);

        return $sql;
    }

    /**
     * Execute the members of the given array of SQL commands.
     *
     * @param array $sql             Array of sql command string(s)
     * @param bool  $continueOnError Whether to continue on errors
     * @return int 0 immediately after error, 1 if continued after an error, 2 for no errors
     */
    public function ExecuteSQLArray($sql, $continueOnError = true)
    {
        $res = 2;
        foreach ($sql as $line) {
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
     * @param mixed $defn array of strings or comma-separated series in one string
     * @param bool  $widespacing optional flag whether to pad the field-name, default false
     * @return 2-member array
     *  [0] = assoc. array or empty. Each member ucase-fieldname=>field-defn
     *  [1] = array of primary-key-field name(s), maybe empty
     */
    protected function GenFields($defn, $widespacing = false)
    {
        if (is_string($defn)) {
            $flds = [];
            $parts = $this->ParseDefn($defn);
            $hasparam = false;
            foreach ($parts as $f0) {
                if (!count($f0)) {
                    break;
                }
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
        } else {
            $flds = $defn;
        }

        $this->autoIncrement = false;
        $lines = [];
        $pkey = [];

        foreach ($flds as $fld) {
            $fld = $this->UpperKeys($fld);
            $fname = false;
            $fdefault = false;
            $fautoinc = false;
            $ftype = false;
            $fsize = false;
            $fprec = false;
            $fprimary = false;
            $fnoquote = false;
            $fdefts = false;
            $fdefdate = false;
            $fconstraint = false;
            $fnot = false;
            $fnotnull = false;
            $funsigned = false;
            $findex = false;
            $fforeign = false;
            $fafter = false;

            //-----------------
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
                    case 'AUTOINCREMENT':
                        $fautoinc = true;
                        $fnotnull = true;
                        break;
                    case 'KEY':
                    case 'PRIMARY':
                        $fprimary = $v;
                        $fnotnull = true;
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
                    case 'INDEX':
                    case 'UNIQUE':
                    case 'FULLTEXT':
                        $findex = $attr;  //last-used prevails
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
                    default:
                        continue 2; // don't clear unprocessed field
                }
                $fld[$i] = '';
            }

            //--------------------
            // VALIDATE FIELD INFO
            if (!$fname) {
                die('failed');
            }

            if (!$ftype) {
                return [[], []];
            }

            $ftype = $this->GetSize(strtoupper($ftype), $ty, $fsize, $fprec);

            switch ($ty) {
                case 'X':
                case 'X2':
                case 'B':
                case 'LX':
                case 'XL':
                case 'MX':
                case 'XM':
                case 'LB':
                case 'BL':
                case 'MB':
                case 'BM':
                    $fdefault = false; //TEXT and BLOB fields cannot have a DEFAULT value
                    $fnotnull = false;
            }

            $fid = strtoupper(preg_replace('/^`(.+)`$/', '$1', $fname));
            $fname = $this->NameQuote($fname);

            if ($fprimary) {
                $pkey[] = $fname;
            }

            //--------------------
            // CONSTRUCT FIELD SQL
            if ($fdefts) {
                $fdefault = 'TIMESTAMP';
            } elseif ($fdefdate) {
                $fdefault = 'DATE';
            } elseif ($fdefault !== false && !$fnoquote) {
                if ($ty == 'C' || $ty[0] == 'X' ||
                    ($fdefault[0] != "'" && !is_numeric($fdefault))) {
                    $len = strlen($fdefault);
                    if ($len != 1 && $fdefault[0] == ' ' && $fdefault[$len - 1] == ' ') {
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

            $suffix = $this->CreateSuffix($fnotnull, $fdefault, $fautoinc, $fconstraint, $funsigned);

            $s = implode(' ', array_filter($fld));
            if ($s) {
                $suffix .= ' '.$s;
            }

            $s = ($widespacing) ? str_pad($fname, 24) : $fname;
            $s .= ' '.$ftype.$suffix;

            if ($fafter) {
                $s .= ' AFTER '.$this->NameQuote($fafter);
            }

            if ($findex) {
                $s .= ", $findex idx_{$fname}($fname)";
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
        return [$lines, $pkey];
    }

    /**
     * Generate the size part of the field defn.
     *
     * @ignore
     *
     * @internal
     */
    protected function GetSize($ftype, $ty, $fsize, $fprec)
    {
        if ($fsize) {
            if ($ty == 'B' || $ty == 'X') {
                if ($fsize <= 256) {
                    if ($ty == 'X') {
                        if (--$fsize < 1) $fsize = 1; //too bad about 256!
                        return 'VARCHAR('.$fsize.')';
                    } else {
                        return 'TINYBLOB';
                    }
                } elseif ($fsize <= 65536) {
                    return $ftype;
                } elseif ($fsize <= 1 << 24) {
                    return 'MEDIUM'.$ftype;
                } else {
                    return 'LONG'.$ftype;
                }
            } elseif (strpos($ftype, '(') === false) {
                $ftype .= '('.$fsize;
                if (strlen($fprec)) {
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
     */
    protected function CreateSuffix($fnotnull, $fdefault, $fautoinc, $fconstraint, $funsigned)
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

    /**
     * Generate SQL to create an index.
     * @param mixed $flds array of strings or comma-separated series in one string, or falsy
     * @internal
     */
    protected function IndexSQL($idxname, $tabname, $flds, $idxoptions)
    {
        $sql = [];

        if (isset($idxoptions['REPLACE']) || isset($idxoptions['DROP'])) {
//            if (1) { //this->alterTableAddIndex was always true
                $sql[self::$ctr++] = self::ALTERTABLE."$tabname DROP INDEX $idxname";
//            } else {
//                $sql[self::$ctr++] = sprintf(self::DROPINDEX, $idxname, $tabname);
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
            $s = self::ALTERTABLE."$tabname ADD{$unique} INDEX $idxname";
//        } else {
//            $s = "CREATE{$unique} INDEX $idxname ON $tabname";
//        }

        $s .= ' ('.$flds.')';

        if (($opts = $this->get_dbtype_options($idxoptions))) {
            $s .= $opts;
        }

        $sql[self::$ctr++] = $s;

        return $sql;
    }

    /**
     * Drop the auto increment column on a table.
     *
     * @internal
     */
    protected function DropAutoIncrement($tabname)
    {
        return false;
    }

    /**
     * Get a list of database type-specific options for a command.
     *
     * @internal
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
     * Build string for generating table.
     * @internal
     * @param string $tabname Table name
     * @param type $lines Field definition(s) from field-parsing
     * @param array $pkey Primary-key-field name(s), from field-parsing
     * @param type $tableoptions Whole-table definitions
     * @return string SQL
     */
    protected function TableSQL($tabname, $lines, $pkey, $tableoptions)
    {
        $sql = [];

        if (isset($tableoptions['REPLACE']) || isset($tableoptions['DROP'])) {
            $sql[self::$ctr++] = sprintf(self::DROPTABLE, $tabname);
            if ($this->autoIncrement) {
                $sInc = $this->DropAutoIncrement($tabname);
                if ($sInc) {
                    $sql[self::$ctr++] = $sInc;
                }
            }
            if (isset($tableoptions['DROP'])) {
                return $sql;
            }
        }
        $s = "CREATE TABLE $tabname (\n";
        $s .= implode(",\n", $lines);
        if ($pkey) {
            $s .= ",\nPRIMARY KEY (";
            $s .= implode(', ', $pkey).')';
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
        $sql[self::$ctr++] = $s;

        return $sql;
    }

    /**
     * Sanitize options.
     *
     * @internal
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
        }

        return $opts;
    }

    /**
     * Convert options into a format usable by the system.
     *
     * @internal
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
