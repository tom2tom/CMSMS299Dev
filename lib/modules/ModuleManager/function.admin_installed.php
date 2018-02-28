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
echo $this->ProcessTemplate('admin_installed.tpl');
