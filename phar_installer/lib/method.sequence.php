<?php

use function cms_installer\lang;
use function cms_installer\status_msg;
use function cms_installer\verbose_msg;

status_msg(lang('install_setsequence'));

// retained sequence tables for maybe-higher-traffic data and/or with a higher race-risk
$table_ids = [
    'content' => ['id' => 'content_id'],
    'users' => ['id' => 'user_id'],
];

foreach ($table_ids as $tablename => $tableinfo) {
    $sql = 'SELECT COALESCE(MAX(?),0) AS maxid FROM '.CMS_DB_PREFIX.$tablename;
    $max = $db->getOne($sql, [$tableinfo['id']]);
    $tableinfo['seq'] = $tableinfo['seq'] ?? $tablename . '_seq';
    verbose_msg(lang('install_updateseq', $tableinfo['seq']));
    $db->CreateSequence(CMS_DB_PREFIX.$tableinfo['seq'], $max);
}

// unique-integer provider
//$db->CreateSequence(CMS_DB_PREFIX.'counter', 0);
