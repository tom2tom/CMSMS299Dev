<?php
# Class: CMSContentManager module utility methods
# Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSContentManager;

use cms_userprefs;
use cms_utils;
use CmsDataNotFoundException;
use CmsLayoutCollection;
use CmsLayoutTemplate;
use CmsLayoutTemplateType;
use function get_userid;

/**
 * Utility methods for the CMSContentManager module.
 *
 * This is an internal class, for the CMSContentManager module only.
 *
 * @package CMS
 * @internal
 * @ignore
 * @author Robert Campbell
 *
 */
final class Utils
{
	private function __construct() {}

	public static function get_pagedefaults()
	{
		$tpl_id = null;
		try {
			$tpl = TemplateOperations::load_default_template_by_type(CmsLayoutTemplateType::CORE.'::page');
			$tpl_id = $tpl->get_id();
		}
		catch( CmsDataNotFoundException $e ) {
			$type = CmsLayoutTemplateType::load(CmsLayoutTemplateType::CORE.'::page');
			$list = TemplateOperations::load_all_by_type($type);
			$tpl = $list[0];
			$tpl_id = $tpl->get_id();
		}

		$mod = cms_utils::get_module('CMSContentManager');
		$tmp = $mod->GetPreference('page_prefs');
		if( $tmp ) {
			$page_prefs = unserialize($tmp, ['allowed_classes'=>false]);
		}
		else {
			$page_prefs = [
			'active'=>true,
			'addteditors'=>[], // array of ints
			'cachable'=>true,
			'content'=>'',
			'contenttype'=>'content',
			'defaultcontent'=>false,
			'design_id'=>CmsLayoutCollection::load_default()->get_id(), // int
			'disallowed_types'=>[], // array of strings
			'extra1'=>'',
			'extra2'=>'',
			'extra3'=>'',
			'metadata'=>'',
			'parent_id'=>-2, // int
			'searchable'=>true,
			'secure'=>false, // deprecated from 2.3
			'showinmenu'=>true,
			'template_id'=>$tpl_id,
			];
		}

		return $page_prefs;
	}

	public static function locking_enabled()
	{
		$mod = cms_utils::get_module('CMSContentManager');
		$timeout = (int) $mod->GetPreference('locktimeout');
		return $timeout > 0;
	}

	public static function get_pagenav_display()
	{
		$userid = get_userid(false);
		$pref = cms_userprefs::get($userid,'ce_navdisplay');
		if( !$pref ) {
			$mod = cms_utils::get_module('CMSContentManager');
			$pref = $mod->GetPreference('list_namecolumn');
			if( !$pref ) $pref = 'title';
		}
		return $pref;
	}
} // class

