<?php

use CMSMS\StylesheetOperations;
use CMSMS\TemplateOperations;

// 1. extra table fields

$dict = GetDataDictionary($db);
$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.TemplateOperations::TABLENAME,'contentfile I(1) DEFAULT 0 AFTER listable');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.StylesheetOperations::TABLENAME,'contentfile I(1) DEFAULT 0');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropColumnSQL(CMS_DB_PREFIX.'module_smarty_plugins','cachable');
$dict->ExecuteSQLArray($sqlarray);
