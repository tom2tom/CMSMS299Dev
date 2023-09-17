<?php
/*
Move item action for CMSMS News module.
Copyright (C) 2022-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use News\AdminOperations;
use function CMSMS\log_error;

//if (some worthy test fails) exit;

if (!$this->CheckPermission('Modify News')) {
    log_error("No 'Modify News' permission", $this->GetName().'::movearticle');
    $this->ShowErrorPage('You are not authorized to modify news items');
    return;
}

$articleid = $params['articleid'] ?? '';
if (!$articleid) {
    $this->SetError($this->Lang('error_detailed', 'TBA')); //TODO informative message
}
$categoryid = $params['tocategory'] ?? '';
if (!$categoryid) {
    $this->SetError($this->Lang('error_detailed', 'TBA')); //TODO informative message
}

if ($articleid && $categoryid) {
    if (AdminOperations::move_article((int)$articleid, (int)$categoryid)) {
        $this->SetMessage($this->Lang('articlemoved'));
    } else {
        $this->SetError($this->Lang('error_detailed', 'TBA')); //TODO informative message
    }
}

$this->RedirectToAdminTab('articles');
