<?php
/*
ContentManager module action: copy content
Copyright (C) 2013-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\SingleItem;

if (!$this->CheckContext()) {
	exit;
}

if (!isset($params['page'])) {
	$this->SetError($this->Lang('error_missingparam'));
	$this->Redirect($id, 'defaultadmin', $returnid);
}
$page_id = (int)$params['page'];
if ($page_id < 1) {
	$this->SetError($this->Lang('error_missingparam'));
	$this->Redirect($id, 'defaultadmin', $returnid);
}
if (!$this->CanEditContent($page_id)) {
	$this->SetError($this->Lang('error_copy_permission'));
	$this->Redirect($id, 'defaultadmin', $returnid);
}
//
// get the data
//
$contentops = SingleItem::ContentOperations();
$from_obj = $contentops->LoadEditableContentFromId($page_id, true);
if (!$from_obj) {
	$this->SetError($this->Lang('error_invalidpageid'));
	$this->Redirect($id, 'defaultadmin', $returnid);
}
$from_obj->GetAdditionalEditors();

$userid = get_userid();
$to_obj = clone $from_obj; // includes id = -1
$to_obj->SetName('Copy of '.$from_obj->Name());
$to_obj->SetMenuText('Copy of '.$from_obj->MenuText());
$to_obj->SetAlias('');
$to_obj->SetDefaultContent(false);
$to_obj->SetOwner($userid);
$to_obj->SetLastModifiedBy($userid);
$to_obj->SetURL('');
$_SESSION['__cms_copy_obj__'] = serialize($to_obj);
$this->Redirect($id, 'editcontent', '', ['content_id' => -1]); // -1 for not-existing & not adding
