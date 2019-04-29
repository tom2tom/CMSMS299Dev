<?php
# Ajax processor to populate stylesheets list
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\FormUtils;
use CMSMS\LockOperations;
use CMSMS\StylesheetOperations;

$CMS_ADMIN_PAGE = 1;

$handlers = ob_list_handlers();
for( $i = 0, $n = count($handlers); $i < $n; ++$i ) { ob_end_clean(); }

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

$userid = get_userid();
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$smarty = CmsApp::get_instance()->GetSmarty();

cleanArray($_REQUEST);

try {
	$css_query = new CmsLayoutStylesheetQuery(); //$filter);
	$sheetslist = $css_query->GetMatches();
	if( $sheetslist ) {
		$themeObject = cms_utils::get_theme_object();
		$u = 'editstylesheet.php'.$urlext.'&css=XXX';
		$t = lang_by_realm('layout','title_edit_stylesheet');
		$icon = $themeObject->DisplayImage('icons/system/edit', $t, '', '', 'systemicon');
		$linkedit = '<a href="'.$u.'" class="edit_css" data-css-id="XXX">'.$icon.'</a>'."\n";

//		$u = ibid
		$t = lang_by_realm('layout','title_steal_lock');
		$icon = $themeObject->DisplayImage('icons/system/permissions', $t, '', '', 'systemicon');
		$linksteal = '<a href="'.$u.'" class="steal_css_lock" data-css-id="XXX" accesskey="e">'.$icon.'</a>'."\n";

		$u = 'stylesheetoperations.php'.$urlext.'&op=copy&css=XXX';
		$t = lang_by_realm('layout','title_copy_stylesheet');
		$icon = $themeObject->DisplayImage('icons/system/copy', $t, '', '', 'systemicon');
		$linkcopy = '<a href="'.$u.'" class="copy_css" data-css-id="XXX">'.$icon.'</a>'."\n";

		$u = 'stylesheetoperations.php'.$urlext.'&op=prepend&css=XXX';
		$t = lang_by_realm('layout','title_prepend_stylesheet');
		$icon = $themeObject->DisplayImage('icons/extra/prepend', $t, '', '', 'systemicon');
		$linkprepend = '<a href="'.$u.'" class="prepend_css" data-css-id="XXX">'.$icon.'</a>'."\n";

		$u = 'stylesheetoperations.php'.$urlext.'&op=append&css=XXX';
		$t = lang_by_realm('layout','title_append_stylesheet');
		$icon = $themeObject->DisplayImage('icons/extra/append', $t, '', '', 'systemicon');
		$linkappend = '<a href="'.$u.'" class="append_css" data-css-id="XXX">'.$icon.'</a>'."\n";

		$u = 'stylesheetoperations.php'.$urlext.'&op=replace&css=XXX';
		$t = lang_by_realm('layout','title_replace_stylesheet');
		$icon = $themeObject->DisplayImage('icons/extra/replace', $t, '', '', 'systemicon');
		$linkreplace = '<a href="'.$u.'" class="replace_css" data-css-id="XXX">'.$icon.'</a>'."\n";

		$u = 'stylesheetoperations.php'.$urlext.'&op=remove&css=XXX';
		$t = lang_by_realm('layout','title_remove_stylesheet');
		$icon = $themeObject->DisplayImage('icons/extra/removeall', $t, '', '', 'systemicon');
		$linkremove = '<a href="'.$u.'" class="remove_css" data-css-id="XXX">'.$icon.'</a>'."\n";

		$u = 'stylesheetoperations.php'.$urlext.'&op=delete&css=XXX';
		$t = lang_by_realm('layout','title_delete_stylesheet');
		$icon = $themeObject->DisplayImage('icons/system/delete', $t, '', '', 'systemicon');
		$linkdel = '<a href="'.$u.'" class="del_css" data-css-id="XXX">'.$icon.'</a>'."\n";

		$now = time();
		$menus = [];
		for( $i = 0, $n = count($sheetslist); $i < $n; ++$i ) {
			$acts = [];
			$sheet = $sheetslist[$i];
			$sid = $sheet->get_id();
			if( !$sheet->locked() ) {
				$acts[] = ['content'=>str_replace('XXX', $sid, $linkedit)];
				$acts[] = ['content'=>str_replace('XXX', $sid, $linkcopy)];
				$acts[] = ['content'=>str_replace('XXX', $sid, $linkprepend)];
				$acts[] = ['content'=>str_replace('XXX', $sid, $linkappend)];
				$acts[] = ['content'=>str_replace('XXX', $sid, $linkreplace)];
				$acts[] = ['content'=>str_replace('XXX', $sid, $linkremove)];
				$acts[] = ['content'=>str_replace('XXX', $sid, $linkdel)];
			}
			else {
				$lock = $sheet->get_lock();
				if( $lock['expires'] < $now ) {
					$acts[] = ['content'=>str_replace('XXX', $sid, $linksteal)];
				}
			}
			if( $acts ) {
				$menus[] = FormUtils::create_menu($acts, ['id'=>'Stylesheet'.$sid, 'class'=>CMS_POPUPCLASS]);
			}
		}

		$locks = LockOperations::get_locks('stylesheet');

		$smarty->assign('stylesheets',$sheetslist)
		 ->assign('menus1',$menus)
		 ->assign('have_css_locks',(($locks) ? count($locks) : 0))
		 ->assign('lock_timeout', cms_siteprefs::get('lock_timeout'))
		 ->assign('manage_stylesheets',check_permission($userid,'Manage Stylesheets'));
	}
	else {
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT EXISTS (SELECT 1 FROM '.CMS_DB_PREFIX.StylesheetOperations::TABLENAME.')';
		if( $db->GetOne($query) ) {
			$smarty->assign('stylesheets',false); //signal rows exist, but none matches
		}
	}

	$extras = [CMS_SECURE_PARAM_NAME => $_SESSION[CMS_USER_KEY]];

	$smarty->assign('urlext',$urlext)
	 ->assign('extraparms',$extras);
	$smarty->display('ajaxget_stylesheets.tpl');
}
catch( Exception $e ) {
	echo '<div class="error">'.$e->GetMessage().'</div>';
}
exit;
