<?php
/*
Module Manager action: display help
Copyright (C) 2008-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of ModuleManager, an addon module for
CMS Made Simple to allow browsing remotely stored modules, viewing
information about them, and downloading or upgrading

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

use CMSMS\Lone;
use CMSMS\NlsOperations;
use CMSMS\Utils;
use function CMSMS\sanitizeVal;

//if( some worthy test fails ) exit;
//if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}

$modname = sanitizeVal($params['mod'], CMSSAN_FILE); //module-identifier == foldername and in file-name
if( $modname ) {
    // get the module instance... force it to load if necessary, without priors
    $mod = Lone::get('ModuleOperations')->get_module_instance($modname, '', true);
}
else {
    $modname = lang('notspecified');
    $mod = null;
}
if( !is_object($mod) ) {
    $this->SetError($this->Lang('error_getmodule', $modname));
    $this->RedirectToAdminTab();
}
$themeObject = Utils::get_theme_object();
$themeObject->SetTitle('module_help');

$our_lang = NlsOperations::get_current_language();
$lang = $params['lang'] ?? '';
if( $lang ) { $lang = sanitizeVal($lang); }

$tpl = $smarty->createTemplate($this->GetTemplateResource('help.tpl')); //,null,null,$smarty);
$tpl->assign('our_lang',$our_lang);

if( $our_lang != 'en_US' ) {
    if( $lang ) {
        $tpl->assign('mylang_text',$this->Lang('display_in_mylanguage'))
         ->assign('mylang_url',$this->create_action_url($id,'local_help',['mod'=>$modname]));
        NlsOperations::set_language('en_US');
    }
    else {
        $yourlang_url = $this->create_action_url($id,'local_help',['mod'=>$modname,'lang'=>'en_US']);
        $tpl->assign('our_lang',$our_lang)
         ->assign('englang_url',$yourlang_url)
         ->assign('englang_text',$this->Lang('display_in_english'));
    }
}

$tpl->assign('module_name',$mod->GetName())
 ->assign('friendly_name',$mod->GetFriendlyName())
 ->assign('back_url',$this->create_action_url($id,'defaultadmin'))
 ->assign('help_page',$mod->GetHelpPage());
if( $our_lang != 'en_US' && $lang ) {
    NlsOperations::set_language($our_lang);
}

$tpl->display();
