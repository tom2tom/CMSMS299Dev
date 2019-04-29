<?php
# Ajax processor to populate templates list
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
use CMSMS\TemplateOperations;

$CMS_ADMIN_PAGE = 1;

$handlers = ob_list_handlers();
for( $i = 0, $n = count($handlers); $i < $n; ++$i ) { ob_end_clean(); }

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

$userid = get_userid();
$pmod = check_permission($userid,'Modify Templates');
$padd = $pmod || check_permission($userid,'Add Templates');
$lock_timeout = cms_siteprefs::get('lock_timeout');
$smarty = CmsApp::get_instance()->GetSmarty();
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

$tmp = $_REQUEST['filter'] ?? null; //cannot clean this one

cleanArray($_REQUEST);

try {
	$filter = ($tmp) ? json_decode($tmp,TRUE) : [];
	if( !check_permission($userid,'Modify Templates') ) {
		$filter[] = 'e:'.get_userid(false);
	}

	$smarty->assign('tpl_filter',$filter);

	include __DIR__.DIRECTORY_SEPARATOR.'method.TemplateQuery.php';

	if( $templates ) {
		$themeObject = cms_utils::get_theme_object();

		$u = 'edittemplate.php'.$urlext.'&tpl=XXX';
		$t = lang_by_realm('layout','title_edit_template');
		$icon = $themeObject->DisplayImage('icons/system/edit', $t, '', '', 'systemicon');
		$linkedit = '<a href="'.$u.'" class="edit_tpl" data-tpl-id="XXX">'.$icon.'</a>'."\n";

//		$u = ibid
		$t = lang_by_realm('layout','prompt_steal_lock');
		$icon = $themeObject->DisplayImage('icons/system/permissions', $t, '', '', 'systemicon edit_tpl steal_tpl_lock');
		$linksteal = '<a href="'.$u.'" class="steal_tpl_lock" data-tpl-id="XXX" accesskey="e">'.$icon.'</a>'."\n";

		if( $padd ) {
			$u = 'templateoperations.php'.$urlext.'&op=copy&tpl=XXX';
			$t = lang_by_realm('layout','title_copy_template');
			$icon = $themeObject->DisplayImage('icons/system/copy', $t, '', '', 'systemicon');
			$linkcopy = '<a href="'.$u.'" class="copy_tpl" data-tpl-id="XXX">'.$icon.'</a>'."\n";
		}

		$u = 'templateoperations.php'.$urlext.'&op=applyall&tpl=XXX';
		$t = lang_by_realm('layout','title_apply_template');
		$icon = $themeObject->DisplayImage('icons/extra/applyall', $t, '', '', 'systemicon');
		$linkapply = '<a href="'.$u.'" class="apply_tpl" data-tpl-id="XXX">'.$icon.'</a>'."\n";

		$u = 'templateoperations.php'.$urlext.'&op=replace&tpl=XXX';
		$t = lang_by_realm('layout','title_replace_template');
		$icon = $themeObject->DisplayImage('icons/extra/replace', $t, '', '', 'systemicon');
		$linkreplace = '<a href="'.$u.'" class="replace_tpl" data-tpl-id="XXX">'.$icon.'</a>'."\n";

		$u = 'templateoperations.php'.$urlext.'&op=delete&tpl=XXX';
		$t = lang_by_realm('layout','title_delete_template');
		$icon = $themeObject->DisplayImage('icons/system/delete', $t, '', '', 'systemicon');
		$linkdel = '<a href="'.$u.'" class="del_tpl" data-tpl-id="XXX">'.$icon.'</a>'."\n";

//TODO where relevant, an action to revert template content to type-default

		$patn = CmsLayoutTemplate::CORE;
		$now = time();
		$menus = [];
		for( $i = 0, $n = count($templates); $i < $n; ++$i ) {
			$acts = [];
			$template = $templates[$i];
			$tid = $template->get_id();
			$origin = $template->get_originator();
			$core = $origin == '' || $origin == $patn;

			if( !$lock_timeout || !$template->locked() ) {
				if( $pmod ) { $acts[] = ['content'=>str_replace('XXX', $tid, $linkedit)]; }
				if( $padd ) {
					$acts[] = ['content'=>str_replace('XXX', $tid, $linkcopy)];
				}
				if( $pmod && $core ) { $acts[] = ['content'=>str_replace('XXX', $tid, $linkapply)]; }
	   			if( $pmod && $core ) { $acts[] = ['content'=>str_replace('XXX', $tid, $linkreplace)]; }
			} else {
				$lock = $template->get_lock();
				if( $lock['expires'] < $now ) {
					$acts[] = ['content'=>str_replace('XXX', $tid, $linksteal)];
				}
			}

			if( !$template->get_type_dflt() && !$template->locked() ) {
				if( $pmod || $template->get_owner_id() == get_userid() ) {
					$acts[] = ['content'=>str_replace('XXX', $tid, $linkdel)];
				}
			}

			if( $acts ) {
				$menus[] = FormUtils::create_menu($acts, ['id'=>'Template'.$tid, 'class'=>CMS_POPUPCLASS]);
			}
		}

		$smarty->assign('templates', $templates)
		 ->assign('menus2', $menus);
/*
		 ->assign('tpl_nav', [
			'pagelimit' => $limit,
			'numpages' => $numpages,
			'numrows' => $totalrows,
			'curpage' => (int)($offset / $limit) + 1,
		]);
*/
        $pagerows = 10;
        $navpages = ceil($totalrows / $pagerows);
        if( $navpages > 1 ) {
            $pagelengths = [10=>10];
            $pagerows += $pagerows;
            if( $pagerows < $totalrows ) $pagelengths[20] = 20;
            $pagerows += $pagerows;
            if( $pagerows < $totalrows ) $pagelengths[40] = 40;
            $pagelengths[0] = lang('all');
        }
        $sellength = 10; //OR some $_REQUEST[]

        $smarty->assign('navpages', $navpages)
         ->assign('pagelengths',$pagelengths)
         ->assign('currentlength',$sellength);



	}
	else {
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT EXISTS (SELECT 1 FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.')';
		if( $db->GetOne($query) ) {
			$smarty->assign('templates',false); //signal row(s) exist, but none matches
		}
	}

	$types = CmsLayoutTemplateType::get_all();
	if( $types ) {
		$originators = [];
		$tmp = [];
		$tmp2 = [];
		$tmp3 = [];
		for( $i = 0, $n = count($types); $i < $n; ++$i ) {
			$tmp['t:'.$types[$i]->get_id()] = $types[$i]->get_langified_display_value();
			$tmp2[$types[$i]->get_id()] = $types[$i]->get_langified_display_value();
			$tmp3[$types[$i]->get_id()] = $types[$i];
			if( !isset($originators[$types[$i]->get_originator()]) ) {
				$originators['o:'.$types[$i]->get_originator()] = $types[$i]->get_originator(TRUE);
			}
		}
		$smarty->assign('list_all_types',$tmp3)
		 ->assign('list_types',$tmp2);
	}
	else {
		$smarty->assign('list_all_types',null)
		 ->assign('list_types',null);
	}

	$locks = LockOperations::get_locks('template');
	$selfurl = basename(__FILE__);
	$extras = [CMS_SECURE_PARAM_NAME => $_SESSION[CMS_USER_KEY]];
	$smarty->assign('have_locks',$locks ? count($locks) : 0)
	 ->assign('lock_timeout',$lock_timeout)
	 ->assign('coretypename',CmsLayoutTemplateType::CORE)
	 ->assign('manage_templates',$pmod)
	 ->assign('has_add_right',$padd)
	 ->assign('selfurl',$selfurl)
	 ->assign('urlext',$urlext)
	 ->assign('extraparms',$extras);

	$smarty->display('ajaxget_templates.tpl');
}
catch( Exception $e ) {
	echo '<div class="error">'.$e->GetMessage().'</div>';
}
exit;
