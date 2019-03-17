<?php
/*
Methods for creating, modifying a database or its components
Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Database\Connection;
use function startswith;

// shouldn't need this.
if (!function_exists('ctype_alnum')) {
    /**
     * @ignore
     */
    function ctype_alnum($text)
    {
        return preg_match('/^[a-z0-9]*$/i', $text);
    }
}

/**
 * @ignore
 */
function _array_change_key_case($an_array)
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
 * Parse $args into an array
 * @ignore
 * @param string $args
 * @param string $endstmtchar optional separator character. Default ','
 * @param string $tokenchars  optional series of ? characters. default '_.-'
 * @return array
 */
function Lens_ParseArgs($args, $endstmtchar = ',', $tokenchars = '_.-')
{
    $pos = 0;
    $stmtno = 0;
    $intoken = false;
    $endquote = false;
    $quoted = false;
    $tokens = [];
    $tokens[] = []; //for $stmtno=0
    $max = strlen($args);

    while ($pos < $max) {
        $ch = $args[$pos];
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
                    $ch2 = $args[$pos + 1];
                    if ($ch2 == $endquote) {
                        ++$pos;
                        $tokarr[] = $ch2;
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
        }
        ++$pos;
    }
    if ($intoken) {
        $tokens[$stmtno][] = implode('', $tokarr);
    }

    return $tokens;
}

/**
 * A class defining methods to work directly with database tables.
 *
 * This file is based on the DataDictionary base class from the adodb_lite library
 * which was in turn a fork of the adodb library in 2004 or thereabouts.
 *
 * Credits and kudos to the authors of those packages.
 *
 * @since 2.3
 */
class DataDictionary
{
    /**
     * SQL sub-string to use for the start of an alter table command
     *
     * @internal
     */
    const alterTable = 'ALTER TABLE ';

    /**
     * SQL command template for creating a drop table command.
     *
     * @internal
     */
    const dropTable = 'DROP TABLE IF EXISTS %s'; // requires MySQL 3.22+

    /**
     * SQL command template for renaming a table.
     *
     * @internal
     */
    const renameTable = 'RENAME TABLE %s TO %s';

    /**
     * SQL command template for dropping an index.
     *
     * @internal
     */
    const dropIndex = 'DROP INDEX %s ON %s';

    /**
     * SQL sub-string to use (in the alter table command) when adding a column.
     *
     * @internal
     */
    const addCol = ' ADD COLUMN';

    /**
     * SQL sub-string to use (in the alter table command) when altering a column.
     *
     * @internal
     */
    const alterCol = ' MODIFY COLUMN';

    /**
     * SQL sub-string to use (in the alter table command) when dropping a column.
     *
     * @internal
     */
    const dropCol = ' DROP COLUMN';

    /**
     * SQL command template for renaming a column.
     *
     * @internal
     */
    const renameColumn = 'ALTER TABLE %s CHANGE COLUMN %s %s %s';

    /**
     * @ignore
     */
    const sysTimeStamp = 'TIMESTAMP';

    /**
     * @ignore
     */
    const sysDate = 'DATE';

    /**
     * @ignore
     */
    const nameRegex = '\w';

    /**
     * @ignore
     */
    const nameRegexBrackets = 'a-zA-Z0-9_\(\)';

    /**
     * @ignore
     */
    const invalidResizeTypes4 = ['CLOB', 'BLOB', 'TEXT', 'DATE', 'TIME']; // for changetablesql

    /**
     * @ignore
     */
    const nameQuote = '`'; // string to use to quote identifiers and names

    /**
     * The database connection object.
     *
     * @internal
     */
    protected $connection;

    /**
     * @ignore
     */
    protected $autoIncrement = false;

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
    protected function _dbType()
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
     * @param string $name          The input name
     * @param bool   $allowBrackets Optional flag whether brackets should be quoted. Default false
     * @return string
     */
    protected function NameQuote($name = null, $allowBrackets = false)
    {
        if (!is_string($name)) {
            return false;
        }

        $name = trim($name);

        if (!is_object($this->connection)) {
            return $name;
        }

        $quote = self::nameQuote;

        // if name is of the form `name`, quote it
        if (preg_match('/^`(.+)`$/', $name, $matches)) {
            return $quote.$matches[1].$quote;
        }

        // if name contains special characters, quote it
        $regex = ($allowBrackets) ? self::nameRegexBrackets : self::nameRegex;

        if (!preg_match('/^['.$regex.']+$/', $name)) {
            return $quote.$name.$quote;
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
     * Given an array of SQL commands execute them in sequence.
     *
     * @param array $sql             Array of sql command string(s)
     * @param bool  $continueOnError Whether to continue on errors
     * @return int 0 immediately after error, 1 if continued after an error, 2 for no errors
     */
    public function ExecuteSQLArray($sql, $continueOnError = true)
    {
        $res = 2;
        foreach ($sql as $line) {
            $this->connection->execute($line);
            if ($this->connection->errno > 0) {
                if (!$continueOnError) {
                    return 0;
                }
                $res = 1;
            }
        }

        return $res;
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
        $options = $this->_Options($options);

        $s = 'CREATE DATABASE '.$this->NameQuote($dbname);
        if (isset($options[$this->upperName])) {
            $s .= ' '.$options[$this->upperName];
        }

        return [$s];
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

        return $this->_IndexSQL($this->NameQuote($idxname), $this->TableName($tabname), $flds, $this->_Options($idxoptions));
    }

    /**
     * Generate the SQL to drop an index.
     *
     * @param string $idxname Index name
     * @param string $tabname Optional table name. Default null
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function DropIndexSQL($idxname, $tabname = null)
    {
        return [sprintf(self::dropIndex, $this->NameQuote($idxname), $this->TableName($tabname))];
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
        $alter = self::alterTable.$this->TableName($tabname).self::addCol.' ';
        $sql = [];
        list($lines, $pkey) = $this->_GenFields($defn);
        foreach ($lines as $v) {
            $sql[] = $alter.$v;
        }

        return $sql;
    }

    /**
     * Generate the SQL to change the definition of one column.
     *
     * @param string       $tabname      table-name
     * @param string       $defn         column-name and type for the changed column
     *  May include FIRST or 'AFTER other-colname' to re-order the field.
     * @param string       $tableflds    UNUSED optional complete columns-definition of the revised table
     * @param array/string $tableoptions UNUSED optional options for the revised table see CreateTableSQL, default ''
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function AlterColumnSQL($tabname, $defn, $tableflds = '', $tableoptions = '')
    {
        $alter = self::alterTable.$this->TableName($tabname).self::alterCol.' ';
        $sql = [];
        list($lines, $pkey) = $this->_GenFields($defn);
        foreach ($lines as $v) {
            $sql[] = $alter.$v;
        }

        return $sql;
    }

    /**
     * Generate the SQL to rename one column.
     *
     * @param string $tabname   table-name
     * @param string $oldcolumn current column-name
     * @param string $newcolumn new column-name, may be empty if the new name is at the start of $defn
     * @param string $defn      optional column definition (using DataDictionary meta types). Default ''
     * NOTE the resultant command will silently fail unless a non-empty $defn value
     * is provided, but to preserve back-compatibility, it remains an optional parameter.
     * May include FIRST or 'AFTER other-colname' to re-order the field.
     * $newcolumn will be prepended to $defn if it's not already there.
     * @return array Strings suitable for use with the ExecuteSQLArray method
     */
    public function RenameColumnSQL($tabname, $oldcolumn, $newcolumn, $defn = '')
    {
        if ($defn) {
            $defn = trim($defn);
            if ($newcolumn && strpos($defn, $newcolumn) !== 0) {
                $defn = $newcolumn.' '.$defn;
            }
            list($lines,) = $this->_GenFields($defn);
            $first = reset($lines);
            list($name, $column_def) = preg_split('/\s+/', $first, 2);
            if (!$newcolumn) {
                $newcolumn = $name;
            }
        } else {
            $column_def = '';  //BAD causes command to fail TODO find something
        }

        return [sprintf(self::renameColumn,
            $this->TableName($tabname),
            $this->NameQuote($oldcolumn),
            $this->NameQuote($newcolumn),
            $column_def)];
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

        $alter = self::alterTable.$this->TableName($tabname).self::dropCol.' ';
        $sql = [];
        foreach ($colname as $v) {
            $sql[] = $alter.$this->NameQuote(trim($v));
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
        return [sprintf(self::dropTable, $this->TableName($tabname))];
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
        return [sprintf(self::renameTable, $this->TableName($tabname), $this->TableName($newname))];
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
            // if the Metatype and size are exactly the same, ignore - by Mark Newham
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
        list($lines, $pkey) = $this->_GenFields($defn);
        $alter = self::alterTable.$this->TableName($tablename);
        $sql = [];

        foreach ($lines as $id => $v) {
            if (isset($cols[$id]) && is_object($cols[$id])) {
                $parts = Lens_ParseArgs($v.' '); //trailing pad needed
                //  We are trying to change the size of the field, if not allowed, simply ignore the request.
                if ($parts && in_array(strtoupper(substr($parts[0][1], 0, 4)), self::invalidResizeTypes4)) {
                    continue;
                }

                $sql[] = $alter.self::alterCol.' '.$v;
            } else {
                $sql[] = $alter.self::addCol.' '.$v;
            }
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

		list($lines, $pkey) = $this->_GenFields($defn, true);
		$taboptions = $this->_Options($tableoptions);
		$tabname = $this->TableName($tabname);
		$sql = $this->_TableSQL($tabname, $lines, $pkey, $taboptions);
		$tsql = $this->_Triggers($tabname, $taboptions);
		foreach ($tsql as $s) {
			$sql[] = $s;
		}

		return $sql;
    }

    /**
     * Part of the process of parsing the data-dictionary format into MySQL commands.
     *
     * @internal
     * @param mixed $defn array of strings or comma-separated series in one string
     * @param bool  $widespacing optional flag whether to pad the field-name, default false
     * @return 2-member array
	 *  [0] = array of ? or empty
	 *  [1] = 1-member array with primary key, or empty
     */
    protected function _GenFields($defn, $widespacing = false)
    {
        if (is_string($defn)) {
            $flds = [];
            $parts = Lens_ParseArgs($defn.' '); //trailing pad supports checking all 'next' chars
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
            $fld = _array_change_key_case($fld);
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
            $flast = false;

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
                    case 'FIRST':
                        $flast = $i;
                        break;
                    case 'FOREIGN':
                        $fforeign = $v;
                        break;
                }
            }

            //--------------------
            // VALIDATE FIELD INFO
            if (!$fname) {
                die('failed');
            }

            if (!$ftype) {
                return [[], []];
            }

            $ftype = $this->_GetSize(strtoupper($ftype), $ty, $fsize, $fprec);

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

            if ($flast !== false) {
                $v = $fld[$flast];
                if (strcasecmp($v, 'AFTER') == 0) {
                    if (empty($fld[$flast + 1])) return [[], []]; //TODO
                }
            }

            //--------------------
            // CONSTRUCT FIELD SQL
            if ($fdefts) {
                $fdefault = self::sysTimeStamp;
            } elseif ($fdefdate) {
                $fdefault = self::sysDate;
            } elseif ($fdefault !== false && !$fnoquote) {
                if ($ty == 'C' || $ty[0] == 'X' ||
                    ($fdefault[0] != "'" && !is_numeric($fdefault))) {
                    $len = strlen($fdefault);
                    if ($len != 1 && $fdefault[0] == ' ' && $fdefault[$len - 1] == ' ') {
                        $fdefault = trim($fdefault);
                    } elseif (strtolower($fdefault) != 'null') {
                        $fdefault = $this->connection->qStr($fdefault);
                    }
                }
            }
            $suffix = $this->_CreateSuffix($fname, $ftype, $fnotnull, $fdefault, $fautoinc, $fconstraint, $funsigned);

            $s = ($widespacing) ? str_pad($fname, 24) : $fname;
            $s .= ' '.$ftype.$suffix;

            if ($flast !== false) {
                $s .= ' '.strtoupper($v);
                if (strcasecmp($v, 'AFTER') == 0) {
                    $s .= ' '.$this->NameQuote($fld[$flast + 1]); //TODO
                }
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
     * Generate the size part of the datatype.
     *
     * @ignore
     *
     * @internal
     */
    protected function _GetSize($ftype, $ty, $fsize, $fprec)
    {
        if ($fsize) {
            if ($ty == 'B' || $ty == 'X') {
                if ($fsize <= 256) {
                    if ($ty == 'X') {
                        if (--$fsize < 1) $fsize = 1;
                        $ftype = 'VARCHAR('.$fsize.')';
                    } else {
                        $ftype = 'TINYBLOB';
                    }
                } elseif ($fsize > 2**16 && $fsize <= 2**24) {
                    $ftype = 'MEDIUM'.$ftype;
                } else {
                    $ftype = 'LONG'.$ftype;
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

	/**
     * Generate SQL to create an index.
     * @param mixed $flds array of strings or comma-separated series in one string, or falsy
     * @internal
     */
    protected function _IndexSQL($idxname, $tabname, $flds, $idxoptions)
    {
        $sql = [];

        if (isset($idxoptions['REPLACE']) || isset($idxoptions['DROP'])) {
//            if (1) { //this->alterTableAddIndex was always true
                $sql[] = self::alterTable."$tabname DROP INDEX $idxname";
//            } else {
//                $sql[] = sprintf(self::dropIndex, $idxname, $tabname);
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
            $s = self::alterTable."$tabname ADD{$unique} INDEX $idxname";
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

    /**
     * Drop the auto increment column on a table.
     *
     * @internal
     */
    protected function _DropAutoIncrement($tabname)
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
        $dbtype = $this->_dbType();
        $list = [$dbtype.$suffix, strtoupper($dbtype).$suffix, strtolower($dbtype).$suffix];

        foreach ($list as $one) {
            if (isset($opts[$one]) && is_string($opts[$one]) && strlen($opts[$one])) {
                return $opts[$one];
            }
        }
    }

    /**
     * Build strings for generating tables.
     *
     * @internal
     */
    protected function _TableSQL($tabname, $lines, $pkey, $tableoptions)
    {
        $sql = [];

        if (isset($tableoptions['REPLACE']) || isset($tableoptions['DROP'])) {
            $sql[] = sprintf(self::dropTable, $tabname);
            if ($this->autoIncrement) {
                $sInc = $this->_DropAutoIncrement($tabname);
                if ($sInc) {
                    $sql[] = $sInc;
                }
            }
            if (isset($tableoptions['DROP'])) {
                return $sql;
            }
        }
        $s = "CREATE TABLE $tabname (\n";
        $s .= implode(",\n", $lines);
        if (count($pkey) > 0) {
            $s .= ",\n                 PRIMARY KEY (";
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
        $sql[] = $s;

        return $sql;
    }

    /**
     * Generate triggers if needed.
     * This is used when table has auto-incrementing field that is emulated using triggers.
     *
     * @internal
     */
    protected function _Triggers($tabname, $taboptions)
    {
        return [];
    }

    /**
     * Sanitize options.
     *
     * @internal
     */
    protected function _ProcessOptions($opts)
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

    /**
     * Convert options into a format usable by the system.
     *
     * @internal
     */
    protected function _Options($opts)
    {
        $opts = $this->_ProcessOptions($opts);
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
