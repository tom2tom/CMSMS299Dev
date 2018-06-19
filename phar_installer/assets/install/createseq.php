<?php

if (isset($CMS_INSTALL_CREATE_TABLES)) {
    $table_ids = [
        'additional_users'          => ['id' => 'additional_users_id'],
        'admin_bookmarks'           => ['id' => 'bookmark_id'],
        'content'                   => ['id' => 'content_id'],
        'events'                    => ['id' => 'event_id'],
        'event_handlers'            => ['id' => 'handler_id', 'seq' => 'event_handler_seq'],
        'group_perms'               => ['id' => 'group_perm_id'],
        'groups'                    => ['id' => 'group_id'],
        'users'                     => ['id' => 'user_id'],
        'permissions'               => ['id' => 'permission_id']
    ];

    status_msg(ilang('install_update_sequences'));
    foreach ($table_ids as $tablename => $tableinfo) {
        $sql = 'SELECT COALESCE(MAX(?),0) AS maxid FROM '.CMS_DB_PREFIX.$tablename;
        $max = $db->GetOne($sql,[$tableinfo['id']]);
        $tableinfo['seq'] = $tableinfo['seq'] ?? $tablename . '_seq';
        verbose_msg(ilang('install_updateseq',$tableinfo['seq']));
        $db->CreateSequence(CMS_DB_PREFIX.$tableinfo['seq'], $max);
    }
}

