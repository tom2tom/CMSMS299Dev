<?php
/*
Plugin to tailor some or all of the content of a string-value.
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

/**
 * Supercharged Smarty escape modifier plugin
 *
 * Type:     modifier
 * Name:     escape
 * Purpose:  Escape the string according to escapement type
 * @link http://smarty.php.net/manual/en/language.modifier.escape.php
 *          escape (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param string
 * @param html|htmlall|htmltiny|url|urlpathinfo|quotes|hex|hexentity|decentity|javascript|nonstd|smartyphp
 * @return string
 *
 * Robert Campbell: change default charset to UTF-8
 */

/*
namespace cms_escape {

function escape_one($string, $_type)
{
	switch ($type) {
	}
}

} // namespace

namespace {
*/
use CMSMS\SingleItem;
use function CMSMS\entitize;
use function CMSMS\specialize;

function smarty_modifier_cms_escape($string, $esc_type = 'html', $char_set = '')
{
	$esc_type = strtolower($esc_type);
/*
	if (strpos($esc_type, ',') !== false) {
		$all = array_map(function($_type) { return trim($_type); }, explode(',', $esc_type));
	}
	else {
		$all = [trim($esc_type)];
	}
	foreach ($all as $_type) {
		$string = cms_escape\\escape_one($string, $_type);
	}
	return $string;
*/
	switch ($esc_type) {
		case 'html':
			if ($char_set) {
				return specialize($string, 0, $char_set);
			}
			return specialize($string);

		case 'htmlall':
			if ($char_set) {
				return entitize($string, 0, $char_set);
			}
			return entitize($string);

		case 'url':
//			return CMSMS\urlencode($string);  // TODO verbatim chars for rfc 3986 {query, fragment}, plus '%', less '?' '&'
			return rawurlencode($string);

		case 'urlpathinfo':
//			return str_replace('%2F', '/', CMSMS\urlencode($string));
			return str_replace('%2F', '/', rawurlencode($string));

		case 'quotes':
			// escape unescaped single quotes
			return preg_replace("%(?<!\\\\)'%", "\\'", $string);

		case 'hex':
			// escape every character to hex
			$_res = '';
			for ($_i = 0, $_len = strlen($string); $_i < $_len; $_i++) {
				$_res .= '%' . bin2hex($string[$_i]);
			}
			return $_res;

		case 'hexentity':
			$_res = '';
			for ($_i = 0, $_len = strlen($string); $_i < $_len; $_i++) {
				$_res .= '&#x' . bin2hex($string[$_i]) . ';';
			}
			return $_res;

		case 'decentity':
			$_res = '';
			for ($_i = 0, $_len = strlen($string); $_i < $_len; $_i++) {
				$_res .= '&#' . ord($string[$_i]) . ';';
			}
			return $_res;

		case 'javascript':
			// escape quotes, backslashes, newlines, etc
			return strtr($string, ['\\'=>'\\\\',"'"=>"\\'",'"'=>'\\"',"\r"=>'\\r',"\n"=>'\\n','</'=>'<\/']);

		case 'mail':
			// safer way to display e-mail address on a web page
			return str_replace(['@', '.'], [' [AT] ', ' [DOT] '], $string);

		case 'nonstd':
			// decimal-escape chars >= 126
			$_res = '';
			for ($_i = 0, $_len = strlen($string); $_i < $_len; $_i++) {
				$_c = substr($string, $_i, 1);
				$_ord = ord($_c);
				if ($_ord >= 126) {
					$_res .= '&#' . $_ord . ';';
				}
				else {
					$_res .= $_c;
				}
			}
			return $_res;

		case 'htmltiny':
			return str_replace('<', '&lt;', $string);

		case 'smartyphp':
			$smarty = SingleItem::Smarty();
			$ldl = $smarty->left_delimiter;
			$rdl = $smarty->right_delimiter;
			$lsep = '';
			$plims = '~@!%_&;`';
			for ($i = 0, $l = strlen($plims); $i < $l; $i++) {
				$c = $plims[$i];
				if (strpos($ldl, $c) === false && strpos($rdl, $c) === false) {
					$lsep = $rsep = $c;
					break;
				}
			}
			if (!$lsep) return $string; // can't proceed
			$eldl = preg_quote($ldl);
			$erdl = preg_quote($rdl);
			$patn = "{$lsep}{$eldl}\s*/?php\s*{$erdl}{$rsep}i";
			return preg_replace($patn, '', $string);

		case 'textarea':
			$matches = [];
			return preg_replace_callback('~<\s*(/?)\s*(textarea)~i', function($matches) {
				$pre = ($matches[1]) ? '&sol;' : ''; // ?? OR &#47;
				return '&lt;'.$pre.$matches[2];
			}, $string);

		default:
			return $string;
	}
}

function smarty_cms_about_modifier_cms_escape()
{
	echo _ld('tags', 'about_generic', 'Monte Ohrt &lt;monte at ohrt dot com&gt;<br />supplemented by CMSMS-extensions. 2004',
	'<li>change default charset to UTF-8</li>
<li>change html processor to CMSMS\specialize</li>
<li>change htmlall processor to CMSMS\entitize</li>
<li>support Smarty2 php tags removal</li>
<li>support textarea-tag escaping, for e.g. a template to be edited using a textarea</li>'
	);
}

function smarty_cms_help_modifier_cms_escape()
{
	echo _ld('tags', 'help_generic2',
	'This plugin converts some or all of the content of a string variable, to tailor it for its context e.g. URL-capable or more secure',
	'$somevar|cms_escape:\'type\'}<br />{$somevar|cms_escape:\'type\':\'charset\'',
	'<li>type: one of
<ul>
<li>decentity: substitute &#N;</li>
<li>hex: substitute %H</li>
<li>hexentity: substitute &#xH;</li>
<li>html (<em>default</em>): apply CMSMS\specialize</li>
<li>htmlall: apply CMSMS\entitize</li>
<li>htmltiny: substitute &amplt; for &lt;</li>
<li>javascript: escape quotes, backslashes, newlines etc</li>
<li>mail: substitute [AT], [DOT]</li>
<li>nonstd: substitute &#N; for chars &gt;= 126</li>
<li>quotes: escape unescaped single-quotes</li>
<li>smartyphp: remove Smarty-2-compatible {php},{/php} tags (however de-limited)</li>
<li>url: apply rawurlencode to appropriate chars (see rfc3986)</li>
<li>urlpathinfo: apply rawurlencode to appropriate chars except for '/'</li>
</ul>
</li>
<li>charset: the variable\'s encoding (optional, <em>default UTF-8</em>)</li>'
	);
	echo 'See also: <a href="https://www.smarty.net/docs/en/language.modifier.escape.tpl" target="_blank">Smarty native escaping</a>';
}

//} //global namespace
