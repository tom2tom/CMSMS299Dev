<?php
#FileManager: a module for CMS Made Simple to allow website file placement, viewing etc
#Copyright (C) 2006-2018 Morten Poulsen <morten@poulsen.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

include_once __DIR__.DIRECTORY_SEPARATOR.'fileinfo.php';

final class FileManager extends CMSModule
{
    public function GetName() { return 'FileManager'; }
    public function LazyLoadFrontend() { return TRUE; }
    public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.inc'); }
    public function GetHeaderHTML() { return $this->_output_header_content(); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetVersion() { return '1.7.0'; }
    public function GetDependencies() { return ['FilePicker'=>'1.1']; }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetAuthor() { return 'Morten Poulsen (Silmarillion)'; }
    public function GetAuthorEmail() { return 'morten@poulsen.org'; }
    public function IsPluginModule() { return FALSE; }
    public function HasAdmin() { return TRUE; }
    public function IsAdminOnly() { return TRUE; }
    public function GetAdminSection() { return 'files'; }
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function MinimumCMSVersion() { return "2.2.2"; }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function UninstallPostMessage() { return $this->Lang('uninstalled'); }
    public function UninstallPreMessage() { return $this->Lang('really_uninstall'); }
    public function GetEventDescription($name) { return $this->Lang('eventdesc_'.$name);	}
    public function GetEventHelp($name) { return $this->Lang('eventhelp_'.$name); }
    public function VisibleToAdminUser() { return $this->AccessAllowed(); }
    public function AccessAllowed() { return $this->CheckPermission("Modify Files"); }
    public function AdvancedAccessAllowed() { return $this->CheckPermission('Use FileManager Advanced',0); }

	/**
	 * @deprecated since 1.7 use FilePicker\Utils::get_file_icon()
	 */
    public function GetFileIcon($extension,$isdir=false)
    {
		return FilePicker\Utils::get_file_icon($extension,$isdir);
    }

    protected function Slash($str,$str2="",$str3="")
    {
        if ($str=="") return $str2;
        if ($str2=="") return $str;
        if ($str[strlen($str)-1]!="/") {
            if ($str2[0]!="/") {
                return $str."/".$str2;
            } else {
                return $str.$str2;
            }
        } else {
            if ($str2[0]!="/") {
                return $str.$str2;
            } else {
                return $str.substr($str2,1); //trim away one of the slashes
            }
        }
        //Three strings not supported yet...
        return "Error in Slash-function. Please report";
    }

    public function GetPermissions($path,$file)
    {
        $config=cmsms()->GetConfig();
        $realpath=$this->Slash($config["root_path"],$path);
        $statinfo=stat($this->Slash($realpath,$file));
        return $statinfo["mode"];
    }

    public function GetMode($path,$file)
    {
        $config=cmsms()->GetConfig();
        $realpath=$this->Slash($config["root_path"],$path);
        $statinfo=stat($this->Slash($realpath,$file));
        return filemanager_util::format_permissions($statinfo["mode"]);
    }

    public function GetModeWin($path,$file)
    {
        $config=cmsms()->GetConfig();
        $realpath=$this->Slash($config["root_path"],$path);
        $realpath=$this->Slash($realpath,$file);
        if (is_writable($realpath)) {
            return "777";
        } else {
            return "444";
        }
    }

    public function GetModeTable($id,$permissions)
    {
        $smarty=CmsApp::get_instance()->GetSmarty();

        $smarty->assign('ownertext', $this->Lang("owner"));
        $smarty->assign('groupstext', $this->Lang("group"));
        $smarty->assign('otherstext', $this->Lang("others"));

        $ownerr=($permissions & 0400) ? "1":"0";
        $smarty->assign('ownerr', $this->CreateInputCheckbox($id,"ownerr","1",$ownerr));

        $ownerw=($permissions & 0200) ? "1":"0";
        $smarty->assign('ownerw', $this->CreateInputCheckbox($id,"ownerw","1",$ownerw));

        $ownerx=($permissions & 0100) ? "1":"0";
        $smarty->assign('ownerx', $this->CreateInputCheckbox($id,"ownerx","1",$ownerx));

        $groupr=($permissions & 0040) ? "1":"0";;
        $smarty->assign('groupr', $this->CreateInputCheckbox($id,"groupr","1",$groupr));

        $groupw=($permissions & 0020) ? "1":"0";;
        $smarty->assign('groupw', $this->CreateInputCheckbox($id,"groupw","1",$groupw));

        $groupx=($permissions & 0010) ? "1":"0";
        $smarty->assign('groupx', $this->CreateInputCheckbox($id,"groupx","1",$groupx));

        $othersr=($permissions & 0004) ? "1":"0";
        $smarty->assign('othersr', $this->CreateInputCheckbox($id,"othersr","1",$othersr));

        $othersw=($permissions & 0002) ? "1":"0";
        $smarty->assign('othersw', $this->CreateInputCheckbox($id,"othersw","1",$othersw));

        $othersx=($permissions & 0001) ? "1":"0";
        $smarty->assign('othersx', $this->CreateInputCheckbox($id,"othersx","1",$othersx));

        return $this->ProcessTemplate('modetable.tpl');
    }

    public function GetModeFromTable($params)
    {
        $owner=0;
        if (isset($params["ownerr"])) $owner+=4;
        if (isset($params["ownerw"])) $owner+=2;
        if (isset($params["ownerx"])) $owner+=1;
        $group=0;
        if (isset($params["groupr"])) $group+=4;
        if (isset($params["groupw"])) $group+=2;
        if (isset($params["groupx"])) $group+=1;
        $others=0;
        if (isset($params["othersr"])) $others+=4;
        if (isset($params["othersw"])) $others+=2;
        if (isset($params["othersx"])) $others+=1;
        return $owner.$group.$others;
    }

    public function GetThumbnailLink($file,$path)
    {
        $config=cmsms()->GetConfig();
//        $advancedmode = FileManager\filemanager_utils::check_advanced_mode();
        $basedir=$config['root_path'];
        $baseurl=$config['root_url'];

        $filepath=$basedir.DIRECTORY_SEPARATOR.$path;
        $url=$baseurl.'/'.$path;
        $image="";
        $imagepath=$this->Slashes($filepath."/thumb_".$file["name"]);

        if (file_exists($imagepath)) {
            $imageurl=$url.'/thumb_'.$file["name"];
            $image="<img src=\"".$imageurl."\" alt=\"".$file["name"]."\" title=\"".$file["name"]."\" />";
            $url = $this->create_url('m1_','view','',array('file'=>$this->encodefilename($file['name'])));
            //$result="<a href=\"".$file['url']."\" target=\"_blank\">";
            $result="<a href=\"".$url."\" target=\"_blank\">";
            $result.=$image;
            $result.="</a>";
            return $result;
        }
    }

    public function WinSlashes($url)
    {
        return str_replace("/","\\",$url);
    }

    public function Slashes($url)
    {
        $result=str_replace("\\","/",$url);
        $result=str_replace("//","/",$result);
        return $result;
    }

    protected function _output_header_content()
    {
        $out='';
        $urlpath=$this->GetModuleURLPath();

        $fmt='<link rel="stylesheet" type="text/css" href="%s/lib/%s" />';
        $cssfiles = [
        'css/filemanager.css',
        'js/jrac/style.jrac.min.css'
        ];
        foreach( $cssfiles as $one ) {
            $out .= sprintf($fmt,$urlpath,$one)."\n";
        }

        $fmt = '<script type="text/javascript" src="%s/lib/js/%s"></script>';
//needed if global jq-ui not loaded	'jquery-file-upload/jquery.ui.widget.min.js',
        $jsfiles = [
        'jquery-file-upload/jquery.iframe-transport.js',
        'jquery-file-upload/jquery.fileupload.js',
        'jqueryrotate/jQueryRotate.min.js',
        'jrac/jquery.jrac.min.js',
        ];

        foreach( $jsfiles as $one ) {
            $out .= sprintf($fmt,$urlpath,$one)."\n";
        }

        return $out;
    }

    protected function encodefilename($filename)
    {
        return str_replace("==","",base64_encode($filename));
    }

    protected function decodefilename($encodedfilename)
    {
        return base64_decode($encodedfilename."==");
    }

    public function GetAdminMenuItems()
    {
        $out=[];

        if( $this->CheckPermission('Modify Files') ) {
            $out[]=CmsAdminMenuItem::from_module($this);
        }

        if( $this->CheckPermission('Modify Site Preferences') ) {
            $obj=new CmsAdminMenuItem();
            $obj->module=$this->GetName();
            $obj->section='files';
            $obj->title=$this->Lang('title_filemanager_settings');
            $obj->description=$this->Lang('desc_filemanager_settings');
            $obj->action='admin_settings';
            $obj->url=$this->create_url('m1_',$obj->action);
            $out[]=$obj;
        }

        return $out;
    }
} // class
