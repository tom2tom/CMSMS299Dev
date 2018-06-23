<?php
#function to generate admin-page-content for an error display
#Copyright (C) 2004-2017 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
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

function smarty_function_page_error($params, $template)
{
	if( !cmsms()->test_state(CmsApp::STATE_ADMIN_PAGE) ) return;
	if( empty($params['msg']) ) return;

	$out = '<div class="pageerror">'.trim($params['msg']).'</div>';
	if( isset($params['assign']) ) {
		//TODO why global smarty instead of $template?
		$template->smarty->assign(trim($params['assign']),$out);
		return;
	}
	return $out;
}

