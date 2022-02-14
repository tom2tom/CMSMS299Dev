<?php
/*
Navigator module installation process
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

//use CMSMS\TemplatesGroup;
use CMSMS\AppState;
use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use function CMSMS\log_error;

if (empty($this) || !($this instanceof Navigator)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

$me = $this->GetName();

try {
    $menu_type = new TemplateType();
    $menu_type->set_originator($me);
    $menu_type->set_name('navigation');
    $menu_type->set_dflt_flag(TRUE);
    $menu_type->set_lang_callback('Navigator::page_type_lang_callback');
    $menu_type->set_content_callback('Navigator::reset_page_type_defaults');
    $menu_type->set_help_callback('Navigator::template_help_callback');
    $menu_type->reset_content_to_factory();
    $menu_type->save();
}
catch (Throwable $t) {
    // log it
    debug_to_log(__FILE__.':'.__LINE__.' '.$t->GetMessage());
    log_error($me,'Installation error: '.$t->GetMessage());
    return $t->GetMessage();
}

try {
    $crumb_type = new TemplateType();
    $crumb_type->set_originator($me);
    $crumb_type->set_name('breadcrumbs');
    $crumb_type->set_dflt_flag(TRUE);
    $crumb_type->set_lang_callback('Navigator::page_type_lang_callback');
    $crumb_type->set_content_callback('Navigator::reset_page_type_defaults');
    $crumb_type->set_help_callback('Navigator::template_help_callback');
    $crumb_type->reset_content_to_factory();
    $crumb_type->save();
}
catch (Throwable $t) {
    // log it
    debug_to_log(__FILE__.':'.__LINE__.' '.$t->GetMessage());
    log_error($me,'Installation error: '.$t->GetMessage());
    return $t->GetMessage();
}

$newsite = AppState::test(AppState::INSTALL);
if( $newsite ) {
    $uid = 1; // templates owned by initial admin
} else {
    $uid = get_userid();
}

try {
    $fn = cms_join_path(__DIR__,'templates','simple_navigation.tpl');
    if( is_file($fn) ) {
        $content = @file_get_contents($fn);
        $tpl = new Template();
        $tpl->set_originator($me);
        $tpl->set_name(TemplateOperations::get_unique_template_name('Simple Navigation'));
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($menu_type);
        $tpl->set_type_default(TRUE);
        $tpl->save();
    }

    $fn = cms_join_path(__DIR__,'templates','cssmenu.tpl');
    if( is_file($fn) ) {
        $content = @file_get_contents($fn);
        $tpl = new Template();
        $tpl->set_originator($me);
        $tpl->set_name(TemplateOperations::get_unique_template_name('cssmenu'));
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($menu_type);
        $tpl->save();
    }

    $fn = cms_join_path(__DIR__,'templates','cssmenu_ulshadow.tpl');
    if( is_file($fn) ) {
        $content = @file_get_contents($fn);
        $tpl = new Template();
        $tpl->set_originator($me);
        $tpl->set_name(TemplateOperations::get_unique_template_name('cssmenu_ulshadow'));
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($menu_type);
        $tpl->save();
    }

    $fn = cms_join_path(__DIR__,'templates','minimal_menu.tpl');
    if( is_file($fn) ) {
        $content = @file_get_contents($fn);
        $tpl = new Template();
        $tpl->set_originator($me);
        $tpl->set_name(TemplateOperations::get_unique_template_name('minimal_menu'));
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($menu_type);
        $tpl->save();
    }
/* RUBBISH migrate to demo site content
    if( $newsite ) { //TODO also check for demo-content installation
        $extras = [];
        try {
            $fn = cms_join_path(__DIR__,'templates','Simplex_Main_Navigation.tpl');
            if( is_file($fn) ) {
                $content = @file_get_contents($fn);
                $tpl = new Template();
                $tpl->set_originator($me);
                $tpl->set_name(TemplateOperations::get_unique_template_name('Simplex Main Navigation'));
                $tpl->set_owner($uid);
                $tpl->set_content($content);
                $tpl->set_type($menu_type);
                $tpl->save();
                $extras[] = $tpl->get_id();
            }

            $fn = cms_join_path(__DIR__,'templates','Simplex_Footer_Navigation.tpl');
            if( is_file($fn) ) {
                $content = @file_get_contents($fn);
                $tpl = new Template();
                $tpl->set_originator($me);
                $tpl->set_name(TemplateOperations::get_unique_template_name('Simplex Footer Navigation'));
                $tpl->set_owner($uid);
                $tpl->set_content($content);
                $tpl->set_type($menu_type);
                $tpl->save();
                $extras[] = $tpl->get_id();
            }
        }
        catch (Throwable $t) {
            // if we got here, it's prolly because default content was not installed.
            log_error($me,'Installation Error: '.$t->GetMessage());
        }

        if( $extras ) {
            try {
                $ob = TemplatesGroup::load('Simplex');
                $ob->add_members($extras);
                $ob->save();
            }
            catch( Throwable $t) {
                //if modules are installed before demo content, that group won't yet exist
            }
        }
    }
*/
    $fn = cms_join_path(__DIR__,'templates','dflt_breadcrumbs.tpl');
    if( is_file($fn) ) {
        $content = @file_get_contents($fn);
        $tpl = new Template();
        $tpl->set_originator($me);
        $tpl->set_name(TemplateOperations::get_unique_template_name('Breadcrumbs'));
        $tpl->set_owner($uid);
        $tpl->set_content($content);
        $tpl->set_type($crumb_type);
        $tpl->set_type_default(TRUE);
        $tpl->save();
    }
}
catch (Throwable $t) {
    debug_to_log(__FILE__.':'.__LINE__.' '.$t->GetMessage());
    log_error($me,'Installation Error: '.$t->GetMessage());
    return $t->GetMessage();
}

// register plugins
$this->RegisterModulePlugin(TRUE);
$this->RegisterSmartyPlugin('nav_breadcrumbs','function','nav_breadcrumbs');
// MenuManager module hangovers
$this->RegisterSmartyPlugin('menu','function','function_plugin');
$this->RegisterSmartyPlugin('cms_breadcrumbs','function','nav_breadcrumbs');
