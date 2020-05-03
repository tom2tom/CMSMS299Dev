<?php
/*
CMSModuleManager module action: local help
Copyright (C) 2008-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\ModuleOperations;
use CMSMS\NlsOperations;
use CMSMS\Utils;

if( !isset($gCms) ) exit;
//if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
$module = strip_tags(get_parameter_value($params,'mod'));
$lang = strip_tags(get_parameter_value($params,'lang'));

// get the module instance... force it to load if necessary.
$modinstance = ModuleOperations::get_instance()->get_module_instance($module,'',TRUE);
if( !is_object($modinstance) ) {
    $this->SetError($this->Lang('error_getmodule',$module));
    $this->RedirectToAdminTab();
}
$themeObject = Utils::get_theme_object();
$themeObject->SetTitle('module_help');

$our_lang = NlsOperations::get_current_language();

$tpl = $smarty->createTemplate($this->GetTemplateResource('local_help.tpl')); //,null,null,$smarty);
$tpl->assign('our_lang',$our_lang);

if( $our_lang != 'en_US' ) {
    if( $lang != '' ) {
        $tpl->assign('mylang_text',$this->Lang('display_in_mylanguage'))
         ->assign('mylang_url',$this->create_url($id,'local_help',$returnid,['mod'=>$module]));
        NlsOperations::set_language('en_US');
    }
    else {
        $yourlang_url = $this->create_url($id,'local_help',$returnid,['mod'=>$module,'lang'=>'en_US']);
        $tpl->assign('our_lang',$our_lang)
         ->assign('englang_url',$yourlang_url)
         ->assign('englang_text',$this->Lang('display_in_english'));
    }
}

$tpl->assign('module_name',$modinstance->GetName())
 ->assign('friendly_name',$modinstance->GetFriendlyName())
 ->assign('back_url',$this->create_url($id,'defaultadmin',$returnid))

 ->assign('help_page',$modinstance->GetHelpPage());
if( $our_lang != 'en_US' && $lang != '' ) {
    NlsOperations::set_language($our_lang);
}

$tpl->display();
return '';
