<?php
/*
ContentManager module action: decide which multi-page action to perform
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\Utils;

if( !$this->CheckContext() ) exit;

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

if( strcasecmp($module,'core') != 0 ) { // i.e. self
    $mod = Utils::get_module($module);
    if( is_object($mod) ) {
        $s = implode(',',$params['bulk_content']);
        $mod->Redirect($id,$bulkaction,$returnid,
          ['bulk_content' => $params['bulk_content'],'contentlist' => $s]); //deprecated since 2.0
    }
    $this->SetError($this->Lang('error_invalidbulkaction'));
    $this->Redirect($id,'defaultadmin',$returnid);
}

$parms = ['bulk_content'=>$params['bulk_content']];
switch( $bulkaction ) {
    case 'inactive':
        $parms['active'] = 0;
        $this->Redirect($id,'bulk_active',$returnid,$parms);

    case 'active':
        $parms['active'] = 1;
        $this->Redirect($id,'bulk_active',$returnid,$parms);

    case 'setcachable':
        $parms['cachable'] = 1;
        $this->Redirect($id,'bulk_cachable',$returnid,$parms);

    case 'setnoncachable':
        $parms['cachable'] = 0;
        $this->Redirect($id,'bulk_cachable',$returnid,$parms);

    case 'showinmenu':
        $parms['showinmenu'] = 1;
        $this->Redirect($id,'bulk_showinmenu',$returnid,$parms);

    case 'hidefrommenu':
        $parms['showinmenu'] = 0;
        $this->Redirect($id,'bulk_showinmenu',$returnid,$parms);

//  case 'setdesign':
    case 'settemplate':
    case 'setstyles':
    case 'changeowner':
    case 'delete':
        $this->Redirect($id,'bulk_'.$bulkaction,$returnid,$parms);
}

$this->SetError($this->Lang('error_invalidbulkaction'));
$this->Redirect($id,'defaultadmin',$returnid);
