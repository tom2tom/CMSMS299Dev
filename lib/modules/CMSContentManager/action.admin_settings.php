<?php
# CMSContentManager module action: settings
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

use CMSContentManager\Utils;
use CMSMS\ContentBase;
use CMSMS\ContentOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

$tpl = $smarty->createTemplate($this->GetTemplateResource('settings.tpl'),null,null,$smarty);

// general tab

$opts = [
 'all'=>$this->Lang('opt_alltemplates'),
 'alldesign'=>$this->Lang('opt_alldesign'),
 'allpage'=>$this->Lang('opt_allpage'),
 'designpage'=>$this->Lang('opt_designpage')
];

$tpl->assign('locktimeout',$this->GetPreference('locktimeout'))
 ->assign('lockrefresh',$this->GetPreference('lockrefresh'))
 ->assign('template_list_opts',$opts)
 ->assign('template_list_mode',$this->GetPreference('template_list_mode','designpage'));

// listsettings tab

$opts = [
 'title'=>$this->Lang('prompt_page_title'),
 'menutext'=>$this->Lang('prompt_page_menutext')
];
$tpl->assign('namecolumnopts',$opts)
 ->assign('list_namecolumn',$this->GetPreference('list_namecolumn','title'));

$allcols = 'expand,icon1,hier,page,alias,url,template,friendlyname,owner,active,default,move,view,copy,addchild,edit,delete,multiselect';
$dflts = 'expand,icon1,hier,page,alias,template,friendlyname,active,default,view,copy,addchild,edit,delete,multiselect';
$tmp = explode(',',$allcols);
$opts = [];
foreach( $tmp as $one ) {
  $opts[$one] = $this->Lang('colhdr_'.$one);
}
$tpl->assign('visible_column_opts',$opts);
$tmp = explode(',',$this->GetPreference('list_visiblecolumns',$dflts));
$tpl->assign('list_visiblecolumns',$tmp);

// pagedefaults tab

$tpl->assign('page_prefs',Utils::get_pagedefaults())
 ->assign('all_contenttypes',ContentOperations::get_instance()->ListContentTypes(false,false))
 ->assign('design_list',CmsLayoutCollection::get_list())
 ->assign('template_list',TemplateOperations::template_query(['as_list'=>1]))
 ->assign('addteditor_list',ContentBase::GetAdditionalEditorOptions());

$tpl->display();
