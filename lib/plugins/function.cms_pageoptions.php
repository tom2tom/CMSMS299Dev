<?php
/*
Plugin to generate the contents for a page-selector element
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

function smarty_function_cms_pageoptions($params, $template)
{
	$numpages = (int)($params['numpages'] ?? 0);
	if( $numpages < 1 ) return '';
	$i = (int)($params['curpage'] ?? 1);
	$curpage = max(1, min($numpages, $i));
	$i = (int)($params['surround'] ?? 3);
	$surround = max(1, min(20, $i));
	$elipsis = $params['elipsis'] ?? '&#8230;'; // OR &hellip; OR ...
	$bare = cms_to_bool($params['bare'] ?? false);
	$list = [];

	for( $i = 1, $m = min($surround, $numpages); $i <= $m; $i++ ) {
		$list[] = $i;
	}

	$x1 = max(1, (int)($curpage - $surround/2));
	$x2 = min($numpages, (int)($curpage + $surround/2));
	for( $i = $x1; $i <= $x2; $i++ ) {
		$list[] = (int)$i;
	}

	for( $i = max(1,$numpages - $surround); $i <= $numpages; $i++ ) {
		$list[] = $i;
	}

	$list = array_unique($list);
	sort($list);

	if( $bare ) {
		$out = $list;
		if( $elipsis ) {
			$out = [];
			for( $i = 1, $n = count($list); $i < $n; $i++ ) {
				if( $list[$i-1] != $list[$i] - 1 ) $out[] = $elipsis;
				$out[] = $list[$i];
			}
		}
	}
	else {
		$out = '';
		$fmt = '<option value="%d">%s</option>';
		$fmt2 = '<option value="%d" selected="selected">%s</option>';
		foreach( $list as $pagenum ) {
			if( $pagenum == $curpage ) {
				$out .= sprintf($fmt2, $pagenum, $pagenum);
			}
			else {
				$out .= sprintf($fmt, $pagenum, $pagenum);
			}
		}
	}
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $out);
		return '';
	}
	return $out;
}
/*
function smarty_cms_about_function_cms_pageoptions()
{
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004',
	'<li>detail</li>' ... OR '<li>'._la('none').'</li>'
	);
}
*/
function smarty_cms_help_function_cms_pageoptions()
{
	echo _ld('tags', 'help_generic',
	'This plugin generates the content for a page-selector element',
	'cms_pageoptions ...',
	'<li>numpages: Number of wanted pages > 0</li>
<li>curpage: Page id of the page to be \'focused\'</li>
<li>surround: Number of wanted pages surrounding the current page 3..20</li>
<li>elipsis: Indicator of omitted text Default &amp;#8230;</li>
<li>bare: Whether to return data array or a html string for a selector Default false</li>'
	);
}