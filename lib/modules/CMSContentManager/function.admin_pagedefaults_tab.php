<?php
# CMSAContentManager module action tab
# Coopyright (C) 2013-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

use CMSMS\ContentBase;
use CMSMS\ContentOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

$page_prefs = CmsContentManagerUtils::get_pagedefaults();
$smarty->assign('page_prefs',$page_prefs);
$smarty->assign('all_contenttypes',ContentOperations::get_instance()->ListContentTypes(FALSE,FALSE));
$smarty->assign('design_list',CmsLayoutCollection::get_list());
$smarty->assign('template_list',CmsLayoutTemplate::template_query(array('as_list'=>1)));
$smarty->assign('addteditor_list',ContentBase::GetAdditionalEditorOptions());

echo $this->ProcessTemplate('admin_pagedefaults_tab.tpl');

