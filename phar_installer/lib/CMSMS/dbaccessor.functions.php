<?php

// unlike accessor.functions.php, this has no namespace

/**
 * @param array $config parameters for connection
 * @return Connection-object of some sort
 * @throws Exception
 */
function GetDb(array $config)
{
  if (1) { //TODO check for new Connection class
    $db = new CMSMS\Database\mysqli\Connection($config);
  } else {
    $db = NULL; //TODO
  }
  if ($db) {
    return $db;
  }
  throw new Exception('Failed to connect to database');
}
/**
 * @param Connection-object of some sort $db
 * @return DataDictionary-object
 */
function GetDataDictionary($db)
{
  if (1) { //TODO check for new Connection class
    return $db->NewDataDictionary();
  } else {
    return NewDataDictionary($db);
  }
}
