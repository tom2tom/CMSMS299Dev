<?php
/*
Plugin to re-scope specified Smarty variables according to supplied params.
Copyright (C) 2015-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

function smarty_function_share_data($params, $template)
{
	$vars = $params['vars'] ?? $params['data'] ?? null;
	if( !$vars ) return; // nothing to do

	if( is_string($vars) ) {
		$t_list = explode(',', $vars);
		$t_list_2 = [];
		foreach( $t_list as $one ) {
			$one = trim($one);
			if( $one ) $t_list_2[] = $one;
		}
		if( !$t_list_2 ) return;
		$vars = $t_list_2;
	}

	$dest = strtolower(trim($params['scope'] ?? 'parent'));
	switch( $dest ) {
	case 'global':
		if( $template instanceof Smarty ) {
			$scope = $template;
		}
		else {
			$scope = $template->smarty;
		}
		$fn = 'assignGlobal';
		break;

/*	case 'root': TODO N/A
		$scope = ;
		$fn = 'assign';
		break;
*/
	default: // parent scope
		$scope = $template->parent;
		if( !is_object($scope) ) return;
		if( $scope !== $template->smarty ) {
			$fn = 'assign';
		}
		else {
			// if our parent is the global smarty object, assume the
			// caller wants global scope
			$fn = 'assignGlobal';
		}
		break;
	}

	foreach( $vars as $one ) {
		$var = $template->getTemplateVars($one, null, false);
		if( !($var instanceof Smarty_Undefined_Variable) ) {
			$scope->$fn($one, $var->value);
		} else {
			$scope->$fn($one, null);
		}
	}
}
/*
function smarty_cms_about_function_share_data()
{
	$n = _la('none');
	echo _ld('tags', 'about_generic', '2015', "<li>$n</li>");
}
*/
function smarty_cms_help_function_share_data()
{
	echo _ld('tags', 'help_generic', 'This plugin re-scopes specified Smarty variable(s)',
	'share_data ...',
	'<li>vars: comma-separated string, or array, of variable names</li>
<li>data: alias for vars</li>
<li>(optional) scope: parent (default) or  global</li>'
	);
}