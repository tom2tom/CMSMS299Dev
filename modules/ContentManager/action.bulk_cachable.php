<?php
/*
ContentManager module action: flag multiple pages as [not] cachable
Copyright (C) 2013-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Lone;
use function CMSMS\log_error;
use function CMSMS\log_notice;

if (!$this->CheckContext()) {
	exit;
}

if (!$this->CheckPermission('Manage All Content')) {
	$this->SetError($this->Lang('error_bulk_permission'));
	$this->Redirect($id, 'defaultadmin', $returnid);
}
if (empty($params['bulk_content'])) {
	$this->SetError($this->Lang('error_missingparam'));
	$this->Redirect($id, 'defaultadmin', $returnid);
}

$contentops = Lone::get('ContentOperations');
$pagelist = $params['bulk_content'];
$cachable = isset($params['cachable']) && cms_to_bool($params['cachable']);
$user_id = get_userid();
$n = 0;

try {
	foreach ($pagelist as $pid) {
		$content = $contentops->LoadEditableContentFromId($pid);
		if (!is_object($content)) {
			continue;
		}

		$content->SetCachable($cachable);
		$content->SetLastModifiedBy($user_id);
		$content->Save();
		++$n;
	}
	log_notice('ContentManager', 'Changed cachable status on '.$n.' pages');
	$this->SetMessage($this->Lang('msg_bulk_successful'));
} catch (Throwable $t) {
	log_error('Multi-page cachability change failed', $t->getMessage());
	$this->SetError($t->getMessage());
}
//$cache = Lone::get('LoadedData');
// TODO or refresh() & save, ready for next stage ?
//$cache->delete('content_quicklist');
//$cache->delete('content_tree');
//$cache->delete('content_flatlist');

$this->Redirect($id, 'defaultadmin', $returnid);