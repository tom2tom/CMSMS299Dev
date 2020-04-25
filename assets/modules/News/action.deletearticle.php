<?php
/*
Delete item action for CMSMS News module.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use News\AdminOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Delete News') ) exit;

$articleid = $params['articleid'] ?? '';
if( AdminOperations::delete_article($articleid) ) {
    $this->SetMessage($this->Lang('articledeleted'));
}
else {
    $this->SetError($this->Lang('error_unknown')); //TODO informative message
}

$this->RedirectToAdminTab('articles');
