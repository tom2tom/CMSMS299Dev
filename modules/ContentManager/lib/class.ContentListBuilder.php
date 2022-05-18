<?php
/*
Class for building and managing content lists and their members
Copyright (C) 2013-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
namespace ContentManager;

use CMSMS\LockOperations;
use CMSMS\Lone;
use CMSMS\TemplateOperations;
use CMSMS\Tree;
use CMSMS\UserParams;
use ContentManager; // the module-class
use ContentManager\ContentBase;
use ContentManager\ContentListFilter;
use ContentManager\ContentListQuery;
use ContentManager\Utils;
use Throwable;
use function check_authorship;
use function cmsms;
use function CMSMS\log_info;
use function get_userid;

/**
 * A class for building and managing page/content lists and their members.
 *
 * @final
 */
final class ContentListBuilder
{
	private $_display_columns = [];
	private $_filter = null;
	private $_locks;
	private $_module;
	private $_offset = 0;
	private $_opened_array = [];
	private $_pagelimit = 500;
	private $_pagelist;
	private $_seek_to;
	private $_use_perms = true;
	private $_userid;

	/**
	 * Constructor
	 *
	 * Caches the opened pages, and user id
	 */
	#[\ReturnTypeWillChange]
	public function __construct(ContentManager $mod)
	{
		$this->_module = $mod;
		$this->_userid = get_userid();
		$tmp = UserParams::get('opened_pages');
		if ($tmp) {
			$this->_opened_array = explode(',', $tmp);
		}
	}

	/**
	 * Record the displayable-status of the named column
	 * @param string $column
	 * @param bool $state Default true
	 */
	public function column_state($column, $state = true)
	{
		$this->_display_columns[$column] = $state;
	}

	/**
	 * Expand a section, given a parent page_id. Hence the children (at least) of this page are displayed.
	 */
	public function expand_section($parent_page_id)
	{
		$parent_page_id = (int)$parent_page_id;
		if ($parent_page_id < 1) {
			return;
		}

		$tmp = $this->_opened_array;
		$tmp[] = $parent_page_id;
		asort($tmp);
		$this->_opened_array = array_unique($tmp);
		UserParams::set('opened_pages', implode(',', $this->_opened_array));
	}

	/**
	 * Marks all parent pages as expanded.	Hence all content pages will be displayed.
	 */
	public function expand_all()
	{
		$hm = cmsms()->GetHierarchyManager(); //TODO below find all children better

		// find all the pages (recursively) that have children.
		// anonymous, recursive function.
		$func = function($node) use (&$func) {
			$out = null;
			if ($node->has_children()) {
				$out = [];
				if ($node->get_tag('id')) {
					$out[] = $node->get_tag('id');
				}
				$children = $node->get_children();
				for ($i = 0, $n = count($children); $i < $n; ++$i) {
					$tmp = $func($children[$i]);
					if ($tmp) {
						$out = array_merge($out, $tmp);
					}
				}
				$out = array_unique($out);
			}
			return $out;
		}; // function.

		$this->_opened_array = $func($hm);
		UserParams::set('opened_pages', implode(',', $this->_opened_array));
	}

	/**
	 * Marks all parent pages as collapsed.	Hence no descendant page is visible.
	 */
	public function collapse_all()
	{
		$this->_opened_array = [];
		UserParams::remove('opened_pages');
	}

	/**
	 * Collapse a parent page, hence  its descendant pages are not visible.
	 */
	public function collapse_section($parent_page_id)
	{
		$parent_page_id = (int)$parent_page_id;
		if ($parent_page_id < 1) {
			return false;
		}

		$tmp = [];
		foreach ($this->_opened_array as $one) {
			if ($one != $parent_page_id) {
				$tmp[] = $one;
			}
		}
		asort($tmp);
		$this->_opened_array = array_unique($tmp);
		if ($this->_opened_array) {
			UserParams::set('opened_pages', implode(',', $this->_opened_array));
		} else {
			UserParams::remove('opened_pages');
		}
		return true;
	}

	/**
	 * Set the active state of a page.
	 *
	 * @param int $page_id > 0 or else ignored
	 * @param bool $state
	 * @return boolean indicating success
	 */
	public function set_active($page_id, $state = true)
	{
		$page_id = (int)$page_id;
		if ($page_id < 1) {
			return false;
		}
		if (!$this->_module->CheckPermission('Manage All Content')) {
			return false;
		}

		$contentops = Lone::get('ContentOperations');
		$content = $contentops->LoadEditableContentFromId($page_id);
		if (!$content) {
			return false;
		}

		$content->SetActive((bool)$state);
		$content->Save();
		return true;
	}

	/**
	 * [Un]set the content list filter
	 *
	 * @param mixed $filter optional filter. ContentListFilter | null to invalidate any filter. Default null
	 */
	public function set_filter(ContentListFilter $filter = null)
	{
		$this->_filter = $filter;
	}

	/**
	 * Set the page limit.
	 * This must be called BEFORE get_content_list() is called.
	 *
	 * @param integer The page limit, constrained to 1 .. 500
	 * @return void
	 */
	public function set_pagelimit($n)
	{
		$n = max(1, min(500, (int)$n));
		$this->_pagelimit = $n;
	}

	/**
	 * Get the page limit.
	 *
	 * @return integer
	 */
	public function get_pagelimit()
	{
		return $this->_pagelimit;
	}

	/**
	 * Set the page offset
	 * This must be called before get_content_list() is called.
	 *
	 * @param int page maximum offset
	 */
	public function set_offset($n)
	{
		$n = max(0, (int)$n);
		$this->_offset = $n;
	}

	/**
	 * Get the current offset
	 *
	 * @return integer
	 */
	public function get_offset()
	{
		return $this->_offset;
	}

	public function seek_to($n)
	{
		$n = max(1, (int)$n);
		$this->_seek_to = $n;
	}

	public function set_page($n)
	{
		$n = max(1, (int)$n);
		$this->_offset = $this->_pagelimit * ($n - 1);
	}

	/**
	 * This can be called after the content list is returned as
	 * the offset can be adjusted because of seeking to a content id.
	 */
	public function get_page()
	{
		return (int)($this->_offset / $this->_pagelimit) + 1;
	}

	/**
	 * Get the number of pages.
	 * Can only be called AFTER get_content_list has been called.
	 *
	 * @return integer
	 */
	public function get_numpages()
	{
		if (!is_array($this->_pagelist)) {
			return;
		}
		$npages = (int)(count($this->_pagelist) / $this->_pagelimit);
		if (count($this->_pagelist) % $this->_pagelimit != 0) {
			++$npages;
		}
		return $npages;
	}

	/**
	 * Set the specified page as the default page.
	 */
	public function set_default($page_id)
	{
		$page_id = (int)$page_id;
		if ($page_id < 1) {
			return false;
		}

		if (!$this->_module->CheckPermission('Manage All Content')) {
			return;
		}

		$contentops = Lone::get('ContentOperations');
		$content1 = $contentops->LoadEditableContentFromId($page_id);
		if (!$content1) {
			return false;
		}
		if (!$content1->IsDefaultPossible()) {
			return false;
		}
		if (!$content1->Active()) {
			return false;
		}

		$page_id2 = $contentops->GetDefaultContent();
		if ($page_id === $page_id2) {
			return true;
		}

		$content1->SetDefaultContent(true);
		$content1->Save();

		$content2 = $contentops->LoadEditableContentFromId($page_id2);
		if ($content2) {
			$content2->SetDefaultContent(false);
			$content2->Save();
		}

		return true;
	}

	/**
	 * Move a page up or down wrt its peers.
	 *
	 * @param int $page_id
	 * @param int $direction < 0 indicates up, > 0 indicates down
	 * @return boolean indicating success
	 */
	public function move_content($page_id, $direction)
	{
		$page_id = (int)$page_id;
		if ($page_id < 1) {
			return false;
		}
		$direction = (int)$direction;
		if ($direction == 0) {
			return false;
		}
		$contentops = Lone::get('ContentOperations');

		$test = false;
		if ($this->_module->CheckPermission('Manage All Content')) {
			$test = true;
		} elseif ($this->_module->CheckPermission('Reorder Content') &&
				$contentops->CheckPeerAuthorship($this->_userid, $page_id)) {
			$test = true;
		}

		if (!$test) {
			return false;
		}

		$content = $contentops->LoadEditableContentFromId($page_id);
		if (!$content) {
			return false;
		}

		$content->ChangeItemOrder($direction);
		$contentops->SetAllHierarchyPositions();
		return true;
	}

	/**
	 * Delete a page.
	 *
	 * @param int $page_id
	 * @return mixed error message on failure | null on success
	 */
	public function delete_content($page_id)
	{
		$page_id = (int)$page_id;
		if ($page_id < 1) {
			return $this->_module->Lang('error_invalidpageid');
		}

		if ($this->_module->CheckPermission('Manage All Content')) {
			$test = true;
		} elseif ($this->_module->CheckPermission('Remove Pages') && check_authorship($this->_userid, $page_id)) {
			$test = true;
		} else {
			$test = false;
		}

		if (!$test) {
			return $this->_module->Lang('error_delete_permission');
		}

		$hm = cmsms()->GetHierarchyManager();
		$node = $hm->get_node_by_id($page_id);
		if (!$node) {
			return $this->_module->Lang('error_invalidpageid');
		}
		if ($node->has_children()) {
			return $this->_module->Lang('error_delete_haschildren');
		}

		$contentops = Lone::get('ContentOperations');
		$content = $contentops->LoadEditableContentFromId($page_id);
		if ($content->DefaultContent()) {
			return $this->_module->Lang('error_delete_defaultcontent');
		}

		$parent = $node->get_parent();
		if ($parent) {
			$parent_id = $parent->get_tag('id');
			$childcount = $parent->count_children();
		} else {
			$parent_id = -1;
			$childcount = 0;
		}

		$content->Delete();
		log_info($page_id, 'ContentManager', 'Deleted content page');

		if ($childcount == 1 && $parent_id > -1) {
			$this->collapse_section($parent_id);
		}
		$this->collapse_section($page_id);

		$contentops->SetAllHierarchyPositions();
	}

	public function pretty_urls_configured()
	{
		$config = Lone::get('Config');
		return isset($config['url_rewriting']) && $config['url_rewriting'] != 'none';
	}

	/**
	 * Get the columns that are to be displayed in the content list
	 *
	 * @return array  Each member like colcode => headertype, where headertype is a
	 * string ('icon'|'normal') to indicate how the column header is intended, or
	 * null to indicate that the column should be hidden.
	 */
	public function get_display_columns()
	{
		$mod = $this->_module;
		$flat = $mod->GetPreference('list_visiblecolumns');
		if (!$flat) {
			$flat =
		'expand,icon1,hier,page,alias,friendlyname,template,active,default,modified,actions,multiselect';
		}
		$cols = explode(',', $flat);

		$pall = $mod->CheckPermission('Manage All Content');
		$padd = $pall || $mod->CheckPermission('Add Pages');
		$pdel = $pall || $mod->CheckPermission('Remove Pages');

		$displaycols = []; //populated in the order of displayed columns
		$displaycols['expand'] = (!$this->_filter && in_array('expand', $cols)) ? 'icon' : null;
		$displaycols['icon1'] = in_array('icon1', $cols) ? 'icon' : null;
		$displaycols['hier'] = in_array('hier', $cols) ? 'normal' : null;
		$displaycols['page'] = in_array('page', $cols) ? 'normal' : null;
		$displaycols['alias'] = in_array('alias', $cols) ? 'normal' : null;
		$displaycols['url'] = in_array('url', $cols) ? 'normal' : null;
		$displaycols['friendlyname'] = in_array('friendlyname', $cols) ? 'normal' : null;
		$displaycols['owner'] = in_array('owner', $cols) ? 'normal' : null;
		$displaycols['template'] = in_array('template', $cols) ? 'normal' : null;
		$displaycols['active'] = ($pall && in_array('active', $cols)) ? 'icon' : null;
		$displaycols['default'] = ($pall && in_array('default', $cols)) ? 'icon' : null;
		$displaycols['modified'] = ($pall && in_array('modified', $cols)) ? 'normal' : null;
		$displaycols['actions'] = 'icon';
		$displaycols['multiselect'] = ($pdel && in_array('multiselect', $cols)) ? 'icon' : null;
		// the rest are probably actions-menu items, not displayed columns
		$displaycols['view'] = in_array('view', $cols) ? 'icon' : null;
		$displaycols['edit'] = in_array('edit', $cols) ? 'icon' : null;
		$displaycols['delete'] = ($pdel && in_array('delete', $cols)) ? 'icon' : null;
		$displaycols['copy'] = ($padd && in_array('copy', $cols)) ? 'icon' : null;
		$displaycols['addchild'] = ($padd && in_array('addchild', $cols)) ? 'icon' : null;
		$displaycols['move'] = (in_array('move', $cols) && ($pall || $mod->CheckPermission('Reorder Content'))) ? 'icon' : null;

		foreach ($displaycols as $key => $val) {
			if (isset($this->_display_columns[$key]) && !$this->_display_columns[$key]) {
				$displaycols[$key] = null;
			}
		}

		return $displaycols;
	}

	/**
	 * Get a hash of current page locks.
	 */
	public function get_locks()
	{
//		if( $this->_module->GetPreference('locktimeout') < 1 ) return;
		if (is_array($this->_locks)) {
			return $this->_locks;
		}
		$this->_locks = [];
		$tmp = LockOperations::get_locks('content');
		if ($tmp) {
			foreach ($tmp as $lock_obj) {
				$this->_locks[$lock_obj['oid']] = $lock_obj;
			}
		}
		return $this->_locks;
	}

	/**
	 * Test whether there is any lock.
	 */
	public function have_locks()
	{
		return $this->get_locks() != false;
	}

	/**
	 * Return display-data for viewable/editable content | null
	 */
	public function get_content_list()
	{
		$pagelist = $this->_load_editable_content();
		if ($pagelist) {
			return $this->_get_display_data($pagelist);
		}
	}

	/**
	 * Return whether this content list supports multiselect
	 */
	public function supports_multiselect()
	{
		$cols = $this->get_display_columns();
		return !empty($cols['multiselect']);
	}

	/**
	 * Recursive function to generate a list of all content pages.
	 */
	private function _get_all_pages(Tree $node)
	{
		$out = [];
		if ($node->get_tag('id')) {
			$out[] = $node->get_tag('id');
		}
		if ($node->has_children()) {
			$children = $node->get_children();
			for ($i = 0, $n = count($children); $i < $n; ++$i) {
				$child = $children[$i];
				$tmp = $this->_get_all_pages($child);
				if ($tmp) {
					$out = array_merge($out, $tmp);
				}
			}
		}
		return $out;
	}

	/**
	 * Load all content that the user has access to.
	 */
	private function _load_editable_content()
	{
		/* build a display list:
		 1. add in top level items (items with parent == -1) which cannot be closed
		 2. for each item in opened array
			 for each parent
			  if not in opened array break
			  if got to root, add items children
		 3. reduce list by items we are able to view (author pages)
		*/
		$hm = cmsms()->GetHierarchyManager();
		$display = [];

		// filter the display list by what the user is authorized to view.
		$modify_any_page = $this->_module->CheckPermission('Manage All Content') || $this->_module->CheckPermission('Modify Any Page');
		if ($this->_filter && $modify_any_page) {
			// we display only the pages matching the filter
			$query = new ContentListQuery($this->_filter);
			while (!$query->EOF()) {
				$display[] = $query->GetObject();
				$query->MoveNext();
			}
		} elseif ($this->_use_perms && $modify_any_page) {
			// we can display anything

			$is_opened = function($node, $opened_array) {
				while ($node && $node->get_tag('id') > 0) {
					if ($node && $node->get_tag('id') > 0) {
						if (!in_array($node->get_tag('id'), $opened_array)) {
							return false;
						}
					}
					$node = $node->get_parent();
				}
				return true;
			};

			// add in top level items.
			$children = $hm->get_children();
			if ($children) {
				foreach ($children as $child) {
					$display[] = $child->get_tag('id');
				}
			}

			// add children of opened_array items to the list.
			foreach ($this->_opened_array as $one) {
				$node = $hm->get_node_by_id($one);
				if (!$node) {
					continue;
				}

				if (!$is_opened($node, $this->_opened_array)) {
					continue;
				}
				$display[] = $one;

				$children = $node->get_children();
				if ($children) {
					foreach ($children as $child) {
						$display[] = $child->get_tag('id');
					}
				}
			}
		} else {
			//
			// we can only edit some pages.
			//

/*			for each item
				if in opened array or has no parent add item
				if all parents are opened add item
*/
			$tmplist = Lone::get('ContentOperations')->GetPageAccessForUser($this->_userid);
			$display = [];
			foreach ($tmplist as $item) {
				// get all the parents
				$parents = [];
				$startnode = $node = $hm->get_node_by_id($item);
				while ($node && $node->get_tag('id') > 0) {
					$parents[] = $node->get_tag('id');
					$node = $node->get_parent();
				}
				// start at root
				// push items from list on the stack if they are root, or the previous item is in the opened array.
				$parents = array_reverse($parents);
				for ($i = 0, $n = count($parents); $i < $n; ++$i) {
					if ($i == 0) {
						$display[] = $parents[$i];
						continue;
					}
					if ($i > 0 && in_array($parents[$i - 1], $this->_opened_array) && in_array($parents[$i - 1], $display)) {
						$display[] = $parents[$i];
					}
				}
			}
		}

		// now order the page id list by hierarchy. and make sure they are unique.
		$display = array_unique($display);
		usort($display, function($a, $b) use ($hm) {
			$node_a = $hm->get_node_by_id($a);
			$hier_a = $node_a->getHierarchy();
			$node_b = $hm->get_node_by_id($b);
			$hier_b = $node_b->getHierarchy();
			return strcmp($hier_a, $hier_b);
		});

		$this->_pagelist = $display;

		if ($this->_seek_to > 0) {
			// re-calculate an offset
			$idx = array_search($this->_seek_to, $this->_pagelist);
			if ($idx > 0) {
				// item found.
				$pagenum = (int)($idx / $this->_pagelimit);
				$this->_offset = (int)($pagenum * $this->_pagelimit);
			}
		}

		$offset = min(count($this->_pagelist), $this->_offset);
		$display = array_slice($display, $offset, $this->_pagelimit);

		Lone::get('ContentOperations')->LoadChildren(-1, false, true, $display);
		return $display;
	}

	/**
	 * Test whether the given|current user has edit-authority for all peers of the given content page.
	 */
	private function _check_peer_authorship($content_id, $userid = 0)
	{
		if ($content_id < 1) {
			return false;
		}
		if ($userid <= 0) {
			$userid = $this->_userid;
		}
		return Lone::get('ContentOperations')->CheckPeerAuthorship($userid, $content_id);
	}

	/**
	 * Test whether the given|current user is the author of the specified content page
	 */
	private function _check_authorship($content_id, $userid = 0)
	{
		if ($userid <= 0) {
			$userid = $this->_userid;
		}
		return Lone::get('ContentOperations')->CheckPageAuthorship($userid, $content_id);
	}

	/**
	 * Test whether the specified page is locked (regardless of expiry).
	 */
	private function _is_locked($page_id)
	{
//		if ($this->_module->GetPreference('locktimeout') < 1) return FALSE;
		$locks = $this->get_locks();
		return $locks && isset($locks[$page_id]);
	}

	/**
	 * Test whether the default page is locked (regardless of expiry).
	 */
	private function _is_default_locked()
	{
		$locks = $this->get_locks();
		if (!$locks) {
			return false;
		}
		$dflt_content_id = Lone::get('ContentOperations')->GetDefaultContent();
		return isset($locks[$dflt_content_id]);
	}

	private function _is_lock_expired($page_id)
	{
		$locks = $this->get_locks();
		if (!$locks) {
			return false;
		}
		if (isset($locks[$page_id])) {
			$lock = $locks[$page_id];
			if ($lock->expired()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Load and cache all users
	 */
	private function _get_users()
	{
		// static properties here >> Lone property|ies ?
		static $_users = null;
		if (!$_users) {
			$tmp = Lone::get('UserOperations')->LoadUsers();
			if (is_array($tmp) && ($n = count($tmp))) {
				$_users = [];
				for ($i = 0; $i < $n; ++$i) {
					$oneuser = $tmp[$i];
					$_users[$oneuser->id] = $oneuser;
				}
			}
		}
		return $_users;
	}

	/**
	 * Build display info for each page in the supplied list.
	 *
	 * @ignore
	 * @param array $page_list integer page-id's
	 * @return array
	 */
	private function _get_display_data($page_list)
	{
		$mod = $this->_module;
		$users = $this->_get_users();
		$columns = $this->get_display_columns();
//		$cache = Lone::get('LoadedData')->get('content_quicklist');
		$contentops = Lone::get('ContentOperations');
		$hm = Lone::get('HierarchyManager');

		$out = [];
		foreach ($page_list as $page_id) {
			$node = $hm->get_node_by_id($page_id); //$cache[$page_id] ?? null;
			if (!$node) {
				continue;
			}
			//NOTE CMSMS\contenttypes\ContentBase object
//			$content = $node->getContent(FALSE,TRUE,TRUE); // get everything
			$content = $contentops->LoadEditableContentFromId($page_id);
			if (!$content) {
				continue;
			}

			$rec = [];
			$rec['depth'] = $node->get_level(); // OR $content->GetLevel();
			$rec['id'] = $content->Id();
			$rec['title'] = strip_tags($content->Name());
			$rec['menutext'] = strip_tags($content->MenuText());
			$rec['template_id'] = $content->TemplateId();
			$rec['cachable'] = $content->Cachable();
			$rec['showinmenu'] = $content->ShowInMenu();
			$rec['hasusablelink'] = $content->HasUsableLink();
			$rec['hastemplate'] = $content->HasTemplate(); // mebbe not actually c.f. template id
			$rec['lastmodified'] = $content->GetModifiedDate();
			$rec['created'] = $content->GetCreationDate();
			$rec['wantschildren'] = $content->WantsChildren();
			$rec['viewable'] = $content->IsViewable();
			$tmp = $content->LastModifiedBy();
			if ($tmp > 0 && isset($users[$tmp])) {
				$rec['lastmodifiedby'] = strip_tags($users[$tmp]->username);
			}
			if ($this->_is_locked($page_id)) {
				$lock = $this->_locks[$page_id];
				$rec['lockuser'] = $users[$lock['uid']]->username;
				$rec['lock'] = $this->_locks[$page_id];
			}
			if ($page_id == $this->_seek_to) {
				$rec['selected'] = 1;
			}
			$rec['can_edit'] = ($mod->CheckPermission('Modify Any Page') || $mod->CheckPermission('Manage All Content') ||
								$this->_check_authorship($rec['id'])) && !$this->_is_locked($page_id);
			$rec['can_steal'] = ($mod->CheckPermission('Modify Any Page') || $mod->CheckPermission('Manage All Content') ||
								 $this->_check_authorship($rec['id'])) && $this->_is_locked($page_id) && $this->_is_lock_expired($page_id);
			$rec['can_delete'] = $rec['can_edit'] && $mod->CheckPermission('Remove Pages');
			$rec['can_edit_tpl'] = $mod->CheckPermission('Modify Templates');

			foreach ($columns as $column => $displayable) {
				switch ($column) {
				case 'expand':
					$rec[$column] = 'none';
					if ($node->has_children()) {
						if (in_array($page_id, $this->_opened_array)) {
							$rec[$column] = 'open';
						} else {
							$rec[$column] = 'closed';
						}
					}
					break;

				case 'hier':
					$rec[$column] = $content->Hierarchy();
					break;

				case 'page':
					if ($content->MenuText() == ContentBase::CMS_CONTENT_HIDDEN_NAME) {
						break;
					}
					if (Utils::get_pagenav_display() == 'title') {
						$rec[$column] = strip_tags($content->Name());
					} else {
						$rec[$column] = $rec['menutext'];
					}
					break;

				case 'alias':
					if ($rec['hasusablelink'] && $content->Alias()) {
						$rec[$column] = strip_tags($content->Alias());
					}
					break;

				case 'url':
					if ($rec['hasusablelink'] && $content->URL()) {
						$rec[$column] = strip_tags($content->URL());
					} else {
						$rec[$column] = '';
					}
					break;

				case 'template':
					if ($rec['viewable']) {
						if ($rec['template_id'] > 0) {
							try {
								$template = TemplateOperations::get_template($rec['template_id']);
								$rec[$column] = $template->get_name();
							} catch (Throwable $t) {
								$rec[$column] = $mod->Lang('critical_error');
							}
						} else {
							$rec[$column] = $mod->Lang('none');
						}
					}
					break;

				case 'friendlyname':
					if (method_exists($content, 'FriendlyName')) {
						$rec[$column] = $content->FriendlyName();
						if (!$rec[$column]) {
							$rec[$column] = $mod->Lang('contenttype_'.$content->Type());
						}
					} else {
						$rec[$column] = $mod->Lang('contenttype_'.$content->Type());
					}
					break;

				case 'owner':
					if ($content->Owner() > 0) {
						$rec[$column] = strip_tags($users[$content->Owner()]->username);
					}
					break;

				case 'active':
					$rec[$column] = '';
					if ($mod->CheckPermission('Manage All Content') && !$content->IsSystemPage() && !$this->_is_locked($page_id)) {
						if ($content->Active()) {
							if ($content->DefaultContent()) {
								$rec[$column] = 'default';
							} else {
								$rec[$column] = 'active';
							}
						} else {
							$rec[$column] = 'inactive';
						}
					}
					break;

				case 'default':
					$rec[$column] = '';
					if ($mod->CheckPermission('Manage All Content') && !$this->_is_locked($page_id) && !$this->_is_default_locked()) {
						if ($content->IsDefaultPossible() && $content->Active()) {
							$rec[$column] = ($content->DefaultContent()) ? 'yes' : 'no';
						}
					}
					break;

				case 'multiselect':
					$rec[$column] = '';
					if (!$content->IsSystemPage() && !$this->_is_locked($rec['id'])) {
						if ($mod->CheckPermission('Manage All Content') || $mod->CheckPermission('Modify Any Page')) {
							$rec[$column] = 'yes';
						} elseif ($mod->CheckPermission('Remove Pages') && $this->_check_authorship($rec['id'])) {
							$rec[$column] = 'yes';
						} elseif ($this->_check_authorship($rec['id'])) {
							$rec[$column] = 'yes';
						}
					}
					break;
				// the rest relate to fields which are normally actions-menu items
				case 'move':
					$rec[$column] = '';
					if (!$this->have_locks() && $this->_check_peer_authorship($rec['id']) && ($nsiblings = $node->count_siblings()) > 1) {
						if ($content->ItemOrder() == 1) {
							$rec[$column] = 'down';
						} elseif ($content->ItemOrder() == $nsiblings) {
							$rec[$column] = 'up';
						} else {
							$rec[$column] = 'both';
						}
					}
					break;

				case 'view':
					if ($rec['hasusablelink'] && $rec['viewable'] && $content->Active()) {
						$rec[$column] = $content->GetURL();
					} else {
						$rec[$column] = '';
					}
					break;

				case 'copy':
					$rec[$column] = '';
					if ($content->IsCopyable() && !$this->_is_locked($rec['id'])) {
						if (($rec['can_edit'] && $mod->CheckPermission('Add Pages')) || $mod->CheckPermission('Manage All Content')) {
							$rec[$column] = 'yes';
						}
					}
					break;

				case 'addchild':
					$rec[$column] = '';
					if (($rec['can_edit'] && $mod->CheckPermission('Add Pages')) || $mod->CheckPermission('Manage All Content')) {
						$rec[$column] = 'yes';
					}
					break;

				case 'edit':
					$rec[$column] = '';
					if ($rec['can_edit']) {
						$rec[$column] = 'yes';
					} elseif ($rec['can_steal']) {
						$rec[$column] = 'steal';
					}
					break;

				case 'delete':
					$rec[$column] = '';
					if ($rec['can_delete'] && !$content->DefaultContent() && !$node->has_children() && !$this->_is_locked($rec['id'])) {
						$rec[$column] = 'yes';
					}
					break;
				} // switch
			} // foreach

			$out[] = $rec;
		} // foreach

		return $out;
	}
} // class
