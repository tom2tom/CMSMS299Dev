<?php
# CMSContentManager module action: decide which multi-page action to perform
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

if( !isset($gCms) ) exit;

if( isset($params['bulk_submit']) && isset($params['bulk_action']) && !empty($params['bulk_content']) ) {
    list($module,$bulkaction) = explode('::',$params['bulk_action'],2);
    if( !$module || $module == '-1' || !$bulkaction || $bulkaction == '-1' ) {
	    $this->SetError($this->Lang('error_nobulkaction'));
		$this->Redirect($id,'defaultadmin',$returnid);
    }
}
else {
    $this->SetError($this->Lang('error_nobulkaction'));
    $this->Redirect($id,'defaultadmin',$returnid);
}

if( strcasecmp($module,'core') != 0 ) {
    $modobj = cms_utils::get_module($module);
    if( is_object($modobj) ) {
		$a = implode('&bulk_content[]=',$params['bulk_content']);
        $s = implode(',',$params['bulk_content']);
		$url = $modobj->create_url($id,$bulkaction,$returnid,
		['bulk_content[]' => $a,
		 'contentlist' => $s]); //deprecated since 2.3
		$url = str_replace('&amp;','&',$url);
		redirect($url);
	}
    $this->SetError($this->Lang('error_invalidbulkaction'));
    $this->Redirect($id,'defaultadmin',$returnid);
}

$a = implode('&bulk_content[]=',$params['bulk_content']);
$parms = ['bulk_content[]'=>$a]; // generates array-like get-URL
switch( $bulkaction ) {
 case 'inactive':
   $parms['active'] = 0;
   $this->Redirect($id,'admin_bulk_active',$returnid,$parms);
   break;

 case 'active':
   $parms['active'] = 1;
   $this->Redirect($id,'admin_bulk_active',$returnid,$parms);
   break;

 case 'setcachable':
   $parms['cachable'] = 1;
   $this->Redirect($id,'admin_bulk_cachable',$returnid,$parms);
   break;

 case 'setnoncachable':
   $parms['cachable'] = 0;
   $this->Redirect($id,'admin_bulk_cachable',$returnid,$parms);
   break;

 case 'showinmenu':
   $parms['showinmenu'] = 1;
   $this->Redirect($id,'admin_bulk_showinmenu',$returnid,$parms);
   break;

 case 'hidefrommenu':
   $parms['showinmenu'] = 0;
   $this->Redirect($id,'admin_bulk_showinmenu',$returnid,$parms);
   break;

// case 'setdesign':
 case 'settemplate':
 case 'setstyles':
 case 'changeowner':
 case 'delete':
   $this->Redirect($id,'admin_bulk_'.$bulkaction,$returnid,$parms);
   break;

}

$this->SetError($this->Lang('error_invalidbulkaction'));
$this->Redirect($id,'defaultadmin',$returnid);
