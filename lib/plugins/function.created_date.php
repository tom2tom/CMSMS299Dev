<?php
#Plugin to get the creation date/time of a site page
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

function smarty_function_created_date($params, $template)
{
	$content_obj = CmsApp::get_instance()->get_content_object();

	if (is_object($content_obj) && $content_obj->GetCreationDate() > -1) {
		if(!empty($params['format'])) { $format = trim($params['format']); }
		else { $format = cms_siteprefs::get('defaultdateformat','%x %X'); }

		$time = $content_obj->GetCreationDate();
		$str = cms_htmlentities(strftime($format, $time));

		if( isset($params['assign']) ) {
			$template->assign(trim($params['assign']),$str);
			return;
		}
		return $str;
	}
}

function smarty_cms_about_function_created_date()
{
	echo <<<'EOS'
<p>Author: Ted Kulp &lt;ted@cmsmadesimple.org&gt;</p>
<p>Change History:<br />
<ul>
<li>May 2019 Revert to site preference 'defaultdateformat' if no 'format' parameter is supplied</li>
</ul>
EOS;
}
