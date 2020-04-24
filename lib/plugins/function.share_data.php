<?php
#Plugin to...
#Copyright (C) 2009-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

function smarty_function_share_data($params, $template)
{
	$dest = trim(strtolower(get_parameter_value($params,'scope','parent')));
	$vars = $params['data']??null;
	$vars = $params['vars']??$vars;
	if( !$vars ) return; // nothing to do.

	if( is_string($vars) ) {
		$t_list = explode(',',$vars);
		$t_list_2 = [];
		foreach( $t_list as $one ) {
			$one = trim($one);
			if( $one ) $t_list_2[] = $one;
		}
		$vars = $t_list_2;
	}

	if( !count($vars) ) return;

	$scope = null;
	$fn = 'assign';
	switch( $dest ) {
	case 'global':
		if( $template instanceof \Smarty ) {
			$scope = $template;
		}
		else {
			$scope = $template->smarty;
		}
		$fn = 'assignGlobal';
		break;

	default: /* parent scope */
		$scope = $template->parent;
		if( !is_object($scope) ) return;
		if( $scope == $template->smarty ) {
			// a bit of a trick... if our parent is the global smarty object
			// we assume we want this variable available through the rest of the templates
			// so we assign it as a global.
			$fn = 'assignGlobal';
		}
		break;
	}

	foreach( $vars as $one ) {
		$var = $template->getVariable($one,null,false,false);
		if( !($var instanceof Smarty_Undefined_Variable) ) {
			$scope->$fn($one,$var->value);
		} else {
			$scope->$fn($one,null);
		} 
	}
}

