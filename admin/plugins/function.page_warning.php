<?php
/*
Plugin to generate admin-page-content for a styled warning message
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

use CMSMS\AppState;
use function CMSMS\specialize;

function smarty_function_page_warning($params, $template)
{
	$out = '';

	if( AppState::test(AppState::ADMIN_PAGE) ) {
		if( isset($params['msg']) ) {
			$msg = trim($params['msg']);
			if( $msg !== '' ) {
				$msg = specialize($msg); // ensure merged content is ok
				$out = '<div class="pagewarn">'.$msg.'</div>';
			}
		}
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_page_warning()
{
	echo _ld('tags', 'about_generic'[2], 'htmlintro', <<<'EOS'
<li>detail</li> ... OR _la('none')
EOS
	);
}
*/
function smarty_cms_help_function_page_warning()
{
	echo _ld('tags', 'help_generic',
	'This plugin generates page-content for a styled warning-message on an admin page',
	'page_error msg=...',
    '<li>msg: the content to be displayed</li>'
	);
}
