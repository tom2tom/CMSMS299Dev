<?php
/*
CMSModuleManager module function: populate installed-modules tab
Copyright (C) 2008-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
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

try {
    $allmoduleinfo = ModuleInfo::get_all_module_info($connection_ok);
}
catch( Throwable $t ) {
     $this->ShowErrors($t->GetMessage());
     return;
}

$in = [
'active',
'admin_only',
//'author',
//'authoremail',
'bundled', // bundled with installer
'can_deactivate',
'can_install',
'can_uninstall',
'can_upgrade',
//'changelog',
'dependents',
'depends',
'deprecated',
'description',
//'dir', // module-folder
'e_status',
'fresh_upgrade',
//'has_custom',
//'help',
'installed_version',
'installed',
'mincmsversion',
'missing_deps',
'name',
'needs_upgrade',
'notavailable',
'notinforge',
'root_writable', // just the module-folder
'stagnant',
'stale_upgrade',
'status', // 'installed' or empty
'ver_compatible',
'version',
'writable', // module-folder & everything in it
];
$out = [];
foreach($allmoduleinfo as $obj) {
    $props = $obj->select_module_properties($in);
    if (empty($props['e_status'])) {
        $props['title_key'] = '';
        $props['main_key'] = $props['status'];
    } else {
        $props['title_key'] = 'title_'.$props['e_status'];
        $props['main_key'] = $props['e_status'];
    }
    $out[] = $props;
}

usort($out,function($a,$b) { return strnatcasecmp($a['name'],$b['name']); });
$tpl->assign('module_info',$out);
$tpl->assign('allow_modman_uninstall',$this->GetPreference('allowuninstall',0));
$devmode = $config['develop_mode'];
$tpl->assign('allow_export',($devmode)?1:0);
if ($devmode) {
    $themeObject = Utils::get_theme_object();
    $path = cms_join_path($this->GetModulePath(),'images','xml');
    $tpl->assign('exporticon',
       $themeObject->DisplayImage($path,'export','','','systemicon',['title'=>$this->Lang('title_moduleexport')]));
}

$s1 = addcslashes($this->Lang('confirm_upgrade'), "'\n\r");
$s2 = addcslashes($this->Lang('confirm_remove'), "'\n\r");
$s3 = addcslashes($this->Lang('confirm_chmod'), "'\n\r");
$s4 = addcslashes($this->Lang('error_nofileuploaded'), "'\n\r");
$s5 = addcslashes($this->Lang('error_invaliduploadtype'), "'\n\r");
$submit = $this->Lang('submit');
$cancel = $this->Lang('cancel');

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
  $('a.mod_upgrade').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this, '$s1');
    return false;
  });
  $('a.mod_remove').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this, '$s2');
    return false;
  });
  $('a.mod_chmod').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this, '$s3');
    return false;
  });
  $('#importbtn').on('click', function() {
    cms_dialog($('#importdlg'), {
      modal: true,
      buttons: {
        '$submit': function() {
          var file = $('#xml_upload').val();
          if(file.length == 0) {
            cms_alert('$s4');
          } else {
            var ext = file.split('.').pop().toLowerCase();
            if($.inArray(ext, ['xml','cmsmod']) == -1) {
              cms_alert('$s5');
            } else {
              $(this).dialog('close');
              $('#local_import').submit();
            }
          }
        },
        '$cancel': function() {
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
