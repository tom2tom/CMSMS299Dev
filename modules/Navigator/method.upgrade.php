<?php
/*
Navigator module upgrade process
Copyright (C) 2013-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\internal\JobOperations;
use CMSMS\internal\ModulePluginOperations;
use CMSMS\LoadedDataType;
use CMSMS\Lone;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use Navigator\FillCacheJob;
use function CMSMS\log_error;

if( empty($this) || !($this instanceof Navigator)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

if( version_compare($oldversion,'1.0.5') < 0 ) {
    try {
        $types = TemplateType::load_all_by_originator($this->GetName());
        if( $types ) {
            foreach( $types as $type_obj ) {
                $type_obj->set_help_callback('Navigator::tpltype_help_callback');
                $type_obj->save();
            }
        }
    }
    catch (Throwable $t) {
        log_error($this->GetName(),'Upgrade error: '.$t->GetMessage());
        return $t->GetMessage();
    }
}
if( version_compare($oldversion,'2.0') < 0 ) {
    // replace superseded default-templates
    $names = ['cssmenu','cssmenu_ulshadow','Breadcrumbs','minimal_menu','Simple Navigation'];
    foreach( [
     'cssmenu.tpl',
     'cssmenu_ulshadow.tpl',
     'dflt_breadcrumbs.tpl',
     'minimal_menu.tpl',
     'simple_navigation.tpl',
     ] as $i => $fn ) {
        $fp = cms_join_path(__DIR__,'templates',$fn);
        if( is_file($fp) ) {
            try {
                $tpl = TemplateOperations::get_template('Navigator::'.$names[$i]);
                $content = file_get_contents($fp);
                if( $content && $tpl ) {
                    $tpl->set_content($content);
                    $tpl->save();
                }
            }
            catch (Throwable $t) {
                //nothing here
            }
        }
    }

    // force-deregister superseded MenuManager module plugins
    try {
        ModulePluginOperations::remove_by_name('menu');
        ModulePluginOperations::remove_by_name('cms_breadcrumbs');
    }
    catch (Throwable $t) {
        //nothing here
    }
    $this->RegisterSmartyPlugin('menu','function','function_plugin');
    $this->RegisterSmartyPlugin('cms_breadcrumbs','function','nav_breadcrumbs');
    // setup handling of clear-cache events
    $this->RegisterEvents();
    // init async cache-populate
    $cache = Lone::get('LoadedData');
    $obj = new LoadedDataType('navigator_data','Navigator\\Utils::fill_cache');
    $cache->add_type($obj);
    (new JobOperations())->load_job(new FillCacheJob());
}
