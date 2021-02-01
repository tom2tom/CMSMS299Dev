<?php
/*
CMSMailer module savegates method
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is part of CMS Made Simple module: CMSMailer.
Refer to licence and other details at the top of file CMSMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/cmsmailer
*/

use CMSMailer\Utils;

$gateway = $params['currentgate']; //e.g. 'alias' or -1
/* TODO
if (!(isset($params['submit']) || isset($params[$gateway.'~delete']))) {
    return;
}
*/
$objs = Utils::get_gateways_full($this);
if ($objs) {
    if (isset($params['submit'])) {
        $sql = 'UPDATE '.CMS_DB_PREFIX.'module_cmsmailer_gates SET active=0 WHERE active=1';
        $db->Execute($sql);
        if ($gateway != '-1') {
            $sql = 'UPDATE '.CMS_DB_PREFIX.'module_cmsmailer_gates SET enabled=1,active=1 WHERE alias=?';
            $db->Execute($sql, [$gateway]);
        }
    }

    //TODO property-changes

/*    foreach ($objs as $classname => $rec) {
        $rec['obj']->handle_setup_form($params);
    }
*/
}
