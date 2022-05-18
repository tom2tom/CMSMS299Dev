<?php
/*
Plugin to retrieve a nested list of recently-updated site pages (max 100 pages)
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

use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\Lone;
use CMSMS\UserParams;
use CMSMS\Utils;

function smarty_function_recently_updated($params, $template)
{
	if( !empty($params['number']) ) $number = min(100, max(1, (int) $params['number']));
	else $number = 10;

	if( !empty($params['leadin']) ) $leadin = $params['leadin'];
	else $leadin = _ld('layout','modified').': ';

	$showtitle = cms_to_bool($params['showtitle'] ?? true);
	$format = trim($params['dateformat'] ?? '');
	if( !$format ) {
		if( AppState::test(AppState::ADMIN_PAGE) ) {
			$userid = get_userid(false);
			$format = UserParams::get_for_user($userid, 'date_format');
		}
	}
	if( !$format ) {
		$format = AppParams::get('date_format', 'Y-m-d');
	}
	if( strpos($format, 'timed') !== false ) {
		$format = str_replace(['timed', '  '], ['', ' '], $format);
		//ensure time is displayed
		if( strpos($format, '%') !== false ) {
			if( !preg_match('/%[HIklMpPrRSTXzZ]/', $format) ) {
				if( strpos($format, '-') !== false || strpos($format, '/') !== false ) {
					$format .= ' %k:%M';
				}
				else {
					$format .= ' %l:%M %P';
				}
			}
		}
		elseif( !preg_match('/(?<!\\\\)[aABgGhHisuv]/', $format) ) {
			if( strpos($format, '-') !== false || strpos($format, '/') !== false ) {
				$format .= ' H:i';
			}
			else {
				$format .= ' g:i a';
			}
		}
	}

	$css_class = $params['css_class'] ?? '';

	if( $css_class ) {
		$output = '<div class="'.$css_class.'"><ul>';
	}
	else {
		$output = '<ul>';
	}

	$db = Lone::get('Db');
	// Get list of most recently updated pages excluding the home page
	$sql = 'SELECT * FROM '.CMS_DB_PREFIX."content
WHERE (type='content' OR type='link') AND default_content!=1 AND active=1 AND show_in_menu=1
ORDER BY IF(modified_date, modified_date, create_date) DESC LIMIT ".((int)$number);
	$rst = $db->execute($sql);
	if( !$rst ) {
		$output = 'DB error: '. $db->errorMsg().'<br />';
		// @todo: throw an exception here  - trigger_error()
	if( !empty($params['assign']) ) {
			$template->assign(trim($params['assign']), $output);
			return '';
		}
		return $output;
	}
	$hm = cmsms()->GetHierarchyManager();
	while( $rst && $updated_page = $rst->FetchRow() ) {
		$curnode = $hm->find_by_tag('id', $updated_page['content_id']);
		$curcontent = $curnode->getContent();
		$output .= '<li>';
		$output .= '<a href="'.$curcontent->GetURL().'">'.$updated_page['content_name'].'</a>';
		if( $showtitle && !empty($updated_page['titleattribute']) ) {
			$output .= '<br />';
			$output .= $updated_page['titleattribute'];
		}
		$output .= '<br />';
		$output .= $leadin;
		$datevar = strtotime($updated_page['modified_date']);
		if( strpos($format, '%') !== false ) {
			$output .= Utils::dt_format($datevar, $format);
		}
		else {
			$output .= date($format, $datevar);
		}
		$output .= '</li>';
	}
	$rst->Close();
	$output .= '</ul>';
	if( $css_class ) $output .= '</div>';
	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $output);
		return '';
	}
	return $output;
}

function smarty_cms_about_function_recently_updated()
{
	echo '<p>Authors: Elijah Lofgren &lt;elijahlofgren@elijahlofgren.com&gt; Olaf Noehring &lt;http://www.team-noehring.de&gt;</p>
<p>Change History:</p>
<ul>
 <li>Added optional parameters:<br />
  leadin<br />
  $showtitle<br />
  css_class<br />
  dateformat
 </li>
 <li>Dec 2021<ul>
  <li>Use site setting \'date_format\' if no \'format\' parameter is supplied</li>
  <li>Support \'timed\' in the format parameter</li>
  <li>If appropriate, generate output using replacement for deprecated strftime()</li>
  </ul></li>
</ul>';
}

function smarty_cms_help_function_recently_updated()
{
	echo _ld('tags', 'help_generic',
	'This plugin retrieves a nested list of recently-updated site pages',
	'recently_updated ...',
	'<li>number: Number of returned pages. Default 10</li>
<li>leadin: Text displayed before the modified date. Default \'Modified:\' (translated)</li>
<li>showtitle: If it exists, display the title attribute of the page. Default true</li>
<li>css_class: Name of class(es) to be applied to a &lt;div/&gt; enclosing the results. Default empty</li>
<li>dateformat: PHP date() and/or strftime()-compatible format for displayed modification times. It may be, or include, the special-case \'timed\'. Default site setting</li>'
	);
}