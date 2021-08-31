<?php
/*
ModuleManager module function: populate search tab
Copyright (C) 2011-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use ModuleManager\ModuleRepClient;
use ModuleManager\Utils;

$search_data = null;
$term = '';
$advanced = false;
// check for saved results (too bad if data are now crap of some sort!)
if( isset($_SESSION['modmgr_search']) ) $search_data = unserialize($_SESSION['modmgr_search']);
if( isset($_SESSION['modmgr_searchterm']) ) $term = $_SESSION['modmgr_searchterm'];
if( isset($_SESSION['modmgr_searchadv']) ) $advanced = (bool)$_SESSION['modmgr_searchadv'];

$clear_search = function() use (&$search_data) {
    unset($_SESSION['modmgr_search']);
    $search_data = null;
};

// get the modules that are already installed
$result = Utils::get_installed_modules();
if( !$result[0] ) {
    $instmodules = '';
    $this->DisplayErrorPage($result[1]);
    return;
}
$instmodules = $result[1];

if( isset($params['submit']) ) {

    $url = $this->GetPreference('module_repository');
    $error = 0;
    $advanced = (bool)$params['advanced']; // boolean for js
    $search_data = [];

    try {
        $term = trim($params['term']);
        if( strlen($term) < 3 ) throw new Exception($this->Lang('error_searchterm'));

        $res = ModuleRepClient::search($term,$advanced);
        if( !is_array($res) || $res[0] == false ) throw new Exception($this->Lang('error_search').' '.$res[1]);
        if( !is_array($res[1]) ) throw new Exception($this->Lang('search_noresults'));

        $res = $res[1];
        if( $res ) $res = Utils::build_module_data($res, $instmodules);

        for( $i = 0, $n = count($res); $i < $n; $i++ ) {
            $row = $res[$i];
            $onerow = (object)$row;

            $onerow->age = Utils::get_status($row['date']);
            $onerow->candownload = false; // maybe changed later
            $onerow->date = $row['date'];
            $onerow->downloads = $row['downloads']??$this->Lang('unknown');
            $onerow->name = $this->CreateLink($id, 'modulelist', $returnid, $row['name'],['name'=>$row['name']]);
            $onerow->version = $row['version'];

            $onerow->help_url = $this->create_action_url($id, 'modulehelp',
                ['name'=>$row['name'], 'version'=>$row['version'], 'filename'=>$row['filename']]);
            $onerow->helplink = $this->CreateLink($id, 'modulehelp', $returnid, $this->Lang('helptxt'),
                ['name'=>$row['name'], 'version'=>$row['version'], 'filename'=>$row['filename']] );

            $onerow->depends_url = $this->create_action_url($id, 'moduledepends',
                ['name' => $row['name'], 'version' => $row['version'], 'filename' => $row['filename']]);
            $onerow->dependslink = $this->CreateLink($id, 'moduledepends', $returnid,
                $this->Lang('dependstxt'),
                ['name' => $row['name'], 'version' => $row['version'],'filename' => $row['filename']]);

            $onerow->about_url = $this->create_action_url($id, 'moduleabout',
                ['name' => $row['name'], 'version' => $row['version'], 'filename' => $row['filename']]);
            $onerow->aboutlink = $this->CreateLink($id, 'moduleabout', $returnid,
                $this->Lang('abouttxt'),
                ['name' => $row['name'], 'version' => $row['version'], 'filename' => $row['filename']]);

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
                // check for uninstalled presence
                $moddir = '';
                $cando = false;
                $writable = false;
                $modname = trim($row['name']);
                foreach( $dirlist as $i => $dir ) {
                    $fp = $dir.DIRECTORY_SEPARATOR.$modname.DIRECTORY_SEPARATOR.$modname.'.module.php';
                    if( is_file($fp) ) {
                        $moddir = dirname($fp);
                        $cando = $writable = $writelist[$i];
                        break;
                    }
                }
                if( !$moddir ) {
                    // nope, default to main-place
                    $moddir = $dirlist[0].DIRECTORY_SEPARATOR.$modname;
                    $cando = $writable = $writelist[0];
                }

                if( !$writable ) {
                    $onerow->status = $this->Lang('cantinstall');
                }
                elseif( (is_dir($moddir) && is_directory_writable($moddir)) ||
                        ($cando && !file_exists($moddir)) ) {
                    $onerow->candownload = true;
                    $onerow->status = $this->CreateLink($id, 'installmodule', $returnid,
                        $this->Lang('download'),
                        ['name' => $row['name'], 'version' => $row['version'],
                         'filename' => $row['filename'], 'size' => $row['size']]);
                }
                else {
                    $onerow->status = $this->Lang('cantinstall');
                }
                break;
              case 'upgrade':
                $moddir = '';
                $cando = false;
                $writable = false;
                $modname = trim($row['name']);
                foreach( $dirlist as $i => $dir ) {
                    $fp = $dir.DIRECTORY_SEPARATOR.$modname.DIRECTORY_SEPARATOR.$modname.'.module.php';
                    if( is_file($fp) ) {
                        $moddir = dirname($fp);
                        $writable = $writelist[$i];
                        $cando = is_writable($moddir);
                        break;
                    }
                }
                if( !$moddir ) { // should never happen for an upgrade
                    $moddir = $dirlist[0].DIRECTORY_SEPARATOR.$modname;
                    $cando = $writable = $writelist[0];
                }

                if( !$writable ) {
                    $onerow->status = $this->Lang('cantupgrade');
                }
                elseif( (is_dir($moddir) && is_directory_writable($moddir)) ||
                        ($cando && !file_exists($moddir)) ) {
                    $onerow->candownload = true;
                    $onerow->status = $this->CreateLink($id, 'installmodule', $returnid,
                        $this->Lang('upgrade'),
                        ['name' => $row['name'], 'version' => $row['version'],
                         'filename' => $row['filename'], 'size' => $row['size']]);
                }
                else {
                    $onerow->status = $this->Lang('cantupgrade');
                }
                break;
            } // switch

            $onerow->size = (int)((float) $row['size'] / 1024.0 + 0.5);
            $onerow->description = ( !empty($row['description']) ) ? $row['description'] : null;
            $search_data[] = $onerow;
        }
        $_SESSION['modmgr_search'] = serialize($search_data);
        $_SESSION['mogmgr_searchterm'] = $term;
        $_SESSION['modmgr_searchadv'] = $params['advanced'];
    }
    catch( Throwable $t ) {
        $clear_search();
        $this->ShowErrors($t->GetMessage());
    }
}

$tpl->assign('search_data',$search_data)
 ->assign('term',$term);

$basic = ($advanced) ? 'false' : 'true';
$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 if ($basic) {
  $('#advhelp').hide();
 }
 $('#advanced').on('click', function() {
  $('#advhelp').toggle();
 });
});
//]]>
</script>
EOS;
add_page_foottext($js);

