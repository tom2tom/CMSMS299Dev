<?php

namespace cms_installer;

use CMSMS\Database\Connection;
use CMSMS\Database\ConnectionSpec;
use CMSMS\Database\mysqli\Connection as OldConnection;
use Exception;
use function cms_installer\get_app;
use function cms_installer\joinpath;

// these functions are used only in wizard step 8 and/or 9 after
// 'connecting' to CMSMS, for creating / upgrading tables and their contents
 
/**
 * @param array $config parameters for connection
 * @return Connection object
 * @throws Exception
 */
function GetDb(array $config)
{
    $fp = joinpath(get_app()->get_destdir(), 'lib', 'classes', 'Database', 'mysqli');
    if (is_dir($fp)) {
        // we have the old database class
        $spec = new ConnectionSpec();
        $spec->type = $config['db_type'] ?? 'mysqli';
        $spec->host = $config['db_host'];
        $spec->username = $config['db_username'];
        $spec->password = $config['db_password'];
        $spec->dbname = $config['db_name'];
        $spec->port = $config['db_port'] ?? null;
        $spec->prefix = $config['db_prefix'];
        $db = new OldConnection($spec);
        if ($db instanceof Connection) {
            try {
                if (!$db->Connect()) {
                    $db = null;
                }
            } catch (Throwable $t) {
                $db = null;
            }
        } else {
            $db = null;
        }
    } else {
        $db = new Connection($config);
        if ($db->errno != 0) {
            $db = null;
        }
    }
    if ($db) {
        return $db;
    }
    throw new Exception('Failed to connect to database');
}

/**
 * Old-database-class dictionary getter
 * Deprecated since 2.99 instead use $db->NewDataDictionary()
 *
 * @param $db Connection object
 * @return DataDictionary object
 */
function GetDataDictionary($db)
{
    return $db->NewDataDictionary(); //works for old and new
}
