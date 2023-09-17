<?php
/*
Navigator: a module for CMS Made Simple to allow building hierarchical navigations.
Copyright (C) 2013-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\CapabilityType;
use CMSMS\internal\JobOperations;
use CMSMS\LoadedDataType;
use CMSMS\Lone;
use CMSMS\TemplateType;
use CMSMS\Utils as AppUtils;
use Navigator\FillCacheJob;

final class Navigator extends CMSModule
{
    const __DFLT_PAGE = '**DFLT_PAGE**';

    public function GetAdminDescription() { return $this->Lang('description'); }
    public function GetAdminSection() { return 'layout'; }
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return ''; }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetName() { return 'Navigator'; }
    public function GetVersion() { return '2.0'; }
    public function HandlesEvents() { return true; } //since 2.0, deprecated since CMSMS3 in favour of HasCapability(EVENTS)
    public function HasAdmin() { return false; }
    public function IsPluginModule() { return true; } //deprecated since CMSMS3 in favour of HasCapability(PLUGIN_MODULE)
//  public function LazyLoadAdmin() { return true; }
//  public function LazyLoadFrontend() { return true; }
    public function MinimumCMSVersion() { return '2.999'; }

    public function GetHelp($lang='en_US') {
        $this->CreateParameter('action','',$this->Lang('help_action'));
        $this->CreateParameter('childrenof','',$this->Lang('help_childrenof'));
        $this->CreateParameter('collapse','',$this->Lang('help_collapse'));
        $this->CreateParameter('excludeprefix','',$this->Lang('help_excludeprefix'));
        $this->CreateParameter('idnodes','0',$this->Lang('help_idnodes'));
        $this->CreateParameter('includeprefix','',$this->Lang('help_includeprefix'));
        $this->CreateParameter('items','contact,home',$this->Lang('help_items'));
        $this->CreateParameter('loadprops','',$this->Lang('help_loadprops'));
        $this->CreateParameter('nlevels','1',$this->Lang('help_nlevels'));
        $this->CreateParameter('no_of_levels','1',$this->Lang('help_number_of_levels'));
        $this->CreateParameter('number_of_levels','1',$this->Lang('help_number_of_levels'));
        $this->CreateParameter('root','',$this->Lang('help_root2'));
        $this->CreateParameter('show_all','0',$this->Lang('help_show_all'));
        $this->CreateParameter('show_root_siblings','1',$this->Lang('help_show_root_siblings'));
        $this->CreateParameter('start_element','1.2',$this->Lang('help_start_element'));
        $this->CreateParameter('start_level','',$this->Lang('help_start_level'));
        $this->CreateParameter('start_page','',$this->Lang('help_start_page'));
        $this->CreateParameter('start_text','',$this->Lang('help_start_text'));
        $this->CreateParameter('template','',$this->Lang('help_template'));
        return $this->Lang('help');
    }

    public function InitializeFrontend()
    {
        $obj = new LoadedDataType('navigator_data','Navigator\\Utils::fill_cache');
        Lone::get('LoadedData')->add_type($obj);
//CMSMS3 does nothing        $this->RestrictUnknownParams();
        $this->SetParameterType([
        'childrenof' => CLEAN_STRING,
        'collapse' => CLEAN_BOOL, // since CMSMS3 anything cms_to_bool() can process
        'excludeprefix' => CLEAN_STRING,
        'idnodes' => CLEAN_BOOL, // since 2.0
        'includeprefix' => CLEAN_STRING,
        'items' => CLEAN_STRING,
        'loadprops' => CLEAN_BOOL, //deprecated since 2.0 effectively true always
        'nlevels' => CLEAN_INT,
        'no_of_levels' => CLEAN_INT,
        'number_of_levels' => CLEAN_INT,
        'root' => CLEAN_STRING,
        'show_all' => CLEAN_BOOL,
        'show_root_siblings' => CLEAN_BOOL,
        'start_element' => CLEAN_STRING, // yeah, it's a string
        'start_level' => CLEAN_INT,
        'start_page' => CLEAN_STRING,
        'start_text' => CLEAN_STRING,
        'template' => CLEAN_STRING,
        ]);
    }

    public function HasCapability($capability, $params=[])
    {
        switch ($capability) {
//abandoned            case CapabilityType::CORE_MODULE:
            case CapabilityType::EVENTS:
            case CapabilityType::PLUGIN_MODULE:
//          case CapabilityType::TASKS: only when needed
                return TRUE;
            default:
                return FALSE;
        }
    }

    public function RegisterEvents()
    {
        $this->AddEventHandler('ContentManager','AddPost',FALSE);
        $this->AddEventHandler('ContentManager','DeletePost',FALSE);
        $this->AddEventHandler('ContentManager','EditPost',FALSE);
        $this->AddEventHandler('ContentManager','OrderPost',FALSE);
        //TODO etc
    }

    /**
     * Determine whether to generate node-data for a template as Navigator\Nodes
     * @since 2.0
     *
     * @param array $params parameters supplied to the action which will
     * populate the template
     * @param string $name template name
     * @return bool true to provide nodes, false to provide node-ids
     */
    public function TemplateNodes(array $params, string $name): bool
    {
        if( isset($params['idnodes']) && ($params['idnodes'] === '' || cms_to_bool($params['idnodes'])) ) return FALSE;
        if( endswith($name,'.tpl') ) return FALSE;
        //assume the dB-stored default templates are id-compatible
        //names from install.php
        if( array_search($name, ['Breadcrumbs','Simple Navigation','cssmenu','cssmenu_ulshadow','minimal_menu']) !== FALSE ) return FALSE;
        return TRUE;
    }

    /**
     * Event handler to initiate refresh of cached navigation data
     * after changes which might affect such data
     * @since 2.0
     *
     * @param string $originator
     * @param string $eventname
     * @param array $params reference, modifiable
     */
    public function DoEvent($originator, $eventname, &$params)
    {
        switch( $eventname ) {
            case 'EditPost':
            case 'AddPost':
            case 'DeletePost':
            case 'OrderPost':
                if( $originator == 'ContentManager' ) { //TODO all relevant test(s)
                    Lone::get('LoadedData')->release('navigator_data'); // TODO consider this also >> in the Job
                    (new JobOperations())->load_job(new FillCacheJob());
                }
        }
    }

    final public static function nav_breadcrumbs($params, $smarty)
    {
        $params['action'] = 'breadcrumbs';
        $params['module'] = 'Navigator';
        return cms_module_plugin($params,$smarty);
    }

    final public static function tpltype_lang_callback($str)
    {
        $mod = AppUtils::get_module('Navigator');
        return $mod->Lang('type_'.$str);
    }

    public static function reset_tpltype_default(TemplateType $type)
    {
        if( $type->get_originator() != 'Navigator' ) {
            throw new LogicException('Cannot reset content for template-type '.$type->get_name());
        }

        switch( $type->get_name() ) {
        case 'navigation':
            $fn = 'simple_navigation.tpl';
            break;
        case 'breadcrumbs':
            $fn = 'dflt_breadcrumbs.tpl';
            break;
        default:
            return null;
        }

        $file = cms_join_path(__DIR__,'templates',$fn);
        if( is_file($file) ) return @file_get_contents($file);
    }

    public static function tpltype_help_callback($str)
    {
        $str = trim($str);
        $file = cms_join_path(__DIR__,'doc','tpltype_'.$str.'.htm');
        if( is_file($file) ) return file_get_contents($file);
        return '';
    }
} // class
