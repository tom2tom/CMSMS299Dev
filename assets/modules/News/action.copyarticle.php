<?php

use News\Adminops;

if (!isset($gCms)) exit;

if (!$this->CheckPermission('Modify News')) {
    $this->SetError($this->Lang('needpermission', 'Modify News')); //probsaly useless before return
    return;
}

$articleid = $params['articleid'] ?? '';

if (Adminops::copy_article($articleid)) {
    $this->SetMessage($this->Lang('articlecopied'));
}
else {
    $this->SetError($this->Lang('error_unknown')); //TODO informative message
}

$this->Redirect($id, 'defaultadmin', $returnid, ['active_tab' => 'articles']);
