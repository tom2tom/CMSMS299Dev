<?php

use News\Adminops;

if (!isset($gCms)) exit;

if (!$this->CheckPermission('Delete News')) {
    $this->SetError($this->Lang('needpermission', 'Modify News')); //probsaly useless before return
    return;
}

$articleid = $params['articleid'] ?? '';
if (Adminops::delete_article($articleid)) {
    $this->SetMessage($this->Lang('articledeleted'));
}
else {
    $this->SetError($this->Lang('error_unknown')); //TODO informative message
}

$this->Redirect($id, 'defaultadmin', $returnid, ['active_tab' => 'articles']);
