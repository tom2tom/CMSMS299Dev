<?php
# ModuleManager module function: populate search tab
# Copyright (C) 2011-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use ModuleManager\modulerep_client;
use ModuleManager\utils;

$dir = CMS_ASSETS_PATH.'/modules';
$caninstall = (is_dir($dir) && is_writable($dir));

// see if there are saved results
$search_data = null;
$term = '';
$advanced = 0;
if( isset($_SESSION['modmgr_search']) ) $search_data = unserialize($_SESSION['modmgr_search']);
if( isset($_SESSION['modmgr_searchterm']) ) $term = $_SESSION['modmgr_searchterm'];
if( isset($_SESSION['modmgr_searchadv']) ) $advanced = $_SESSION['modmgr_searchadv'];

$clear_search = function() use (&$search_data) {
    unset($_SESSION['modmgr_search']);
    $search_data = null;
};

// get the modules that are already installed
$instmodules = '';
$result = utils::get_installed_modules();
if( ! $result[0] ) {
    $this->_DisplayErrorPage( $id, $params, $returnid, $result[1] );
    return;
}
$instmodules = $result[1];

if( isset($params['submit']) ) {
    try {
        $url = $this->GetPreference('module_repository');
        $error = 0;
        $term = trim($params['term']);
        if( strlen($term) < 3 ) throw new Exception($this->Lang('error_searchterm'));
        $advanced = (int)$params['advanced'];

        $res = modulerep_client::search($term,$advanced);
        if( !is_array($res) || $res[0] == false ) throw new Exception($this->Lang('error_search').' '.$res[1]);
        if( !is_array($res[1]) ) throw new Exception($this->Lang('search_noresults'));

        $res = $res[1];
        $data = [];
        if( $res ) $res = utils::build_module_data($res, $instmodules);

        $writable = true;
        foreach( cms_module_places() as $dir ) {
           if( !is_dir($dir) || !is_writable($dir) ) {
               $writable = false;
               break;
           }
        }

        $search_data = [];
        for( $i = 0, $n = count($res); $i < $n; $i++ ) {
            $row =& $res[$i];
            $obj = new stdClass();
            foreach( $row as $k => $v ) {
                $obj->$k = $v;
            }
            $obj->name = $this->CreateLink( $id, 'modulelist', $returnid, $row['name'],['name'=>$row['name']]);
            $obj->version = $row['version'];
            $obj->help_url = $this->create_url( $id, 'modulehelp', $returnid,
                                                ['name'=>$row['name'],'version'=>$row['version'],'filename'=>$row['filename']] );
            $obj->helplink = $this->CreateLink( $id, 'modulehelp', $returnid, $this->Lang('helptxt'),
                                                ['name'=>$row['name'],'version'=>$row['version'],'filename'=>$row['filename']] );
            $obj->depends_url = $this->create_url( $id, 'moduledepends', $returnid,
                                                   ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]);
            $obj->dependslink = $this->CreateLink( $id, 'moduledepends', $returnid,
                                                   $this->Lang('dependstxt'),
                                                   ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]);
            $obj->about_url = $this->create_url( $id, 'moduleabout', $returnid,
                                                 ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]);

            $obj->aboutlink = $this->CreateLink( $id, 'moduleabout', $returnid,
                                                 $this->Lang('abouttxt'),
                                                 ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]);
            $obj->age = utils::get_status($row['date']);
            $obj->date = $row['date'];
            $obj->downloads = $row['downloads']??$this->Lang('unknown');
            $obj->candownload = false;

            switch( $row['status'] ) {
            case 'incompatible':
                $obj->status = $this->Lang('incompatible');
                break;
            case 'uptodate':
                $obj->status = $this->Lang('uptodate');
                break;
            case 'newerversion':
                $obj->status = $this->Lang('newerversion');
                break;
            case 'notinstalled':
                $mod = $moduledir.DIRECTORY_SEPARATOR.$row['name'];
                if( (($writable && is_dir($mod) && is_directory_writable( $mod )) ||
                     ($writable && !file_exists( $mod ) )) && $caninstall ) {
                    $obj->candownload = true;
                    $obj->status = $this->CreateLink( $id, 'installmodule', $returnid,
                                                      $this->Lang('download'),
                                                      ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'],
                                                       'size' => $row['size']]);
                }
                else {
                    $obj->status = $this->Lang('cantdownload');
                }
                break;

            case 'upgrade':
                $mod = $moduledir.DIRECTORY_SEPARATOR.$row['name'];
                if( (($writable && is_dir($mod) && is_directory_writable( $mod )) ||
                     ($writable && !file_exists( $mod ) )) && $caninstall ) {
                    $obj->candownload = true;
                    $obj->status = $this->CreateLink( $id, 'installmodule', $returnid,
                                                      $this->Lang('upgrade'),
                                                      ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'],
                                                       'size' => $row['size']]);
                }
                else {
                    $obj->status = $this->Lang('cantdownload');
                }
                break;
            } // case

            $obj->size = (int)((float) $row['size'] / 1024.0 + 0.5);
            if( isset( $row['description'] ) )  $obj->description=$row['description'];
            $search_data[] = $obj;
        }
        $_SESSION['modmgr_search'] = serialize($search_data);
        $_SESSION['mogmgr_searchterm'] = $term;
        $_SESSION['modmgr_searchadv'] = $params['advanced'];
    }
    catch( Exception $e ) {
        $clear_search();
        $this->ShowErrors($e->GetMessage());
    }
}

if( is_array($search_data) ) $tpl->assign('search_data',$search_data);
$tpl->assign('term',$term)
 ->assign('advanced',$advanced)
 ->assign('formstart',$this->CreateFormStart($id,'defaultadmin','','post','',false,'',['__activetab'=>'search']))
 ->assign('formend',$this->CreateFormEnd());

