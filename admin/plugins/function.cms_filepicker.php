<?php
#Plugin to...
#Copyright (C) 2004-2018 Ted Kulp <ted@cmsmadesimple.org>
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
	$profile_name = trim(get_parameter_value($params,'profile'));
	$prefix = trim(get_parameter_value($params,'prefix'));
	$name = trim(get_parameter_value($params,'name'));
	$value = trim(get_parameter_value($params,'value'));
	$top = trim(get_parameter_value($params,'top'));
	$type = trim(get_parameter_value($params,'type'));
	$required = cms_to_bool(get_parameter_value($params,'required'));
	if( !$name ) return;

	$name = $prefix.$name;
	$filepicker = \cms_utils::get_filepicker_module();
	if( !$filepicker ) return;

	$profile = $filepicker->get_profile_or_default($profile_name);
	$parms = [];
	if( $top ) $parms['top'] = $top;
	if( $type ) $parms['type'] = $type;
	if( count($parms) ) $profile = $profile->overrideWith( $parms );

	// todo: something with required.
	$out = $filepicker->get_html( $name, $value, $profile, $required );
	if( isset($params['assign']) ) {
		$template->assign( $params['assign'], $out );
	} else {
		return $out;
	}
}
