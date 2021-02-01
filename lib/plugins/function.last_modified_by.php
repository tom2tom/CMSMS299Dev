<?php
/*
Plugin to get information about the most-recent modifier of the current page.
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

use CMSMS\AppSingle;

function smarty_function_last_modified_by($params, $template)
{
	$out = lang('unknown');
	$content_obj = AppSingle::App()->get_content_object();
    if( is_object($content_obj) ) {
		$id = $content_obj->LastModifiedBy();
	    if( $id > -1) {
			$thisuser = AppSingle::UserOperations()->LoadUserByID($id);
			if( $thisuser ) {
				if( !empty($params['format']) ) {
					$format = $params['format'];
				}
				else {
					$format = 'id';
				}

				if( $format === 'id' ) {
					$out = $id;
				}
				elseif( $format === 'username' ) {
					$out = $thisuser->username;
				}
				elseif( $format === 'fullname' ) {
					$out = $thisuser->firstname .' '. $thisuser->lastname;
				}
				else {
					$out = '';
				}
			}
		}
	}
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}

	return $out;
}

function smarty_cms_help_function_last_modified_by()
{
	echo <<<'EOS'
<h3>What does it do?</h3>
Retrieves information about the most-recent editor/modifier of the current page.
<h4>Parameters:</h4>
<ul>
<li>format: optional, 'id'(default) | 'username' | 'fullname'</li>
</ul>
<br />
And/or Smarty generic parameters: nocache, assign etc
EOS;
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
