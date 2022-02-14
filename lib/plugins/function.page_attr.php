<?php
/*
Plugin to retrieve the value of a specified property of the current page or a specified page.
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\SingleItem;

function smarty_function_page_attr($params, $template)
{
	if( isset($params['page']) ) {
		$page = trim($params['page']);
		if( is_numeric($page) ) {
			$page += 0; // it's an id
		}
	}
	else {
		$page = false;
	}
	$key = trim($params['key'] ?? '');
	$inactive = cms_to_bool($params['inactive'] ?? false);
	$contentobj = null;

	if( $page || $page === 0 ) {
		// gotta find it by id or alias
		if( is_numeric($page) && $page > 0 ) {
			// it's an id
			$hm = SingleItem::App()->GetHierarchyManager();
			$node = $hm->find_by_tag('id', $page);
			if( $node ) $contentobj = $node->getContent(true, true, $inactive);
		}
		else { //if( !is_numeric($page) ) {
			// this is quicker if using an alias
			$contentobj = SingleItem::ContentOperations()->LoadContentFromAlias($page, !$inactive);
		}
	}
	else {
		$contentobj = SingleItem::App()->get_content_object();
	}

	$result = '';
	if( $contentobj && $key ) {
		switch( $key ) {
		case '_dflt_':
			$result = $contentobj->GetContent(); // i.e. content_en
			break;

		case 'alias':
			$result = $contentobj->Alias();
			break;

		case 'id':
			$result = $contentobj->Id();
			break;

		case 'title':
		case 'name':
			$result = $contentobj->Name();
			break;

		case 'titleattribute':
		case 'description':
			$result = $contentobj->TitleAttribute();
			break;

		case 'create_date':
			$result = $contentobj->GetCreationDate();
			if( $result < 0 ) $result = null;
			break;

		case 'modified_date':
			$result = $contentobj->GetModifiedDate();
			if( $result < 0 ) $result = null;
			break;

		case 'last_modified_by':
			$result = (int) $contentobj->LastModifiedBy();
			break;

		case 'owner':
			$result = (int) $contentobj->Owner();
			break;

		default:
			$result = $contentobj->GetPropertyValue($key);
			break;
		}
	}

	if( !empty($params['assign']) ) {
		$template->assign(trim($params['assign']), $result);
		return '';
	}
	return $result;
}

function smarty_cms_about_function_page_attr()
{
	echo _ld('tags', 'about_generic', 'Ted Kulp 2004',
	'<li>2015-06-02 - Added page parameter (Robert Campbell)</li>'
	);
}

function smarty_cms_help_function_page_attr()
{
	echo _ld('tags', 'help_generic',
	'This plugin retrieves the value of a specified property of the current page or a specified page',
	'page_attr ...',
	'<li>key: Name of wanted property. May be \'_dflt_\' to get the content</li>
<li>(optional)page: Numeric id or alias of a specific page to be processed instead of the current page</li>
<li>(optional)inactive: Whether to report even if page is inactive. Default false</li>'
	);
}