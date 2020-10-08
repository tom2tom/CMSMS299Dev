<?php
# FileManager: a module for CMS Made Simple to allow website file placement, viewing etc
# Copyright (C) 2006-2018 Morten Poulsen <morten@poulsen.org>
# Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AdminMenuItem;
use CMSMS\App;
use CMSMS\CoreCapabilities;
use FileManager\Utils;
use FilePicker\Utils as PickerUtils;

include_once __DIR__.DIRECTORY_SEPARATOR.'fileinfo.php';

final class FileManager extends CMSModule
{
    public function AccessAllowed() { return $this->CheckPermission('Modify Files'); }
    public function AdvancedAccessAllowed() { return $this->CheckPermission('Use FileManager Advanced',0); }
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'files'; }
    public function GetAuthor() { return 'Morten Poulsen (Silmarillion)'; }
    public function GetAuthorEmail() { return 'morten@poulsen.org'; }
    public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetDependencies() { return ['FilePicker'=>'1.1']; }
    public function GetEventDescription($name) { return $this->Lang('eventdesc_'.$name);    }
    public function GetEventHelp($name) { return $this->Lang('eventhelp_'.$name); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetName() { return 'FileManager'; }
    public function GetVersion() { return '1.7.0'; }
    public function HasAdmin() { return TRUE; }
    public function HasCapability($capability, $params = []) { return $capability == CoreCapabilities::CORE_MODULE; }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function IsAdminOnly() { return TRUE; }
//    public function LazyLoadFrontend() { return TRUE; }
    public function MinimumCMSVersion() { return '2.2.2'; }
    public function UninstallPostMessage() { return $this->Lang('uninstalled'); }
    public function UninstallPreMessage() { return $this->Lang('really_uninstall'); }
    public function VisibleToAdminUser() { return $this->AccessAllowed(); }

    /**
     * @deprecated since 1.7 use FilePicker\Utils::get_file_icon()
     */
    public function GetFileIcon($extension, $isdir=false)
    {
        return PickerUtils::get_file_icon($extension, $isdir);
    }

    public function GetPermissions($path, $file)
    {
        $realpath=cms_join_path(CMS_ROOT_PATH, $path, $file);
        $statinfo=stat($realpath);
        return $statinfo['mode'];
    }

    public function GetMode($path, $file)
    {
        $realpath=cms_join_path(CMS_ROOT_PATH, $path, $file);
        $statinfo=stat($realpath);
        return Utils::format_permissions($statinfo['mode']);
    }

    public function GetModeWin($path, $file)
    {
        $realpath=cms_join_path(CMS_ROOT_PATH, $path, $file);
        if (is_writable($realpath)) {
            return '777';
        } else {
            return '444';
        }
    }

    public function GetModeTable($id, $permissions)
    {
        $smarty=App::get_instance()->GetSmarty();
        $tpl = $smarty->createTemplate($this->GetTemplateResource('modetable.tpl'), null, null, $smarty);

        $tpl->assign('ownertext', $this->Lang('owner'))
         ->assign('groupstext', $this->Lang('group'))
         ->assign('otherstext', $this->Lang('others'));

        $ownerr=($permissions & 0400) ? '1':'0';
        $tpl->assign('ownerr', $this->CreateInputCheckbox($id, 'ownerr', '1', $ownerr));

        $ownerw=($permissions & 0200) ? '1':'0';
        $tpl->assign('ownerw', $this->CreateInputCheckbox($id, 'ownerw', '1', $ownerw));

        $ownerx=($permissions & 0100) ? '1':'0';
        $tpl->assign('ownerx', $this->CreateInputCheckbox($id, 'ownerx', '1', $ownerx));

        $groupr=($permissions & 0040) ? '1':'0';
        $tpl->assign('groupr', $this->CreateInputCheckbox($id, 'groupr', '1', $groupr));

        $groupw=($permissions & 0020) ? '1':'0';
        $tpl->assign('groupw', $this->CreateInputCheckbox($id, 'groupw', '1', $groupw));

        $groupx=($permissions & 0010) ? '1':'0';
        $tpl->assign('groupx', $this->CreateInputCheckbox($id, 'groupx', '1', $groupx));

        $othersr=($permissions & 0004) ? '1':'0';
        $tpl->assign('othersr', $this->CreateInputCheckbox($id, 'othersr', '1', $othersr));

        $othersw=($permissions & 0002) ? '1':'0';
        $tpl->assign('othersw', $this->CreateInputCheckbox($id, 'othersw', '1', $othersw));

        $othersx=($permissions & 0001) ? '1':'0';
        $tpl->assign('othersx', $this->CreateInputCheckbox($id, 'othersx', '1', $othersx));

        return $tpl->fetch();
    }

    public function GetModeFromTable($params)
    {
        $owner=0;
        if (isset($params['ownerr'])) $owner+=4;
        if (isset($params['ownerw'])) $owner+=2;
        if (isset($params['ownerx'])) $owner+=1;
        $group=0;
        if (isset($params['groupr'])) $group+=4;
        if (isset($params['groupw'])) $group+=2;
        if (isset($params['groupx'])) $group+=1;
        $others=0;
        if (isset($params['othersr'])) $others+=4;
        if (isset($params['othersw'])) $others+=2;
        if (isset($params['othersx'])) $others+=1;
        return $owner.$group.$others;
    }

    public function SetMode($mode, $path, $file='')
    {
        $realfile = '';
        if ($file) {
//          $realpath = cms_join_path(CMS_ROOT_PATH,$path);
            $realfile = cms_join_path($path, $file);
        } else {
            $realfile = $path;
        }

//      return chmod($realfile,decoct(octdec(77)));
        return chmod($realfile, '0'.octdec($mode));
    }

    public function SetModeWin($mode, $path, $file='')
    {
        if ($file) {
//          $realpath = cms_join_path(CMS_ROOT_PATH,$path);
            $realfile = cms_join_path($path, $file);
        } else {
            $realfile = $path;
        }
        $realfile = $this->WinSlashes($realfile);
//      echo $realfile; echo $mode;die();
        $returnvar = 0;
        $output = [];
        if ($mode == '777') {
//          return chmod($realfile,'775');
            exec('attrib -R '.$realfile, $output, $returnvar);
        } else {
            exec('attrib +R '.$realfile, $output, $returnvar);
//          return chmod($realfile,'0666');
        }
        /*      echo $realfile;
                echo $returnvar;
                print_r($output);
        */
        return ($returnvar == 0);
    }

    /**
     * @since 1.7 param string $id instead of hardcoded value
     */
    public function GetThumbnailLink($id, $file, $path)
    {
//        $advancedmode = Utils::check_advanced_mode();
        $imagepath=cms_join_path(CMS_ROOT_PATH, $path, 'thumb_'.$file['name']);
        if (file_exists($imagepath)) {
            $imageurl=CMS_ROOT_URL.'/'.$this->Slashes($path).'/thumb_'.$file['name'];
            $image='<img src="'.$imageurl.'" class="listicon" alt="'.$file['name'].'" title="'.$file['name'].'" />';
            $url = $this->create_url($id, 'view', '', ['file'=>$this->encodefilename($file['name'])]);
            //$result="<a href=\"".$file['url']."\" target=\"_blank\">";
            $result='<a href="'.$url.'" target="_blank">';
            $result.=$image;
            $result.='</a>';
            return $result;
        }
    }

    /**
     * @deprecated since 1.7 use cms_join_path()
     */
    protected function Slash($str, $str2='', $str3='')
    {
        $parts=[$str];
        if($str2 !== '') $parts[]=$str2;
        if($str3 !== '') $parts[]=$str3;
        return cms_join_path(...$parts);
    }

    public function WinSlashes($path)
    {
        return str_replace('/', '\\', $path);
    }

    public function Slashes($url)
    {
        return str_replace(['\\','//'], ['/','/'], $url);
    }

    public function GetHeaderHTML()
    {
        $out='';
        $urlpath=$this->GetModuleURLPath();
        $fmt='<link rel="stylesheet" type="text/css" href="%s/lib/%s" />';
        $cssfiles = [
        'css/filemanager.css',
        'js/jrac/style.jrac.min.css'
        ];
        foreach ($cssfiles as $one) {
            $out .= sprintf($fmt, $urlpath, $one).PHP_EOL;
        }
        add_page_headtext($out, false);

        $out = '';
        $fmt = '<script type="text/javascript" src="%s/lib/js/%s"></script>';
        //needed if global jq-ui not loaded 'jquery-file-upload/jquery.ui.widget.min.js',
        $jsfiles = [
        'jquery-file-upload/jquery.iframe-transport.js',
        'jquery-file-upload/jquery.fileupload.js',
        'jqueryrotate/jQueryRotate.min.js',
        'jrac/jquery.jrac.min.js',
        ];
        foreach ($jsfiles as $one) {
            $out .= sprintf($fmt, $urlpath, $one).PHP_EOL;
        }
        add_page_foottext($out);
    }

    protected function encodefilename($filename)
    {
        return str_replace('==', '', base64_encode($filename));
    }

    protected function decodefilename($encodedfilename)
    {
        return base64_decode($encodedfilename.'==');
    }

    public function GetAdminMenuItems()
    {
        $out=[];

        if ($this->CheckPermission('Modify Files')) {
            $out[]=AdminMenuItem::from_module($this);
        }

        if ($this->CheckPermission('Modify Site Preferences')) {
            $obj=new AdminMenuItem();
            $obj->module=$this->GetName();
            $obj->section='files';
            $obj->title=$this->Lang('title_filemanager_settings');
            $obj->description=$this->Lang('desc_filemanager_settings');
            $obj->action='admin_settings';
            $obj->icon = false;
            $obj->url=$this->create_url('m1_', $obj->action);
            $out[]=$obj;
        }

        return $out;
    }
} // class
