<?php
# CMSContentManager module tab creator
# Copyright (C) 2013-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

$opts = [
 'title'=>$this->Lang('prompt_page_title'),
 'menutext'=>$this->Lang('prompt_page_menutext')
];
$smarty->assign('namecolumnopts',$opts);
$smarty->assign('list_namecolumn',$this->GetPreference('list_namecolumn','title'));

$allcols = 'expand,icon1,hier,page,alias,url,template,friendlyname,owner,active,default,move,view,copy,addchild,edit,delete,multiselect';
$dflts = 'expand,icon1,hier,page,alias,template,friendlyname,active,default,view,copy,addchild,edit,delete,multiselect';
$tmp = explode(',',$allcols);
$opts = [];
foreach( $tmp as $one ) {
  $opts[$one] = $this->Lang('colhdr_'.$one);
}
$smarty->assign('visible_column_opts',$opts);
$tmp = explode(',',$this->GetPreference('list_visiblecolumns',$dflts));
$smarty->assign('list_visiblecolumns',$tmp);

echo $this->ProcessTemplate('admin_listsettings_tab.tpl');
