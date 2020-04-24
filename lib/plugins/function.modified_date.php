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

function smarty_function_modified_date($params, $template)
{
	$content_obj = CmsApp::get_instance()->get_content_object();

	$format = '%x %X';
	if(!empty($params['format'])) $format = $params['format'];
	if (is_object($content_obj) && $content_obj->GetModifiedDate() > -1) {
		$time = $content_obj->GetModifiedDate();
		$str = cms_htmlentities(strftime($format, $time));

		if( isset($params['assign']) ) {
			$template->assign(trim($params['assign']),$str);
			return;
		}
		return $str;
	}
}

function smarty_cms_about_function_modified_date()
{
	echo <<<'EOS'
<p>Author: Ted Kulp &lt;ted@cmsmadesimple.org&gt;</p>
<p>Change History:</p>
<ul>
<li>Added assign paramater (calguy1000)</li>
</ul>
EOS;
}
