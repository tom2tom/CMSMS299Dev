<?php
#Methods for modules to do miscellaneous functions
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

namespace CMSMS\module_support;

/**
 * Miscellaneous methods for modules.
 *
 * @internal
 * @since   1.0
 * @package CMS
 * @license GPL
 */
/**
 * @param $modinst the module-object
 * @return string
 */
function GetAbout($modinst) : string
{
	$str = '';
	if (($val = $modinst->GetAuthor())) {
		$str .= '<br />'.lang('author').': ' . $val;
		if (($val = $modinst->GetAuthorEmail())) $str .= ' &lt;' . $val . '&gt;';
		$str .= '<br />';
	}
	$str .= '<br />'.lang('version').': ' .$modinst->GetVersion() . '<br />';

	if (($val = $modinst->GetChangeLog())) {
		$str .= '<br />'.lang('changehistory').':<br />';
		$str .= $val . '<br />';
	}
	return $str;
};

/**
 * @param $modinst the module-object
 * @return string
 */
function GetHelpPage($modinst) : string
{
	ob_start();
	echo $modinst->GetHelp();
	$str = ob_get_clean();
	$dependencies = $modinst->GetDependencies();
	if ($dependencies) {
		$str .= '<h3>'.lang('dependencies').'</h3>';
		$str .= '<ul>';
		foreach ($dependencies as $dep => $ver) {
			$str .= '<li>';
			$str .= $dep.' =&gt; '.$ver;
			$str .= '</li>';
		}
		$str .= '</ul>';
	}

	$paramarray = $modinst->GetParameters();
	if ($paramarray) {
		$str .= '<h3>'.lang('parameters').'</h3>';
		$str .= '<ul>';
		foreach ($paramarray as $oneparam) {
			$str .= '<li>';
			$help = '';
			if ($oneparam['optional'] == true) $str .= '<em>(optional)</em> ';
			if( isset($oneparam['help']) ) $help = $oneparam['help'];
			$str .= $oneparam['name'].'="'.$oneparam['default'].'" - '.$help.'</li>';
		}
		$str .= '</ul>';
	}
	return $str;
};
