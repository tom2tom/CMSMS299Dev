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

function smarty_function_cms_filepicker($params, $template)
{
	$name = trim(get_parameter_value($params,'name'));
	if( !$name ) return;
	$filepicker = cms_utils::get_filepicker_module();
	if( !$filepicker ) return;

	$profile_name = trim(get_parameter_value($params,'profile'));
	$prefix = trim(get_parameter_value($params,'prefix'));
	$value = trim(get_parameter_value($params,'value'));
	$top = trim(get_parameter_value($params,'top'));
	$type = trim(get_parameter_value($params,'type')); // enum (numeric), not name
	$required = cms_to_bool(get_parameter_value($params,'required'));

	$name = $prefix.$name;

	$profile = $filepicker->get_profile_or_default($profile_name);
	$parms = [];
	if( $top ) $parms['top'] = $top;
	if( $type !== '') $parms['type'] = $type;
	if( $parms ) $profile = $profile->overrideWith( $parms );

	$out = $filepicker->get_html( $name, $value, $profile, $required );
	if( isset($params['assign']) ) {
		$template->assign( $params['assign'], $out );
	} else {
		return $out;
	}
}
