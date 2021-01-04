<?php
/*
CMSModuleManager module function: populate installed-modules tab
Copyright (C) 2008-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Utils;
use ModuleManager\ModuleInfo;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) return;

try {
    $allmoduleinfo = ModuleInfo::get_all_module_info($connection_ok);
}
catch( Exception $e ) {
    $this->SetError($e->GetMessage()); //probably useless before a return
    return;
}

$s1 = json_encode($this->Lang('confirm_upgrade'));
$s2 = json_encode($this->Lang('confirm_remove'));
$s3 = json_encode($this->Lang('confirm_chmod'));
$s4 = json_encode($this->Lang('error_nofileuploaded'));
$s5 = json_encode($this->Lang('error_invaliduploadtype'));

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
  $('a.mod_upgrade').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this,$s1);
    return false;
  });
  $('a.mod_remove').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this,$s2);
    return false;
  });
  $('a.mod_chmod').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this,$s3);
    return false;
  });
  $('#importbtn').on('click', function() {
    cms_dialog($('#importdlg'), {
      modal: true,
      buttons: {
        {$this->Lang('submit')}: function() {
          var file = $('#xml_upload').val();
          if(file.length == 0) {
            cms_alert($s4);
          } else {
            var ext = file.split('.').pop().toLowerCase();
            if($.inArray(ext, ['xml','cmsmod']) == -1) {
              cms_alert($s5);
            } else {
              $(this).dialog('close');
              $('#local_import').submit();
            }
          }
        },
        {$this->Lang('cancel')}: function() {
          $(this).dialog('close');
        }
      }
    });
  });
});
//]]>
</script>
EOS;
add_page_foottext($js);

uksort($allmoduleinfo,'strnatcasecmp');
$tpl->assign('module_info',$allmoduleinfo);
$devmode = $config['develop_mode'];
$tpl->assign('allow_export',($devmode)?1:0);
if ($devmode) {
    $themeObject = Utils::get_theme_object();
    $path = cms_join_path($this->GetModulePath(),'images','xml');
    $tpl->assign('exporticon',
       $themeObject->DisplayImage($path, 'export', '', '', 'systemicon', ['title'=>$this->Lang('title_moduleexport')]));
}
$tpl->assign('allow_modman_uninstall',$this->GetPreference('allowuninstall',0));
