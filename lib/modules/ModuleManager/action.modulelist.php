<?php
# ModuleManager module action: modulelist
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

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;

$_SESSION[$this->GetName()]['active_tab'] = 'modules';
if( !isset($params['name']) ) $this->Redirect($id,'defaultadmin');

$prefix = trim($params['name']);
$repmodules = modulerep_client::get_repository_modules($prefix,FALSE,TRUE);
if( !is_array($repmodules) || $repmodules[0] === FALSE ) $this->Redirect($id,'defaultadmin'); // for some reason, nothing matched.

$repmodules = $repmodules[1];

$result = utils::get_installed_modules();
if( ! $result[0] ) {
  $this->_DisplayErrorPage( $id, $params, $returnid, $result[1] );
  return;
}

$instmodules = $result[1];

$caninstall = true;
foreach (cms_module_places() as $dir) {
   if (!is_dir($dir) || !is_writable($dir)) {
	   $caninstall = false;
	   break;
   }
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('showmodule.tpl'),null,null,$smarty);

$data = utils::build_module_data($repmodules,$instmodules,false);
if( $data ) {
  $size = count($data);

  // check for permissions
  $moduledir = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'modules';
  $writable = is_writable( $moduledir );

  // build the table
  $rowarray = [];
  $newestdisplayed='';
  foreach( $data as $row ) {
    $onerow = new stdClass();
    $onerow->date = $row['date'];
    $onerow->age = utils::get_status($row['date']);
    $onerow->downloads = $row['downloads'];
    $onerow->name = $row['name'];
    $onerow->version = $row['version'];
    $onerow->helplink = $this->CreateLink( $id, 'modulehelp', $returnid,
					   $this->Lang('helptxt'),
					   ['name' => $row['name'],
						 'version' => $row['version'],
						 'filename' => $row['filename']]);
    $onerow->dependslink = $this->CreateLink( $id, 'moduledepends', $returnid,
					      $this->Lang('dependstxt'),
					      ['name' => $row['name'],
						    'version' => $row['version'],
						    'filename' => $row['filename']]);
    $onerow->aboutlink = $this->CreateLink( $id, 'moduleabout', $returnid,
					    $this->Lang('abouttxt'),
					    ['name' => $row['name'],
						  'version' => $row['version'],
						  'filename' => $row['filename']]);

    switch( $row['status'] ) {
    case 'incompatible':
      $onerow->status = $this->Lang('incompatible');
      break;
    case 'uptodate':
      $onerow->status = $this->Lang('uptodate');
      break;
    case 'newerversion':
      $onerow->status = $this->Lang('newerversion');
      break;
    case 'notinstalled':
      {
	$mod = $moduledir.DIRECTORY_SEPARATOR.$row['name'];
	if( (($writable && is_dir($mod) && is_directory_writable( $mod )) ||
	     ($writable && !file_exists( $mod ) )) && $caninstall ) {
	  $onerow->status = $this->CreateLink( $id, 'installmodule', $returnid,
					       $this->Lang('download'),
					       ['name' => $row['name'],
						     'version' => $row['version'],
						     'filename' => $row['filename'],
						     'size' => $row['size']]);
	}
	else {
	  $onerow->status = $this->Lang('cantdownload');
	}
      }
      break;

    case 'upgrade':
      {
	$mod = $moduledir.DIRECTORY_SEPARATOR.$row['name'];
	if( (($writable && is_dir($mod) && is_directory_writable( $mod )) ||
	     ($writable && !file_exists( $mod ) )) && $caninstall ) {
	  $onerow->status = $this->CreateLink( $id, 'installmodule', $returnid,
					       $this->Lang('upgrade'),
					       ['name' => $row['name'],
						     'version' => $row['version'],
						     'filename' => $row['filename'],
						     'size' => $row['size']]);
	}
	else {
	  $onerow->status = $this->Lang('cantdownload');
	}
      }
      break;
    }

    $onerow->size = (int)((float) $row['size'] / 1024.0 + 0.5);
    if( isset( $row['description'] ) ) $onerow->description=$row['description'];
    $rowarray[] = $onerow;
  } // for

  utils::get_images($tpl);
  $tpl->assign('items', $rowarray)
   ->assign('itemcount', count($rowarray));
}

$tpl->assign('nametext',$this->Lang('nametext'))
 ->assign('vertext',$this->Lang('vertext'))
 ->assign('sizetext',$this->Lang('sizetext'))
 ->assign('statustext',$this->Lang('statustext'))
 ->assign('header',$this->Lang('versionsformodule',$prefix));

$tpl->display();
return false;
