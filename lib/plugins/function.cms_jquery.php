<?php
#...
#Copyright (C) 2004-2018 Ted Kulp <ted@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#BUT withOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

function smarty_function_cms_jquery($params, &$template)
{
	$exclude = trim(get_parameter_value($params,'exclude'));
	$cdn = cms_to_bool(get_parameter_value($params,'cdn'));
	$append = trim(get_parameter_value($params,'append'));
	$ssl = cms_to_bool(get_parameter_value($params,'ssl'));
	$custom_root = trim(get_parameter_value($params,'custom_root'));
	$include_css = cms_to_bool(get_parameter_value($params,'include_css',1));

	// get the output
	$out = cms_get_jquery($exclude,$ssl,$cdn,$append,$custom_root,$include_css);
	if( isset($params['assign']) ) {
		$template->assign(trim($params['assign']),$out);
		return;
	}

	return $out;
}

