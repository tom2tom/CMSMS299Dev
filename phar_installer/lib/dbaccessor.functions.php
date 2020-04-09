<?php

// unlike accessor.functions.php, this has global namespace

/**
 * @param array $config parameters for connection
 * @return Connection object
 * @throws Exception
 */
function GetDb(array $config)
{
    if (is_file(__DIR__.DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR.'class.ConnectionSpec.php')) {
        // old Connection class
        $spec = new CMSMS\Database\ConnectionSpec();
        $spec->type = $config['db_type'];
        $spec->host = $config['db_host'];
        $spec->username = $config['db_username'];
        $spec->password = $config['db_password'];
        $spec->dbname = $config['db_name'];
        $spec->port = $config['db_port'] ?? null;
        $spec->prefix = $config['db_prefix'];
        $db = new CMSMS\Database\mysqli\Connection($spec);
        if ($db instanceof CMSMS\Database\Connection) {
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
        $db = new CMSMS\Database\Connection($config);
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
 * @param Connection object
 * @return DataDictionary object
 */
function GetDataDictionary($db)
{
    return $db->NewDataDictionary(); //works for old and new
}
