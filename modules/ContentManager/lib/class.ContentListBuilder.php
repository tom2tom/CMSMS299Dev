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
namespace ContentManager; // the module-class in global namespace

use CMSMS\LockOperations;
use CMSMS\Lone;
use CMSMS\TemplateOperations;
use ContentManager;
use ContentManager\ContentBase;
use ContentManager\ContentListFilter;
use ContentManager\ContentListQuery;
use ContentManager\Utils;
use Throwable;
use const CMS_USER_KEY;
use function cmsms;
use function CMSMS\log_info;
use function get_userid;

/**
 * A class for building and managing page/content lists and their members
 *
 * @final
 */
final class ContentListBuilder
{
	private $display_columns = [];
	private $filter = null;
	private $locks;
	private $module;
	private $offset = 0;
	/**
	 * @var array ids of items with displayed child(ren)
	 */
	private $opened;
	private $pagelimit = 500;
	private $pagelist;
	private $seek_to;
	private $use_perms = true;
	private $userid;

	/**
	 * Constructor
	 *
	 * Caches the opened pages, and user id
	 */
	public function __construct(ContentManager $mod)
	{
		$this->module = $mod;
		$this->userid = get_userid();
		$tmp = $_SESSION['opened_pages'.$_SESSION[CMS_USER_KEY]] ?? null;
		$this->opened = ($tmp) ? explode(',', $tmp) : [];
	}

	/**
	 * Record the displayable-status of the named column
	 *
	 * @param string $column
	 * @param bool $state Default true
	 */
	public function column_state($column, $state = true)
	{
		$this->display_columns[$column] = $state;
	}

	/**
	 * Expand a parent page. Hence its child(ren) are displayed.
	 *
	 * @param int $parent_page_id > 0 or else ignored
	 */
	public function expand_section($parent_page_id)
	{
		$parent_page_id = (int)$parent_page_id;
		if ($parent_page_id < 1) {
			return;
		}
		$tmp = $this->opened;
		$tmp[] = $parent_page_id;
		sort($tmp, SORT_NUMERIC);
		$this->opened = array_unique($tmp);
		$_SESSION['opened_pages'.$_SESSION[CMS_USER_KEY]] = implode(',', $tmp);
	}

	/**
	 * Expand all parent pages. Hence all content pages are displayed.
	 */
	public function expand_all()
	{
		$ptops = cmsms()->GetHierarchyManager(); // OR Lone::get('PageTreeOperations');
		// find all the pages that have children
		$tmp = array_keys($ptops->children);
		$n = array_search(-1, $tmp); // omit the root-member
		if ($n !== false) { unset($tmp[$n]); }
		sort($tmp, SORT_NUMERIC);
		$this->opened = $tmp;
		$_SESSION['opened_pages'.$_SESSION[CMS_USER_KEY]] = implode(',', $tmp);
	}

	/**
	 * Collapse all parent pages. Hence no descendent page is displayed.
	 */
	public function collapse_all()
	{
		$this->opened = [];
		unset($_SESSION['opened_pages'.$_SESSION[CMS_USER_KEY]]);
	}

	/**
	 * Collapse a parent page. Hence its descendent pages are not displayed
	 *
	 * @param int $parent_page_id > 0 or else ignored
	 */
	public function collapse_section($parent_page_id)
	{
		$parent_page_id = (int)$parent_page_id;
		if ($parent_page_id < 1) {
			return false;
		}
		$n = array_search($parent_page_id, $this->opened);
		if ($n !== false) {
			unset($this->opened[$n]);
			if ($this->opened) {
				$_SESSION['opened_pages'.$_SESSION[CMS_USER_KEY]] = implode(',', $this->opened);
			} else {
				unset($_SESSION['opened_pages'.$_SESSION[CMS_USER_KEY]]);
			}
			return true;
		}
		return false;
	}

	/**
	 * Set the active state of a page
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
		if (!$this->module->CheckPermission('Manage All Content')) {
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
		$this->filter = $filter;
	}

	/**
	 * Set the page limit
	 * This MUST be called before get_content_list() is called
	 *
	 * @param integer The page limit, constrained to 1 .. 500
	 * @return void
	 */
	public function set_pagelimit($n)
	{
		$n = max(1, min(500, (int)$n));
		$this->pagelimit = $n;
	}

	/**
	 * Get the page limit
	 *
	 * @return int
	 */
	public function get_pagelimit()
	{
		return $this->pagelimit;
	}

	/**
	 * Set the page offset
	 * This MUST be called before get_content_list() is called
	 *
	 * @param int page maximum offset
	 */
	public function set_offset($n)
	{
		$n = max(0, (int)$n);
		$this->offset = $n;
	}

	/**
	 * Get the current offset
	 *
	 * @return int
	 */
	public function get_offset()
	{
		return $this->offset;
	}

	public function seek_to($n)
	{
		$n = max(1, (int)$n);
		$this->seek_to = $n;
	}

	public function set_page($n)
	{
		$n = max(1, (int)$n);
		$this->offset = $this->pagelimit * ($n - 1);
	}

	/**
	 * This might be called after the content list is returned, because
	 * the offset can be adjusted because of seeking to a content id.
	 */
	public function get_page()
	{
		return (int)($this->offset / $this->pagelimit) + 1;
	}

	/**
	 * Get the number of pages
	 * This may only be called after get_content_list() has been called
	 *
	 * @return int
	 */
	public function get_numpages()
	{
		if (!is_array($this->pagelist)) {
			return;
		}
		$npages = (int)(count($this->pagelist) / $this->pagelimit);
		if (count($this->pagelist) % $this->pagelimit != 0) {
			++$npages;
		}
		return $npages;
	}

	/**
	 * Set the specified page as the default page
	 *
	 * @param int $page_id > 0 or else ignored
	 */
	public function set_default($page_id)
	{
		$page_id = (int)$page_id;
		if ($page_id < 1) {
			return false;
		}

		if (!$this->module->CheckPermission('Manage All Content')) {
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
	 * Move a page up or down relative to its peers
	 *
	 * @param int $page_id > 0 or else ignored
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
		if ($this->module->CheckPermission('Manage All Content')) {
			$flag = true;
		} elseif ($this->module->CheckPermission('Reorder Content') &&
				$contentops->CheckPeerAuthorship($this->userid, $page_id)) {
			$flag = true;
		} else {
			$flag = false;
		}

		if (!$flag) {
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
	 * Delete a page
	 *
	 * @param int $page_id > 0 or else ignored
	 * @return mixed error message on failure | null on success
	 */
	public function delete_content($page_id)
	{
		if ($this->module->CheckPermission('Manage All Content')) {
			$flag = true;
		} elseif ($this->module->CheckPermission('Remove Pages') && $this->check_authorship($page_id, $this->userid)) {
			$flag = true;
		} else {
			$flag = false;
		}
		if (!$flag) {
			return $this->module->Lang('error_delete_permission');
		}

		$page_id = (int)$page_id;
		if ($page_id < 1) {
			return $this->module->Lang('error_invalidpageid');
		}
		$ptops = cmsms()->GetHierarchyManager(); // OR Lone::get('PageTreeOperations');
		if (!$ptops->get_tag('id', $page_id)) {
			return $this->module->Lang('error_invalidpageid');
		}
		if ($ptops->has_children($page_id)) {
			return $this->module->Lang('error_delete_haschildren');
		}

		$contentops = Lone::get('ContentOperations');
		$content = $contentops->LoadEditableContentFromId($page_id);
		if ($content->DefaultContent()) {
			return $this->module->Lang('error_delete_defaultcontent');
		}

		$parent_id = $ptops->get_parent($page_id, false);
		if ($parent_id) {
			$childcount = $ptops->count_children($parent_id);
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
	 * Get the columns that are to be displayed in the content list.
	 * Some of them (expand,icon1,actions,multiselect) are not subject
	 * to user-choice
	 *
	 * @return array  Each member like colcode => headertype,
	 *  where headertype is
	 * a string ('icon'|'normal') to indicate how the column header is intended, or
	 * null to indicate that the column should be hidden
	 */
	public function get_display_columns()
	{
		$mod = $this->module;
		$flat = $mod->GetPreference('list_visiblecolumns');
		if ($flat) {
			$flat = 'expand,icon1,' . $flat . ',actions,multiselect';
		} else {
			$flat =
		'expand,icon1,hier,page,alias,type,template,active,default,created,modified,actions,multiselect';
		}
		$cols = explode(',', $flat);

		$pall = $mod->CheckPermission('Manage All Content');
		$padd = $pall || $mod->CheckPermission('Add Pages');
		$pdel = $pall || $mod->CheckPermission('Remove Pages');

		$displaycols = [];
		//NOTE the array is populated in the order of displayed columns
		$displaycols['expand'] = (!$this->filter && in_array('expand', $cols)) ? 'icon' : null;
		$displaycols['icon1'] = in_array('icon1', $cols) ? 'icon' : null;
		$displaycols['hier'] = in_array('hier', $cols) ? 'normal' : null;
		$displaycols['page'] = in_array('page', $cols) ? 'normal' : null;
		$displaycols['alias'] = in_array('alias', $cols) ? 'normal' : null;
		$displaycols['url'] = in_array('url', $cols) ? 'normal' : null;
		$displaycols['type'] = in_array('type', $cols) ? 'normal' : null;
		$displaycols['owner'] = in_array('owner', $cols) ? 'normal' : null;
		$displaycols['template'] = in_array('template', $cols) ? 'normal' : null;
		$displaycols['active'] = in_array('active', $cols) ? 'normal' : null;
		$displaycols['default'] = ($pall && in_array('default', $cols)) ? 'normal' : null;
		$displaycols['created'] = ($pall && in_array('created', $cols)) ? 'normal' : null;
		$displaycols['modified'] = ($pall && in_array('modified', $cols)) ? 'normal' : null;
		$displaycols['actions'] = 'normal';
		$displaycols['multiselect'] = ($pdel && in_array('multiselect', $cols)) ? 'icon' : null;
		// the rest are probably actions-menu items, not displayed columns
		$displaycols['view'] = in_array('view', $cols) ? 'icon' : null;
		$displaycols['edit'] = in_array('edit', $cols) ? 'icon' : null;
		$displaycols['delete'] = ($pdel && in_array('delete', $cols)) ? 'icon' : null; // OR also allow owner to delete?
		$displaycols['copy'] = ($padd && in_array('copy', $cols)) ? 'icon' : null;
		$displaycols['addchild'] = ($padd && in_array('addchild', $cols)) ? 'icon' : null;
		$displaycols['move'] = (in_array('move', $cols) && ($pall || $mod->CheckPermission('Reorder Content'))) ? 'icon' : null;

		foreach ($displaycols as $key => $val) {
			if (isset($this->display_columns[$key]) && !$this->display_columns[$key]) {
				$displaycols[$key] = null;
			}
		}
		return $displaycols;
	}

	/**
	 * Get the displayed-columns sortable indicators
	 * @since 2.0
	 *
	 * @param $columns optional array generated by get_display_columns(). Default null.
	 * @return array each member like field-identifier => 'text'|'link'|'icon'|'date'
	 */
	public function get_sort_columns($columns = null) : array
	{
		$sortcols = [];
		if (!$columns) { $columns = $this->get_display_columns(); }
		$cols = array_keys($columns);
		foreach ($cols as $key) {
			switch ($key) {
				case 'alias':
				case 'type':
				case 'friendlyname': //deprecated alternate of page-type
				case 'owner':
				case 'url':
					$sortcols[$key] = 'text';
					break;
				case 'page':
				case 'template':
					$sortcols[$key] = 'link';
					break;
				case 'hier':
					$sortcols[$key] = 'number';
					break;
				case 'active':
				case 'default':
				case 'icon1':
					$sortcols[$key] = 'icon';//TODO another inner-HTML etc sorter for these
					break;
				case 'created':
				case 'modified':
					$sortcols[$key] = 'date';
					break;
			}
		}
		return $sortcols;
	}

	/**
	 * Get a hash of current page locks
	 */
	public function get_locks()
	{
//		if( $this->module->GetPreference('locktimeout') < 1 ) return;
		if (is_array($this->locks)) {
			return $this->locks;
		}
		$this->locks = [];
		$tmp = LockOperations::get_locks('content');
		if ($tmp) {
			foreach ($tmp as $lock_obj) {
				$this->locks[$lock_obj['oid']] = $lock_obj;
			}
		}
		return $this->locks;
	}

	/**
	 * Test whether there is any lock
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
		$pagelist = $this->load_editable_content();
		if ($pagelist) {
			return $this->get_display_data($pagelist);
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

	/* *
	 * Return a list of the ids of the specified node and all its descendent nodes
	 * @internal
	 *
	 * @param int $nid node id
	 * @param PageTreeOperations $ptops
	 * @return array maybe empty
	 */
/*	private function get_all_pages(int $nid, $ptops)
	{
		$out = [];
		if ($nid > 0) {
			$out[] = $nid;
		}
		if ($ptops->has_children($nid)) {
			$children = $ptops->get_children(false, $nid);
			foreach ($children as $cid) {
				$tmp = $this->get_all_pages($cid, $ptops); //recurse
				if ($tmp) {
					$out = array_merge($out, $tmp);
				}
			}
		}
		return $out;
	}
*/
	/**
	 * Load all displayed content
	 */
	private function load_editable_content()
	{
		$ptops = cmsms()->GetHierarchyManager(); // OR Lone::get('PageTreeOperations');
		$display = [];

		$modify_any_page = $this->module->CheckPermission('Manage All Content') || $this->module->CheckPermission('Modify Any Page');
		if ($modify_any_page && $this->filter) {
			// display only the pages matching the filter
			$query = new ContentListQuery($this->filter);
			while (!$query->EOF()) {
				$display[] = $query->GetObject();
				$query->MoveNext();
			}
		} elseif ($modify_any_page && $this->use_perms) {
			/* build a display list:
			 1. add in top level items (items with parent == -1) which are never closed
			 TODO deal with strange logic here
			 2. for each id in this->opened[]
				 for each ancestor
				  if not also in this->opened[] break
				  if ancestor is the root, add items children
			 3. remove ids that the user is not permitted to view (author pages)
			*/
/*
			$is_opened = function($node, $opened_array) {
				while ($node) {
					$nid = $node->getId();
					if ($nid > 0) {
						if (!in_array($nid, $opened_array)) {
							return false;
						}
						$node = $node->get_parent();
					}
					else {
						break;
					}
				}
				return true;
			};

			// add in top level items
			$children = $ptops->get_children();
			if ($children) {
				foreach ($children as $child) {
					$display[] = $child->getId();
				}
			}

			// add children of ->opened[] items to the list
			foreach ($this->opened as $one) {
				$node = $ptops->get_node_by_id($one);
				if (!$node) {
					continue;
				}

				if (!$is_opened($node, $this->opened)) {
					continue;
				}
				$display[] = $one;

				$children = $node->get_children();
				if ($children) {
					foreach ($children as $child) {
						$display[] = $child->getId();
					}
				}
			}
*/
//*
			// add in top level items
			$children = $ptops->get_children(false);
			if ($children) {
				$display = $children;
			}

			// add in children of ->opened[] items TODO deal with strange logic here
			foreach ($this->opened as $pid) {
				if (!$ptops->get_tag('id', $pid)) {
					continue;
				}
//				$parents = $ptops->get_ancestors($pid);
//				if (!array_intersect($parents, $this->opened)) continue;
				$xid = $pid;
				while ($xid && $xid != -1) {
					if (!in_array($xid, $this->opened)) {
						continue 2;
					}
					$xid = $ptops->get_parent($xid, false);
				}
				$display[] = $pid;
				$children = $ptops->get_children(false, $pid);
				if ($children) {
					$display = array_merge($display, $children);
				}
			}
//*/
		} else {
			// user may only edit some pages
/*			for each item
				if in ->opened[] or has no parent, add item
				if all ancestors are opened, add item
*/
			$tmp = Lone::get('ContentOperations')->GetPageAccessForUser($this->userid);
			$display = [];
			foreach ($tmp as $pid) {
				// accumulate this page and all its ancestors
//				$parents = array_merge([$pid], $ptops->get_ancestors($pid, false));
				$parents = [];
				$xid = $pid;
				while ($xid && $xid != -1) {
					$parents[] = $xid;
					$xid = $ptops->get_parent($xid, false);
				}

				// start at root
				// push items from list on the stack if they are root, or the previous item is in ->opened[]
				$parents = array_reverse($parents);
				for ($i = 0, $n = count($parents); $i < $n; ++$i) {
					if ($i == 0) {
						$display[] = $parents[$i]; // always display the accessible page
						continue;
					}
					$xid = $parents[$i - 1];
					if (in_array($xid, $display) && in_array($xid, $this->opened)) {
						$display[] = $parents[$i];
					}
				}
			}
		}

		// order the page id's by hierarchy and make sure they are unique
		$display = array_unique($display);
		usort($display, function($a, $b) use ($ptops) {
			$hier_a = $ptops->getHierarchy($a);
			$hier_b = $ptops->getHierarchy($b);
			return $hier_a <=> $hier_b;
		});

		$this->pagelist = $display;

		if ($this->seek_to > 0) {
			// re-calculate an offset
			$idx = array_search($this->seek_to, $this->pagelist);
			if ($idx > 0) {
				// item found
				$pagenum = (int)($idx / $this->pagelimit);
				$this->offset = (int)($pagenum * $this->pagelimit);
			}
		}

		$offset = min(count($this->pagelist), $this->offset);
		$display = array_slice($display, $offset, $this->pagelimit);

		Lone::get('ContentOperations')->LoadChildren(-1, false, true, $display);
		return $display;
	}

	/**
	 * Test whether the given|current user is authorized to edit
	 * all peers of the given content page
	 * @ignore
	 * @return bool
	 */
	private function check_peer_authorship($content_id, $userid = 0)
	{
		if ($content_id < 1) {
			return false;
		}
		if ($userid <= 0) {
			$userid = $this->userid;
		}
		return Lone::get('ContentOperations')->CheckPeerAuthorship($userid, $content_id);
	}

	/**
	 * Test whether the given|current user is authorized to edit
	 * the given content page (which authority might extend beyond
	 * the page-owner)
	 * @ignore
	 * @return bool
	 */
	private function check_authorship($content_id, $userid = 0)
	{
		if ($userid <= 0) {
			$userid = $this->userid;
		}
		return Lone::get('ContentOperations')->CheckPageAuthorship($userid, $content_id);
	}

	/**
	 * Test whether the specified page is locked (regardless of expiry)
	 * @ignore
	 * @return bool
	 */
	private function is_locked($page_id)
	{
//		if ($this->module->GetPreference('locktimeout') < 1) return FALSE;
		$locks = $this->get_locks();
		return $locks && isset($locks[$page_id]);
	}

	/**
	 * Test whether the default page is locked (regardless of expiry)
	 * @ignore
	 * @return bool
	 */
	private function is_default_locked()
	{
		$locks = $this->get_locks();
		if (!$locks) {
			return false;
		}
		$dflt_content_id = Lone::get('ContentOperations')->GetDefaultContent();
		return isset($locks[$dflt_content_id]);
	}

	private function is_lock_expired($page_id)
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
	 * Load and cache all users' names
	 * @ignore
	 * @return array each member like id=>name (friendly/public if any, or login)
	 */
	private function get_users()
	{
		// static properties here >> Lone property|ies ?
		static $_users = null;
		if ($_users === null) {
			$_users = Lone::get('UserOperations')->GetUsers(true, true);
		}
		return $_users;
	}

	/**
	 * Build display info for each page in the supplied list
	 *
	 * @ignore
	 * @param array $page_list integer page-id's
	 * @return array
	 */
	private function get_display_data($page_list)
	{
		$mod = $this->module;
		$usernames = $this->get_users();
		$columns = $this->get_display_columns();
//		$cache = Lone::get('LoadedData')->get('content_quicklist');
		$contentops = Lone::get('ContentOperations');
		$ptops = cmsms()->GetHierarchyManager(); // OR Lone::get('PageTreeOperations');
		$pany = $mod->CheckPermission('Modify Any Page') || $mod->CheckPermission('Manage All Content');
		$out = [];
		foreach ($page_list as $page_id) {
			if (!$ptops->get_tag('id', $page_id)) {
				continue;
			}
			$content = $contentops->LoadEditableContentFromId($page_id);
			if (!$content) {
				continue;
			}

			$rec = [];
			$rec['cachable'] = $content->Cachable();
			$rec['created'] = $content->GetCreationDate();
			$rec['depth'] = $ptops->get_level($page_id); // OR $content->GetLevel() ?
			$rec['hastemplate'] = $content->HasTemplate(); // mebbe not actually c.f. template id
			$rec['hasusablelink'] = $content->HasUsableLink();
			$rec['id'] = $page_id;
			$rec['lastmodified'] = $content->GetModifiedDate();
			$rec['menutext'] = strip_tags($content->MenuText());
			$rec['showinmenu'] = $content->ShowInMenu();
			$rec['template_id'] = $content->TemplateId();
			$rec['title'] = strip_tags($content->Name()); // OR something title-specific?
			$rec['viewable'] = $content->IsViewable();
			$rec['wantschildren'] = $content->WantsChildren();
			$tmp = $content->LastModifiedBy();
			if ($tmp > 0 && isset($usernames[$tmp])) {
				$rec['lastmodifiedby'] = strip_tags($usernames[$tmp]); //and/or specialize()?
			}
			if ($this->is_locked($page_id)) {
				$lock = $this->locks[$page_id];
				$rec['lockuser'] = $usernames[$lock['uid']];
				$rec['lock'] = $this->locks[$page_id];
			}
			if ($page_id == $this->seek_to) {
				$rec['selected'] = 1;
			}
			$rec['can_edit'] = ($pany || $this->check_authorship($page_id)) &&
				!$this->is_locked($page_id);
			$rec['can_steal'] = ($pany || $this->check_authorship($page_id)) &&
				$this->is_locked($page_id) && $this->is_lock_expired($page_id);
			$rec['can_delete'] = $rec['can_edit'] && $mod->CheckPermission('Remove Pages');
			$rec['can_edit_tpl'] = $mod->CheckPermission('Modify Templates');

			foreach ($columns as $column => $displayable) {
				switch ($column) {
				case 'expand':
					if (!$ptops->has_children($page_id)) {
						$rec[$column] = 'none';
					} elseif (in_array($page_id, $this->opened)) {
						$rec[$column] = 'open';
					} else {
						$rec[$column] = 'closed';
					}
					break;

				case 'hier':
					$rec[$column] = $content->Hierarchy();
					break;

				case 'page':
					if ($rec['menutext'] !== ContentBase::CMS_CONTENT_HIDDEN_NAME) {
						if (Utils::get_pagenav_display() == 'title') {
							$rec[$column] = strip_tags($content->Name());
						} else {
							$rec[$column] = $rec['menutext'];
						}
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

				case 'type':
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
						$rec[$column] = strip_tags($usernames[$content->Owner()]); // tags prob. irrelevant for login
					}
					break;

				case 'active':
					$rec[$column] = '';
					// not $this->check_authorship($page_id) here
					if (($pany || $content->Owner() == $this->userid) &&
						!($content->IsSystemPage() || $this->is_locked($page_id))) {
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
					if ($pany && !($this->is_locked($page_id) || $this->is_default_locked())) {
						if ($content->IsDefaultPossible() && $content->Active()) {
							$rec[$column] = ($content->DefaultContent()) ? 'yes' : 'no';
						}
					}
					break;

				case 'multiselect':
					$rec[$column] = '';
					if (!($content->IsSystemPage() || $this->is_locked($page_id))) {
						if ($pany ||/*) {
							$rec[$column] = 'yes';
redundant				} elseif ($this->check_authorship($page_id) && $mod->CheckPermission('Remove Pages')) {
							$rec[$column] = 'yes';
						} elseif (
*/
							$content->Owner() == $this->userid) {
							$rec[$column] = 'yes';
						}
					}
					break;
				// the rest relate to fields which are normally actions-menu items
				case 'move':
					$rec[$column] = '';
					if (!$this->have_locks() && $this->check_peer_authorship($page_id) && ($nsiblings = $ptops->count_siblings($page_id)) > 1) {
						$n = $content->ItemOrder();
						if ($n == 1) {
							$rec[$column] = 'down';
						} elseif ($n == $nsiblings) {
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
					if ($content->IsCopyable() && !$this->is_locked($page_id)) {
						if ($pany || ($rec['can_edit'] && $mod->CheckPermission('Add Pages'))) {
							$rec[$column] = 'yes';
						}
					}
					break;

				case 'addchild':
					$rec[$column] = '';
					if ($pany || ($rec['can_edit'] && $mod->CheckPermission('Add Pages'))) {
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
					if ($rec['can_delete'] && !($content->DefaultContent() || $ptops->has_children($page_id) || $this->is_locked($page_id))) {
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
