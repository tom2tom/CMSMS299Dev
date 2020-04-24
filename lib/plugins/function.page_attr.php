<?php
#Plugin to...
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

function smarty_function_page_attr($params, $template)
{
	$key = trim(get_parameter_value($params,'key'));
	$page = trim(get_parameter_value($params,'page'));
	$assign = trim(get_parameter_value($params,'assign'));
	$inactive = cms_to_bool(get_parameter_value($params,'inactive'));
	$contentobj = null;

	if( $page ) {
		// gotta find it by id or alias
		if( is_numeric($page) && (int) $page > 0 ) {
			// it's an id
			$hm = CmsApp::get_instance()->GetHierarchyManager();
			$node = $hm->find_by_tag('id',$page);
			if( $node ) $contentobj = $node->getContent(true,true,$inactive);
		}
		else {
			// this is quicker if using an alias
			$content_ops = ContentOperations::get_instance();
			$contentobj = $content_ops->LoadContentFromAlias($page,!$inactive);
		}
	}
	else {
		$contentobj = cms_utils::get_current_content();
	}

	$result = null;
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
	if( $assign ) {
		$template->assign($assign,$result);
		return;
	}
	return $result;
}

function smarty_cms_about_function_page_attr()
{
	echo <<<'EOS'
<p>Author: Ted Kulp &lt;ted@cmsmadesimple.org&gt;</p>
<p>Change History:</p>
<ul>
 <li>2015-06-02 - Added page parameter (calguy1000)</li>
</ul>
EOS;
}

