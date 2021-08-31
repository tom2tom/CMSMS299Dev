<?php
/*
Plugin to retrieve a nested list of recently-updated site pages (max 100 pages)
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\SingleItem;
use CMSMS\UserParams;

function smarty_function_recently_updated($params, $template)
{
	if( !empty($params['number']) ) $number = min(100, max(1, (int) $params['number']));
	else $number = 10;

	if( !empty($params['leadin']) ) $leadin = $params['leadin'];
	else $leadin = lang_by_realm('layout','modified').': ';

	$showtitle = cms_to_bool($params['showtitle'] ?? true);
	$dateformat = trim($params['dateformat'] ?? '');
	if( !$dateformat ) {
		if( AppState::test(AppState::ADMIN_PAGE) ) {
			$userid = get_userid(false);
			$dateformat = UserParams::get_for_user($userid, 'date_format_string');
		}
	}
	if( !$dateformat ) {
		$dateformat = AppParams::get('defaultdateformat', 'd.m.y h:m');
	}
	$css_class = $params['css_class'] ?? '';

	if( $css_class ) {
		$output = '<div class="'.$css_class.'"><ul>';
	}
	else {
		$output = '<ul>';
	}

	$db = SingleItem::Db();
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
	$hm = SingleItem::App()->GetHierarchyManager();
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
		$output .= date($dateformat, strtotime($updated_page['modified_date']));
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
	echo <<<'EOS'
<p>Authors: Elijah Lofgren &lt;elijahlofgren@elijahlofgren.com&gt; Olaf Noehring &lt;http://www.team-noehring.de&gt;</p>
<p>Change History:</p>
<ul>
 <li>added new parameters:<br />
  &lt;leadin&gt;. The contents of leadin will be shown before the modified date. Default is &lt;Modified:&gt;<br />
  $showtitle='true' - if non-falsy, the title attribute of the page will be shown if it exists<br />
  css_class<br />
  dateformat - default is the system setting or d.m.y h:m, use the (PHP date()) format you want
 </li>
</ul>
EOS;
}
/*
function smarty_cms_help_function_recently_updated()
{
	echo lang_by_realm('tags', 'help_generic', 'This plugin does ...', 'recently_updated ...', <<<'EOS'
<li>number</li>
<li>leadin</li>
<li>showtitle</li>
<li>dateformat</li>
<li>css_class</li>
EOS
	);
}
*/
