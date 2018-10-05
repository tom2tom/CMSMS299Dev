<?php

use News\Adminops;

if (!isset($gCms)) exit;

if (!$this->CheckPermission('Delete News')) {
    $this->SetError($this->Lang('needpermission', 'Modify News')); //probsaly useless before return
    return;
}

$articleid = '';
if (isset($params['articleid'])) {
    $articleid = $params['articleid'];
}

Adminops::delete_article($articleid);

$this->SetMessage($this->Lang('articledeleted'));

$this->Redirect($id, 'defaultadmin', $returnid, ['active_tab' => 'articles']);

