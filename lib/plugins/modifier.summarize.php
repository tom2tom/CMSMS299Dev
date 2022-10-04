<?php
/*
Plugin to reduce a string to (at most) the specified number of words
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

/**
 * Smarty plugin
 * Type:    modifier
 * Name:    summarize
 * Purpose: returns desired amount of words from the full string
 *        	ideal for article text, etc.
 * Author:  MarkS, AKA Skram, mark@mark-s.net
 *        	http://dev.cmsmadesimple.org/users/marks/
 */
function smarty_modifier_summarize($string, $numwords = 5, $etc = '&#8230;') // OR &hellip; OR ...
{
	$tmp = explode(' ', strip_tags($string));
	$stringarray = [];

	for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
		if( $tmp[$i] != '' ) $stringarray[] = $tmp[$i];
	}

	if( $numwords >= count($stringarray) ) {
		return $string;
	}

	$tmp = array_slice($stringarray, 0, $numwords);
	$tmp = implode(' ', $tmp).$etc;
	return $tmp;
}
/*
function smarty_cms_about_modifier_summarize()
{
	echo _ld('tags', 'about_generic', 'Skram &lt;mark@mark-s.net&gt; 2012',
	'<li>detail</li>' ... OR '<li>'.lang('none').'</li>'
	);
}
*/
function smarty_cms_help_modifier_summarize()
{
	echo _ld('tags', 'help_generic2',
	'This plugin reduces a string variable to (at most) the specified number of words',
	'$somevar|summarize:3}<br>{$somevar|summarize:3:\'...\'',
	'<li>first, the maximum number of words wanted (<em>default 5</em>)</li>
<li>second, the ellipsis-indicator string to be appended (<em>default &amp;#8230;</em>)</li>'
	);
}
