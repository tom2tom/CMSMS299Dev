<?php
/*
OutMailer module save-platforms method
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is part of CMS Made Simple module: OutMailer.
Refer to licence and other details at the top of file OutMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/outmailer
*/

use OutMailer\Utils;

$current = $params['platform']; //e.g. 'alias' or -1
$objs = Utils::get_platforms_full($this);
if ($objs) {
/* TODO
    if (isset($params[$current.'~delete'])) {
    } else
*/
    if (isset($params['submit'])) {
        $sql = 'UPDATE '.CMS_DB_PREFIX.'module_outmailer_platforms SET active=0 WHERE active=1';
        $db->Execute($sql);
        if ($current != '-1') {
            $sql = 'UPDATE '.CMS_DB_PREFIX.'module_outmailer_platforms SET enabled=1,active=1 WHERE alias=?';
            $db->Execute($sql, [$current]);
            $this->SetPreference('platform', $current); //TODO
        }
    }

    //TODO property-changes
/*    foreach ($objs as $classname => $rec) {
        $rec['obj']->handle_setup_form($params);
    }
*/
}
