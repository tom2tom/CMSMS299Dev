<?php

// unlike accessor.functions.php, this has no namespace

/**
 * @param array $config parameters for connection
 * @return CMSMS\Database\mysqli\Connection object
 * @throws Exception
 */
function GetDb(array $config)
{
  if (file_exists(__DIR__.DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR.'class.ConnectionSpec.php')) {
    // old Connection class
    $spec = new CMSMS\Database\ConnectionSpec();
    $spec->type = $config['dbtype'];
    $spec->host = $config['dbhost'];
    $spec->username = $config['dbuser'];
    $spec->password = $config['dbpass'];
    $spec->dbname = $config['dbname'];
    $spec->port = $config['dbport'] ?? null;
    $spec->prefix = $config['dbprefix'];
    $db = new CMSMS\Database\mysqli\Connection($spec);
    if ($db instanceof CMSMS\Database\Connection) {
	  try {
        if (!$db->Connect()) {
          $db = null;
	    }
      } catch (Exception $e) {
        $db = null;
      }
    } else {
      $db = null;
    }
  } else {
    $db = new CMSMS\Database\mysqli\Connection($config);
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
 * @param CMSMS\Database\mysqli\Connection object
 * @return DataDictionary-object
 */
function GetDataDictionary($db)
{
  return $db->NewDataDictionary(); //works for old and new
}
