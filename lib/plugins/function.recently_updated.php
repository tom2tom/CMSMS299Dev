<?php
#Plugin to...
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

function smarty_function_recently_updated($params, $template)
{
	$number = 10;
	if(!empty($params['number'])) $number = min(100,max(1,(int) $params['number']));

	$leadin = 'Modified: ';
	if(!empty($params['leadin'])) $leadin = $params['leadin'];

	$showtitle='true';
	if(!empty($params['showtitle'])) $showtitle = $params['showtitle'];

	$dateformat = $params['dateformat'] ?? 'd.m.y h:m' ;
	$css_class = $params['css_class'] ?? '' ;

	if (isset($params['css_class'])) {
		$output = '<div class="'.$css_class.'"><ul>';
	}
	else {
		$output = '<ul>';
	}

	$gCms = CmsApp::get_instance();
	$db = $gCms->GetDb();

	// Get list of most recently updated pages excluding the home page
	$q = 'SELECT * FROM '.CMS_DB_PREFIX."content WHERE (type='content' OR type='link')
AND default_content != 1 AND active = 1 AND show_in_menu = 1
ORDER BY modified_date DESC LIMIT ".((int)$number);
	$dbresult = $db->Execute( $q );
	if( !$dbresult ) {
		// @todo: throw an exception here
		return 'DB error: '. $db->ErrorMsg().'<br />';
	}
	$hm = $gCms->GetHierarchyManager();
	while ($dbresult && $updated_page = $dbresult->FetchRow())
	{
		$curnode = $hm->find_by_tag('id',$updated_page['content_id']);
		$curcontent = $curnode->getContent();
		$output .= '<li>';
		$output .= '<a href="'.$curcontent->GetURL().'">'.$updated_page['content_name'].'</a>';
		if ((FALSE == empty($updated_page['titleattribute'])) && ($showtitle=='true')) {
			$output .= '<br />';
			$output .= $updated_page['titleattribute'];
		}
		$output .= '<br />';
		$output .= $leadin;
		$output .= date($dateformat,strtotime($updated_page['modified_date']));
		$output .= '</li>';
	}

	$output .= '</ul>';
	if (isset($params['css_class'])) $output .= '</div>';

	if( isset($params['assign']) ) {
		$template->assign(trim($params['assign']),$output);
		return;
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
  &lt;leadin&gt;. The contents of leadin will be shown left of the modified date. Default is &lt;Modified:&gt;<br />
  $showtitle='true' - if true, the titleattribute of the page will be shown if it exists (true|false)<br />
  css_class<br />
  dateformat - default is d.m.y h:m , use the format you wish (php format)
 </li>
</ul>
EOS;
}
