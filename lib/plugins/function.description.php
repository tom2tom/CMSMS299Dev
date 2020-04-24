<?php
#Plugin to get a page title
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Elijah Lofgren and all other contributors from the CMSMS Development Team.
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

function smarty_function_description($params, $template)
{
	$gCms = CmsApp::get_instance();
	$content_obj = $gCms->get_content_object();

	if (!is_object($content_obj) || $content_obj->Id() == -1) {
		// We've a custom error message...  set a message
		$out='404 Error';
	}
	else {
		$result = $content_obj->TitleAttribute();
		$out = preg_replace("/\{\/?php\}/", '', $result);
	}

	if( isset($params['assign']) ) {
		$template->assign(trim($params['assign']),$out);
		return;
	}
	return $out;
}

function smarty_cms_about_function_description()
{
	echo <<<'EOS'
<p>Author: Elijah Lofgren &lt;elijahlofgren@elijahlofgren.com&gt;</p>
<p>Change History:</p>
<ul>
<li>None</li>
</ul>
EOS;
}
