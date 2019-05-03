<?php
# CMSContentManager module action tab
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

use CMSContentManager\ContentListBuilder;
use CMSMS\internal\bulkcontentoperations;

if( !isset($gCms) ) exit;

if( !function_exists('cm_prettyurls_ok') ) {
  function cm_prettyurls_ok()
  {
    static $_prettyurls_ok = -1;
    if( -1 < $_prettyurls_ok ) return $_prettyurls_ok;

    $config = cmsms()->GetConfig();
    if( isset($config['url_rewriting']) && $config['url_rewriting'] != 'none' )
      $_prettyurls_ok = 1;
    else
      $_prettyurls_ok = 0;
    return $_prettyurls_ok;
  }
}

if( isset($params['multisubmit']) && isset($params['multiaction']) &&
    isset($params['bulk_content']) && is_array($params['bulk_content']) && count($params['bulk_content']) > 0 ) {
  list($module,$bulkaction) = explode('::',$params['multiaction'],2);
  if( $module == '' || $module == '-1' || $bulkaction == '' || $bulkaction == '-1' ) {
    $this->SetError($this->Lang('error_nobulkaction'));
    $this->RedirectToAdminTab();
  }
  // redirect to special action to handle bulk content stuff.
  $this->Redirect($id,'admin_multicontent',$returnid,
		  ['multicontent'=>base64_encode(serialize($params['bulk_content'])),
			'multiaction'=>$params['multiaction']]);
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('admin_pages_tab.tpl'),null,null,$smarty);

$tpl->assign('prettyurls_ok',cm_prettyurls_ok())
 ->assign('can_add_content',$this->CheckPermission('Add Pages') || $this->CheckPermission('Manage All Content'))
 ->assign('can_reorder_content',$this->CheckPermission('Manage All Content'));

// load all the content that this user can display...
// organize it into a tree
$builder = new ContentListBuilder($this);
$curpage = 1;
if( isset($params['curpage']) ) $curpage = (int)$params['curpage'];

//
// handle all of the possible ajaxy/sub actions.
//
$ajax = 0;
if( isset($params['ajax']) ) {
  $ajax = 1;
}
if( isset($params['expandall']) || isset($_GET['expandall']) ) {
  $builder->expand_all();
  $curpage = 1;
}
if( isset($params['collapseall']) || isset($_GET['collapseall']) ) {
  $builder->collapse_all();
  $curpage = 1;
}
if( isset($params['expand']) ) {
  $builder->expand_section($params['expand']);
}
if( isset($params['collapse']) ) {
  $builder->collapse_section($params['collapse']);
  $curpage = 1;
}
if( isset($params['setinactive']) ) {
  $builder->set_active($params['setinactive'],FALSE);
  if( !$res ) $this->ShowErrors($this->Lang('error_setinactive'));
}
if( isset($params['setactive']) ) {
  $res = $builder->set_active($params['setactive'],TRUE);
  if( !$res ) $this->ShowErrors($this->Lang('error_setactive'));
}
if( isset($params['setdefault']) ) {
  $res = $builder->set_default($params['setdefault'],TRUE);
  if( !$res ) $this->ShowErrors($this->Lang('error_setdefault'));
}
if( isset($params['moveup']) ) {
  $res = $builder->move_content($params['moveup'],-1);
  if( !$res ) $this->ShowErrors($this->Lang('error_movecontent'));
}
if( isset($params['movedown']) ) {
  $res = $builder->move_content($params['movedown'],1);
  if( !$res ) $this->ShowErrors($this->Lang('error_movecontent'));
}
if( isset($params['delete']) ) {
  $res = $builder->delete_content($params['delete']);
  if( $res ) $this->ShowErrors($res);
}

//
// build the display
//

if( isset($params['setoptions']) ) {
  cms_userprefs::set($this->GetName().'_pagelimit',(int)$params['pagelimit']);
}
$pagelimit = cms_userprefs::get($this->GetName().'_pagelimit',500);

$builder->set_pagelimit($pagelimit);
$builder->set_page($curpage);
$editinfo = $builder->get_content_list();
$npages = $builder->get_numpages();
$pagelimits = [10=>10,25=>25,100=>100,250=>250,500=>500];
$tpl->assign('pagelimits',$pagelimits);
$pagelist = [];
for( $i = 0; $i < $npages; $i++ ) {
  $pagelist[$i+1] = $i+1;
}
$tpl->assign('pagelimit',$pagelimit)
 ->assign('pagelist',$pagelist)
 ->assign('curpage',$curpage)
 ->assign('npages',$npages);
$columns  = $builder->get_display_columns();
$tpl->assign('columns',$columns);
if( $this->GetPreference('list_namecolumn','menutext') == 'title' ) {
  $tpl->assign('colhdr_page',$this->Lang('colhdr_pagetitle'));
}
else {
  $tpl->assign('colhdr_page',$this->Lang('colhdr_menutext'));
}
$tpl->assign('content_list',$editinfo)
 ->assign('ajax',$ajax);

$opts = [];
if( $this->CheckPermission('Remove Pages') ||
    $this->CheckPermission('Manage All Content') ) {
  bulkcontentoperations::register_function($this->Lang('bulk_delete'),'delete');
}
if( $this->CheckPermission('Manage All Content')) {
  bulkcontentoperations::register_function($this->Lang('bulk_active'),'active');
  bulkcontentoperations::register_function($this->Lang('bulk_inactive'),'inactive');
  bulkcontentoperations::register_function($this->Lang('bulk_cachable'),'setcachable');
  bulkcontentoperations::register_function($this->Lang('bulk_noncachable'),'setnoncachable');
  bulkcontentoperations::register_function($this->Lang('bulk_showinmenu'),'showinmenu');
  bulkcontentoperations::register_function($this->Lang('bulk_hidefrommenu'),'hidefrommenu');
  bulkcontentoperations::register_function($this->Lang('bulk_setdesign'),'setdesign');
  bulkcontentoperations::register_function($this->Lang('bulk_changeowner'),'changeowner');
}
$opts = bulkcontentoperations::get_operation_list();
$tpl->assign('bulk_options',$opts);

$tpl->display();
