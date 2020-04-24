<?php
#Search: a module to find words/phrases in 'core' site pages and some modules' pages
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\CoreCapabilities;
use CMSMS\Events;
use Search\Command\ReindexCommand;
use Search\Utils;

const NON_INDEXABLE_CONTENT = '<!-- pageAttribute: NotSearchable -->';

class Search extends CMSModule
{
    public function GetAdminDescription() { return $this->Lang('description'); }
    public function GetAdminSection() { return 'siteadmin'; }
    public function GetAuthor() { return 'Ted Kulp'; }
    public function GetAuthorEmail() { return 'ted@cmsmadesimple.org'; }
    public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetEventDescription( $eventname ) { return $this->lang('eventdesc-' . $eventname); }
    public function GetEventHelp( $eventname ) { return $this->lang('eventhelp-' . $eventname); }
    public function GetFriendlyName() { return $this->Lang('search'); }
    public function GetHelp($lang='en_US') { return $this->Lang('help'); }
    public function GetName() { return 'Search'; }
    public function GetVersion() { return '1.53'; }
    public function HandlesEvents () { return true; }
    public function HasAdmin() { return true; }
    public function IsPluginModule() { return true; } //deprecated
//    public function LazyLoadAdmin() { return true; }
//    public function LazyLoadFrontend() { return false; }
    public function MinimumCMSVersion() { return '2.2.900'; }
    public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

    public function InitializeAdmin()
    {
        $this->CreateParameter('action','default',$this->Lang('param_action'));
        $this->CreateParameter('count','null',$this->Lang('param_count'));
        $this->CreateParameter('detailpage','null',$this->Lang('param_detailpage'));
        $this->CreateParameter('formtemplate','',$this->Lang('param_formtemplate'));
        $this->CreateParameter('inline','false',$this->Lang('param_inline'));
        $this->CreateParameter('modules','null',$this->Lang('param_modules'));
        $this->CreateParameter('pageid','null',$this->Lang('param_pageid'));
        $this->CreateParameter('passthru_*','null',$this->Lang('param_passthru'));
        $this->CreateParameter('resultpage', 'null', $this->Lang('param_resultpage'));
        $this->CreateParameter('resulttemplate','',$this->Lang('param_resulttemplate'));
        $this->CreateParameter('search_method','get',$this->Lang('search_method'));
        $this->CreateParameter('searchtext','null',$this->Lang('param_searchtext'));
        $this->CreateParameter('submit',$this->Lang('searchsubmit'),$this->Lang('param_submit'));
        $this->CreateParameter('use_or','true',$this->Lang('param_useor')); //CHECKME disabled?
    }

    public function InitializeFrontend()
    {
//2.3 does nothing        $this->RestrictUnknownParams();
        $this->SetParameterType('count',CLEAN_INT);
        $this->SetParameterType('detailpage',CLEAN_STRING);
        $this->SetParameterType('formtemplate',CLEAN_STRING);
        $this->SetParameterType('inline',CLEAN_STRING);
        $this->SetParameterType('modules',CLEAN_STRING);
        $this->SetParameterType('origreturnid',CLEAN_INT);
        $this->SetParameterType('pageid',CLEAN_INT);
        $this->SetParameterType('resultpage',CLEAN_STRING);
        $this->SetParameterType('resulttemplate',CLEAN_STRING);
        $this->SetParameterType('search_method',CLEAN_STRING);
        $this->SetParameterType('searchinput',CLEAN_STRING);
        $this->SetParameterType('searchtext',CLEAN_STRING);
        $this->SetParameterType('submit',CLEAN_STRING);
        $this->SetParameterType('use_or',CLEAN_INT); //CHECKME disabled?
        $this->SetParameterType(CLEAN_REGEXP.'/passthru_.*/',CLEAN_STRING);
    }

    protected function GetSearchHtmlTemplate()
    {
        return ''.@file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'search.tpl');
    }

    protected function GetResultsHtmlTemplate()
    {
        return ''.@file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'results.tpl');
    }

    protected function DefaultStopWords()
    {
        return $this->Lang('default_stopwords');
    }

    public function RemoveStopWordsFromArray($words)
    {
        $stop_words = preg_split("/[\s,]+/", $this->GetPreference('stopwords', $this->DefaultStopWords()));
        return array_diff($words, $stop_words);
    }

    public function StemPhrase($phrase)
    {
        return Utils::StemPhrase($this,$phrase);
    }

    public function AddWords($module = 'Search', $id = -1, $attr = '', $content = '', $expires = NULL)
    {
        return Utils::AddWords($this,$module,$id,$attr,$content,$expires);
    }

    public function DeleteWords($module = 'Search', $id = -1, $attr = '')
    {
        return Utils::DeleteWords($this,$module,$id,$attr);
    }

    public function DeleteAllWords($module = 'Search', $id = -1, $attr = '')
    {
        $db = $this->GetDb();
        $db->Execute('TRUNCATE '.CMS_DB_PREFIX.'module_search_index');
        $db->Execute('TRUNCATE '.CMS_DB_PREFIX.'module_search_items');

        Events::SendEvent( 'Search', 'SearchAllItemsDeleted' );
    }

    public function Reindex()
    {
        return Utils::Reindex($this);
    }

    public function RegisterEvents()
    {
        $this->AddEventHandler( 'Core', 'ContentEditPost', false );
        $this->AddEventHandler( 'Core', 'ContentDeletePost', false );
        $this->AddEventHandler( 'Core', 'AddTemplatePost', false );
        $this->AddEventHandler( 'Core', 'EditTemplatePost', false );
        $this->AddEventHandler( 'Core', 'DeleteTemplatePost', false );
        $this->AddEventHandler( 'Core', 'ModuleUninstalled', false );
    }

    public function DoEvent($originator,$eventname,&$params)
    {
        return Utils::DoEvent($this, $originator, $eventname, $params);
    }

    public function HasCapability($capability,$params = [])
    {
        switch( $capability ) {
        case CoreCapabilities::CORE_MODULE:
        case CoreCapabilities::SEARCH_MODULE:
        case CoreCapabilities::PLUGIN_MODULE:
            return true;
        case 'clicommands':
            return class_exists('CMSMS\\CLI\\App'); //TODO better namespace
        }
        return false;
    }

    public static function page_type_lang_callback($str)
    {
        $mod = cms_utils::get_module('Search');
        if( is_object($mod) ) return $mod->Lang('type_'.$str);
    }

    public static function reset_page_type_defaults(CmsLayoutTemplateType $type)
    {
        if( $type->get_originator() != 'Search' ) throw new UnexpectedValueException('Cannot reset contents for this template type');

        $mod = cms_utils::get_module('Search');
        if( !is_object($mod) ) return;
        switch( $type->get_name() ) {
        case 'searchform':
            return $mod->GetSearchHtmlTemplate();
        case 'searchresults':
            return $mod->GetResultsHtmlTemplate();
        }
    }

    /**
     * @since 2.3
     * @throws LogicException
     * @param CMSMS\CLI\App $app (exists only in App mode) TODO better namespace
     * @return array
     */
    public function get_cli_commands( $app ) : array
    {
        $out = [];
        if( parent::get_cli_commands($app) !== null ) {
            $out[] = new ReindexCommand( $app );
        }
        return $out;
    }
} // class
