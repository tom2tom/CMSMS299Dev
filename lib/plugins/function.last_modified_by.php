<?php
#Plugin to...
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\UserOperations;

function smarty_function_last_modified_by($params, $template)
{
	$gCms = CmsApp::get_instance();
	$content_obj = $gCms->get_content_object();
	$id = '';

	if (isset($content_obj) && $content_obj->LastModifiedBy() > -1)	{
		$id = $content_obj->LastModifiedBy();
	} else {
		return;
	}

	$format = 'id';
	if(!empty($params['format'])) $format = $params['format'];
	$thisuser = UserOperations::get_instance()->LoadUserByID($id);
	if( !$thisuser ) return; // could not find user record.

	$output = '';
	if($format==='id') {
		$output = $id;
	} else if ($format==='username') {
		$output = cms_htmlentities($thisuser->username);
	} else if ($format==='fullname') {
		$output = cms_htmlentities($thisuser->firstname .' '. $thisuser->lastname);
	}

	if( isset($params['assign']) ) {
		$template->assign(trim($params['assign']),$output);
		return;
	}
	return $output;
}

function smarty_cms_about_function_last_modified_by()
{
	echo <<<'EOS'
<p>Author: Ted Kulp &lt;ted@cmsmadesimple.org&gt;</p>
<ul>Change History:</p>
<ul>
<li>Added assign parameter (Calguy)</li>
</ul>
</p>
EOS;
}
