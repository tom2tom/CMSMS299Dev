<?php
/*
ContentManager module action: bulk_settemplate
Copyright (C) 2019-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use CMSMS\UserParams;
use function CMSMS\log_error;
use function CMSMS\log_notice;

if (!$this->CheckContext()) {
	exit;
}

if (isset($params['cancel'])) {
	$this->SetInfo($this->Lang('msg_cancelled'));
	$this->Redirect($id, 'defaultadmin', $returnid);
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

$showmore = 0;
if (isset($params['showmore'])) {
	$showmore = (int) $params['showmore'];
	UserParams::set('cgcm_bulk_showmore', $showmore);
}
if (isset($params['submit'])) {
	if (!isset($params['confirm1']) || !isset($params['confirm2'])) {
		$this->SetError($this->Lang('error_notconfirmed'));
		$this->Redirect($id, 'defaultadmin', $returnid);
	}
	if (!isset($params['template'])) {
		$this->SetError($this->Lang('error_missingparam'));
		$this->Redirect($id, 'defaultadmin', $returnid);
	}

	set_time_limit(9999);
	$user_id = get_userid();
	$n = 0;

	try {
		foreach ($pagelist as $pid) {
			$content = $contentops->LoadEditableContentFromId($pid);
			if (!is_object($content)) {
				continue;
			}

			$content->SetTemplateId((int)$params['template']);
			$content->SetLastModifiedBy($user_id);
			$content->Save();
			++$n;
		}
		if ($n != count($pagelist)) {
			throw new Exception('Bulk operation to set template did not adjust all selected pages');
		}
		log_notice('ContentManager', 'Changed template of '.$n.' pages');
		$this->SetMessage($this->Lang('msg_bulk_successful'));
	} catch (Throwable $t) {
		log_error('Failed to change template on multiple pages', $t->getMessage());
		$this->SetError($t->getMessage());
	}
//	$cache = Lone::get('LoadedData');
	// TODO or refresh() & save, ready for next stage ?
//	$cache->delete('content_quicklist');
//	$cache->delete('content_tree');
//	$cache->delete('content_flatlist');

	$this->Redirect($id, 'defaultadmin', $returnid);
}

$displaydata = [];
foreach ($pagelist as $pid) {
	$content = $contentops->LoadEditableContentFromId($pid);
	if (!is_object($content)) {
		continue; // this should never happen either
	}

	$rec = [];
	$rec['id'] = $content->Id();
	$rec['name'] = $content->Name();
	$rec['menutext'] = $content->MenuText();
	$rec['owner'] = $content->Owner();
	$rec['alias'] = $content->Alias();
	$displaydata[] = $rec;
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('bulk_settemplate.tpl')); //,null,null,$smarty);

$tpl->assign('showmore', UserParams::get('cgcm_bulk_showmore')) // WHAT ??
	->assign('pagelist', $params['bulk_content'])
	->assign('displaydata', $displaydata);

$dflt_tpl_id = -1;
try {
	$dflt_tpl = TemplateOperations::get_default_template_by_type(TemplateType::CORE.'::page');
	$dflt_tpl_id = $dflt_tpl->get_id();
} catch (Throwable $t) {
	// ignore
}
$tpl->assign('dflt_tpl_id', $dflt_tpl_id);
if ($showmore) {
	$_tpl = TemplateOperations::template_query(['as_list' => 1]);
	$tpl->assign('alltemplates', $_tpl);
} else {
	// gotta get the core page template type
	$_type = TemplateType::load(TemplateType::CORE.'::page');
	$_tpl = TemplateOperations::template_query(['t:'.$_type->get_id(), 'as_list' => 1]);
	$tpl->assign('alltemplates', $_tpl);
}

$js = <<<'EOS'
<script>
$(function() {
  $('#showmore_ctl').on('click', function() {
    $(this).closest('form').submit();
  });
});
</script>
EOS;
add_page_foottext($js);

$tpl->display();
