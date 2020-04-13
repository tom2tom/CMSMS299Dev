<?php

use function cms_installer\lang;

status_msg(lang('install_setsequence'));

// retained sequence tables for maybe-higher-traffic data with a higher race-risk
$table_ids = [
	'content' => ['id' => 'content_id'],
];

foreach ($table_ids as $tablename => $tableinfo) {
	$sql = 'SELECT COALESCE(MAX(?),0) AS maxid FROM '.CMS_DB_PREFIX.$tablename;
	$max = $db->GetOne($sql,[$tableinfo['id']]);
	$tableinfo['seq'] = $tableinfo['seq'] ?? $tablename . '_seq';
	verbose_msg(lang('install_updateseq',$tableinfo['seq']));
	$db->CreateSequence(CMS_DB_PREFIX.$tableinfo['seq'], $max);
}
