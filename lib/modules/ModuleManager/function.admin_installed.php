<?php
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) return;

try {
    $allmoduleinfo = ModuleManagerModuleInfo::get_all_module_info($connection_ok);
    uksort($allmoduleinfo,'strnatcasecmp');
}
catch( Exception $e ) {
    $this->SetError($e->GetMessage());
    return;
}
$smarty->assign('module_info',$allmoduleinfo);
$devmode = !empty($config['developer_mode']);
$smarty->assign('allow_export',($devmode)?1:0);
if ($devmode) {
    $smarty->assign('iconsurl',$this->GetModuleURLPath().'/images');
}
$smarty->assign('allow_modman_uninstall',$this->GetPreference('allowuninstall',0));

$yes = $this->Lang('yes');
$s1 = json_encode($this->Lang('confirm_upgrade'));
$s2 = json_encode($this->Lang('confirm_remove'));
$s3 = json_encode($this->Lang('confirm_chmod'));
$s4 = json_encode($this->Lang('error_nofileuploaded'));
$s5 = json_encode($this->Lang('error_invaliduploadtype'));

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
  $('a.mod_upgrade').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this,$s1,'$yes');
    return false;
  });
  $('a.mod_remove').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this,$s2,'$yes');
    return false;
  });
  $('a.mod_chmod').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this,$s3,'$yes');
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
$this->AdminBottomContent($js);

echo $this->ProcessTemplate('admin_installed.tpl');
