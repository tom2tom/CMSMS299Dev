<?php
/*
ContentManger module action: ajax_get_content
Copyright (C) 2014-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use ContentManager\BulkOperations;
use ContentManager\ContentListBuilder;
use ContentManager\Utils as ManagerUtils;
use CMSMS\FormUtils;
use CMSMS\NlsOperations;
use CMSMS\UserParams;
use CMSMS\Utils;

if (!empty($firstlist)) {
	$ajax = false;
	//and we'll use the template initiated upstream
	$tpl->assign('pattern', '');
} else {
	// we're doing an ajax-refresh, not initial display via defaultadmin action
	//CHECKME moduleinterface used for ajax? if( !$this->CheckContext() ) exit;
	//if( some worthy test fails ) exit;
	// no permissions checks here.

	$handlers = ob_list_handlers();
	for ($cnt = 0,$n = count($handlers); $cnt < $n; ++$cnt) {
		ob_end_clean();
	}

	$tpl = $smarty->createTemplate($this->GetTemplateResource('get_content.tpl')); //,null,null,$smarty );
	$ajax = true;

	if (empty($params['search'])) {
		$tpl->assign('pattern', ''); //no fuzzy-search, or cancel prior one
	} else {
		// supplied match-char(s)
		$tpl->assign('pattern', $params['search']); //TODO sanitizeVal() etc
		//from https://codereview.stackexchange.com/questions/23899/faster-javascript-fuzzy-string-matching-function
		$arr = str_split($params['search']);
		$t = '/'.$arr[0];
		unset($arr[0]);
		$rx = '[^ ]*? ';
		$patn = array_reduce($arr, function($m, $c) use ($rx) {
			if ($c != '/') {
				$rx[2] = $c;
				$rx[6] = $c;
				return $m . $rx;
			} else {
				return $m .'[^\/]*?\/';
			}
		}, $t);
		$patn .= '/i';
	}
}

$pmanage = $this->CheckPermission('Manage All Content'); // TODO etc e.g. Modify Pages ...
$padd = $pmanage || $this->CheckPermission('Add Pages');
$pdel = $pmanage || $this->CheckPermission('Remove Pages');
$tpl->assign('can_manage_content', $pmanage)
	->assign('can_reorder_content', $pmanage)
	->assign('can_add_content', $padd)
	->assign('direction', NlsOperations::get_language_direction()); //'ltr' or 'rtl'

$themeObject = Utils::get_theme_object();
$builder = new ContentListBuilder($this);
$modname = $this->GetName();

try {
	// load all the content that this user can display...
	// organize it into a tree
	if (!isset($patn)) {
		$curpage = (isset($_SESSION[$modname.'_curpage']) && !isset($params['seek'])) ? (int)$_SESSION[$modname.'_curpage'] : 1;
		if (isset($params['curpage'])) {
			$curpage = (int)$params['curpage'];
		}
		$filter = UserParams::get($modname.'_userfilter');
		if ($filter) {
			$filter = unserialize($filter);
			$builder->set_filter($filter);
		}
		$tpl->assign('have_filter', is_object($filter))
			->assign('filter', $filter);
	} else {
		$curpage = 1;
		$filter = null;
		$tpl->assign('have_filter', false);
	}

	//
	// build the display
	//
	$tpl->assign('prettyurls_ok', $builder->pretty_urls_configured());

	if (isset($params['setoptions'])) {
		UserParams::set($modname.'_pagelimit', (int)$params['pagelimit']);
	}
	$pagelimit = UserParams::get($modname.'_pagelimit', 100);

	$builder->set_pagelimit($pagelimit);
	if (isset($params['seek']) && $params['seek'] != '') {
		$builder->seek_to((int)$params['seek']);
	} else {
		$builder->set_page($curpage);
	}

	$editinfo = $builder->get_content_list();
	$npages = $builder->get_numpages();
	$pagelist = [];
	for ($i = 0; $i < $npages; ++$i) {
		$pagelist[$i + 1] = $i + 1;
	}

	$tpl->assign('indent', !$filter && UserParams::get('indent', 1));
	$locks = $builder->get_locks();
	$have_locks = ($locks) ? 1 : 0;

	$tpl->assign('locking', ManagerUtils::locking_enabled())
		->assign('have_locks', $have_locks)
		->assign('pagelimit', $pagelimit)
		->assign('pagelimits', [10 => 10, 25 => 25, 100 => 100, 250 => 250, 500 => 500])
		->assign('pagelist', $pagelist)
		->assign('curpage', $builder->get_page())
		->assign('npages', $npages)
		->assign('multiselect', $builder->supports_multiselect())
		->assign('columns', $builder->get_display_columns());
/*
	$url = $this->create_action_url($id,'ajax_get_content',['forjs'=>1,CMS_JOB_KEY=>1]);
	$tpl->assign('ajax_get_content_url',$url)
		->assign('settingsicon',cms_join_path(__DIR__,'images','settings'));
*/
	if (ManagerUtils::get_pagenav_display() == 'title') {
		$tpl->assign('colhdr_page', $this->Lang('colhdr_pagetitle'))
			->assign('coltitle_page', $this->Lang('coltitle_name'));
	} else {
		$tpl->assign('colhdr_page', $this->Lang('colhdr_menutext'))
			->assign('coltitle_page', $this->Lang('coltitle_menutext'));
	}

	if ($editinfo) {
		$url = $this->create_action_url($id, 'defaultadmin', ['moveup' => 'XXX']);
		$t = $this->Lang('prompt_page_sortup');
		$icon = $themeObject->DisplayImage('icons/system/arrow-u', $t, '', '', 'systemicon');
		$linkup = '<a href="'.$url.'" class="page_sortup" accesskey="m">'.$icon.'</a>'.PHP_EOL;

		$url = $this->create_action_url($id, 'defaultadmin', ['movedown' => 'XXX']);
		$t = $this->Lang('prompt_page_sortdown');
		$icon = $themeObject->DisplayImage('icons/system/arrow-d', $t, '', '', 'systemicon');
		$linkdown = '<a href="'.$url.'" class="page_sortdown" accesskey="m">'.$icon.'</a>'.PHP_EOL;

		$t = $this->Lang('prompt_page_view');
		$icon = $themeObject->DisplayImage('icons/system/view', $t, '', '', 'systemicon');
		$linkview = '<a target="_blank" href="XXX" class="page_view" accesskey="v">'.$icon.'</a>'.PHP_EOL;

		$url1 = $this->create_action_url($id, 'editcontent', ['content_id' => 'XXX']);
		$t = $this->Lang('prompt_page_edit');
		$icon = $themeObject->DisplayImage('icons/system/edit', $t, '', '', 'systemicon page_edit');
		$linkedit = '<a href="'.$url1.'" class="page_edit" accesskey="e" data-cms-content="XXX">'.$icon.'</a>'.PHP_EOL;

		$url = str_replace('XXX', '%s', $url1).'&m1_steal=1'; //sprintf template
		$tpl->assign('stealurl', $url);

		if ($padd) {
			$url = str_replace('content_id', 'clone_id', $url1);
			$t = $this->Lang('prompt_page_copy');
			$icon = $themeObject->DisplayImage('icons/system/copy', $t, '', '', 'systemicon page_copy');
			$linkcopy = '<a href="'.$url.'" accesskey="o">'.$icon.'</a>'.PHP_EOL;

			$url = $this->create_action_url($id, 'editcontent', ['parent_id' => 'XXX']);
			$t = $this->Lang('prompt_page_addchild');
			$icon = $themeObject->DisplayImage('icons/system/newobject', $t, '', '', 'systemicon page_addchild');
			$linkchild = '<a href="'.$url.'" class="page_edit" accesskey="a">'.$icon.'</a>'.PHP_EOL;
		}

		if ($pdel) {
			$url = $this->create_action_url($id, 'defaultadmin', ['delete' => 'XXX']);
			$t = $this->Lang('prompt_page_delete');
			$icon = $themeObject->DisplayImage('icons/system/delete', $t, '', '', 'systemicon page_delete');
			$linkdel = '<a href="'.$url.'" class="page_delete" accesskey="r">'.$icon.'</a>'.PHP_EOL;
		}
		$now = time();
		$userid = get_userid();
		$menus = [];
		foreach ($editinfo as $i => &$row) {
			//TODO filter rows downstream, instead of removal here
			if (isset($patn)) { //doing a fuzzy search
				$keep = false;
				foreach (['page', 'title', 'menutext', 'alias', 'url'] as $t) {
					if (!empty($row[$t]) && preg_match($patn, $row[$t])) {
						$keep = true;
						break;
					}
				}
				if (!$keep) {
					unset($row);
					unset($editinfo[$i]);
					continue;
				}
			}

			$acts = [];
			$rid = $row['id'];

			if (isset($row['lock'])) {
				$obj = $row['lock'];
				$locker = $obj['uid'];
				if ($locker == $userid) {
					unset($row['lock'], $row['lockuser']);
				} else {
					$row['lock'] = ($obj['expires'] < $now) ? 1 : -1;
				}
			}

			if (isset($row['move'])) {
				if ($row['move'] == 'up') {
					$acts[] = ['content' => str_replace('XXX', $rid, $linkup)];
				} elseif ($row['move'] == 'down') {
					$acts[] = ['content' => str_replace('XXX', $rid, $linkdown)];
				} elseif ($row['move'] == 'both') {
					$acts[] = ['content' => str_replace('XXX', $rid, $linkup)];
					$acts[] = ['content' => str_replace('XXX', $rid, $linkdown)];
				}
			}
			if ($row['viewable']) {
				$acts[] = ['content' => str_replace('XXX', $row['view'], $linkview)];
			}
			if ($row['can_edit']) {
				$acts[] = ['content' => str_replace('XXX', $rid, $linkedit)];
			}
			if ($padd) {
				if ($row['copy']) {
					$acts[] = ['content' => str_replace('XXX', $rid, $linkcopy)];
				}
				//no downstream check for add-child
				$acts[] = ['content' => str_replace('XXX', $rid, $linkchild)];
			}
			if ($pdel && $row['can_delete'] && $row['delete']) {
				$acts[] = ['content' => str_replace('XXX', $rid, $linkdel)];
			}
			$menus[] = FormUtils::create_menu($acts, ['id' => 'Page'.$rid]);
		}
		unset($row);

		$tpl->assign('content_list', $editinfo)
			->assign('menus', $menus);
	}

	if (!$editinfo && ($filter || !empty($patn))) {
		$tpl->assign('error', $this->Lang('err_nomatchingcontent'));
	}

	if ($pmanage) {
		BulkOperations::register_function($this->Lang('bulk_active'), 'active');
		BulkOperations::register_function($this->Lang('bulk_inactive'), 'inactive');
		BulkOperations::register_function($this->Lang('bulk_showinmenu'), 'showinmenu');
		BulkOperations::register_function($this->Lang('bulk_hidefrommenu'), 'hidefrommenu');
		BulkOperations::register_function($this->Lang('bulk_cachable'), 'setcachable');
		BulkOperations::register_function($this->Lang('bulk_noncachable'), 'setnoncachable');
		BulkOperations::register_function($this->Lang('bulk_changeowner'), 'changeowner');
		BulkOperations::register_function($this->Lang('bulk_setstyles'), 'setstyles');
		BulkOperations::register_function($this->Lang('bulk_settemplate'), 'settemplate');
	}

	if ($pmanage || ($this->CheckPermission('Remove Pages') && $this->CheckPermission('Modify Any Page'))) {
		BulkOperations::register_function($this->Lang('bulk_delete'), 'delete');
	}

	$bulks = BulkOperations::get_operation_list();
	if ($bulks) {
		$tpl->assign('bulk_options', $bulks);
	}

	if ($ajax) {
		$tpl->display();
		exit;
	}
} catch (Throwable $t) {
	debug_to_log($t);
	echo '<div class="error">'.$t->getMessage().'</div>';
	if ($ajax) {
		exit;
	}
}
