<?php
/*
Clone item action for CMSMS News module.
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use News\AdminOperations;

if (!isset($gCms)) exit;

if (!$this->CheckPermission('Modify News')) {
    $this->SetError($this->Lang('needpermission', 'Modify News')); //probsaly useless before return
    return '';
}

$articleid = $params['articleid'] ?? '';

if (AdminOperations::copy_article($articleid)) {
    $this->SetMessage($this->Lang('articlecopied'));
}
else {
    $this->SetError($this->Lang('error_unknown')); //TODO informative message
}

$this->RedirectToAdminTab('articles');
