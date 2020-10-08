<?php
# Navigator: a module for CMS Made Simple to allow building hierarchical navigations.
# Copyright (C) 2013-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

use CMSMS\CoreCapabilities;
use CMSMS\TemplateType;
use CMSMS\Utils;

class NavigatorNode
{
    /**
     * This little function will remove all silly notices in smarty.
     */
    public function __get($key) { return null; }
}

final class Navigator extends CMSModule
{
    const __DFLT_PAGE = '**DFLT_PAGE**';

    public function GetAdminDescription() { return $this->Lang('description'); }
    public function GetAdminSection() { return 'layout'; }
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp($lang='en_US') { return $this->Lang('help'); }
    public function GetName() { return 'Navigator'; }
    public function GetVersion() { return '1.3'; }
    public function HasAdmin() { return false; }
    public function IsPluginModule() { return true; } //deprecated
//    public function LazyLoadAdmin() { return true; }
//    public function LazyLoadFrontend() { return true; }
    public function MinimumCMSVersion() { return '2.2.911'; }

    public function InitializeFrontend()
    {
//2.3 does nothing        $this->RestrictUnknownParams();
        $this->SetParameterType('childrenof',CLEAN_STRING);
        $this->SetParameterType('collapse',CLEAN_INT);
        $this->SetParameterType('excludeprefix',CLEAN_STRING);
        $this->SetParameterType('includeprefix',CLEAN_STRING);
        $this->SetParameterType('items',CLEAN_STRING);
        $this->SetParameterType('loadprops',CLEAN_INT);
        $this->SetParameterType('nlevels',CLEAN_INT);
        $this->SetParameterType('number_of_levels',CLEAN_INT);
        $this->SetParameterType('root',CLEAN_STRING);
        $this->SetParameterType('show_all',CLEAN_INT);
        $this->SetParameterType('show_root_siblings',CLEAN_INT);
        $this->SetParameterType('start_element',CLEAN_STRING); // yeah, it's a string
        $this->SetParameterType('start_level',CLEAN_INT);
        $this->SetParameterType('start_page',CLEAN_STRING);
        $this->SetParameterType('start_text',CLEAN_STRING);
        $this->SetParameterType('template',CLEAN_STRING);
    }

    public function InitializeAdmin()
    {
        $this->CreateParameter('action','',$this->Lang('help_action'));
        $this->CreateParameter('childrenof','',$this->Lang('help_childrenof'));
        $this->CreateParameter('collapse','',$this->Lang('help_collapse'));
        $this->CreateParameter('excludeprefix','',$this->Lang('help_excludeprefix'));
        $this->CreateParameter('includeprefix','',$this->Lang('help_includeprefix'));
        $this->CreateParameter('items', 'contact,home', $this->lang('help_items'));
        $this->CreateParameter('loadprops','',$this->Lang('help_loadprops'));
        $this->CreateParameter('nlevels', '1', $this->lang('help_nlevels'));
        $this->CreateParameter('number_of_levels', '1', $this->lang('help_number_of_levels'));
        $this->CreateParameter('root','',$this->Lang('help_root2'));
        $this->CreateParameter('show_all', '0', $this->lang('help_show_all'));
        $this->CreateParameter('show_root_siblings', '1', $this->lang('help_show_root_siblings'));
        $this->CreateParameter('start_element', '1.2', $this->lang('help_start_element'));
        $this->CreateParameter('start_level', '', $this->lang('help_start_level'));
        $this->CreateParameter('start_page', '', $this->lang('help_start_page'));
        $this->CreateParameter('start_text', '', $this->lang('help_start_text'));
        $this->CreateParameter('template', '', $this->lang('help_template'));
    }

    public function HasCapability($capability, $params=[])
    {
        switch ($capability) {
            case CoreCapabilities::CORE_MODULE:
            case CoreCapabilities::PLUGIN_MODULE:
                return TRUE;
            default:
                return FALSE;
        }
    }

    final public static function nav_breadcrumbs($params, $smarty)
    {
        $params['action'] = 'breadcrumbs';
        $params['module'] = self::class;
        return cms_module_plugin($params,$smarty);
    }

    final public static function page_type_lang_callback($str)
    {
        $mod = Utils::get_module(self::class);
        if( is_object($mod) ) return $mod->Lang('type_'.$str);
    }

    public static function reset_page_type_defaults(TemplateType $type)
    {
        if( $type->get_originator() != self::class ) throw new UnexpectedValueException('Cannot reset contents for this template type');

        $fn = null;
        switch( $type->get_name() ) {
        case 'navigation':
            $fn = 'simple_navigation.tpl';
            break;
        case 'breadcrumbs':
            $fn = 'dflt_breadcrumbs.tpl';
            break;
        }

        $fn = cms_join_path(__DIR__,'templates',$fn);
        if( is_file($fn) ) return @file_get_contents($fn);
    }

    public static function template_help_callback($str)
    {
        $str = trim($str);
        $mod = Utils::get_module('Navigator');
        if( is_object($mod) ) {
            $file = $mod->GetModulePath().'/doc/tpltype_'.$str.'.inc';
            if( is_file($file) ) return file_get_contents($file);
        }
    }
} // class
