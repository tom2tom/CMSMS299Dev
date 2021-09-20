<?php
/*
Search: a module to find words/phrases in 'core' site pages and some modules' pages
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\CoreCapabilities;
use CMSMS\Events;
use CMSMS\HookOperations;
use CMSMS\TemplateType;
use CMSMS\Utils as AppUtils;
//use Search\Command\ReindexCommand;
//use Search\PruneJob;
use Search\Utils as Utils;

class Search extends CMSModule
{
    const NON_INDEXABLE_CONTENT = '<!-- pageAttribute: NotSearchable -->'; //formerly-global constant

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
    public function GetVersion() { return '2.0'; }
    public function HandlesEvents () { return true; }
    public function HasAdmin() { return true; }
    public function IsPluginModule() { return true; } //deprecated
//  public function LazyLoadAdmin() { return true; }
//  public function LazyLoadFrontend() { return false; }
    public function MinimumCMSVersion() { return '2.99'; }
    public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

    public function InitializeAdmin()
    {
        HookOperations::add_hook('ExtraSiteSettings',[$this,'ExtraSiteSettings']);

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
//CMSMS 2.99 does nothing        $this->RestrictUnknownParams();
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

    public function RegisterEvents()
    {
        $this->AddEventHandler('Core','ContentEditPost',false);
        $this->AddEventHandler('Core','ContentDeletePost',false);
        $this->AddEventHandler('Core','AddTemplatePost',false);
        $this->AddEventHandler('Core','EditTemplatePost',false);
        $this->AddEventHandler('Core','DeleteTemplatePost',false);
        $this->AddEventHandler('Core','ModuleUninstalled',false);
    }

    /**
     *
     * @param string $originator
     * @param string $eventname
     * @param array $params
     */
    public function DoEvent($originator, $eventname, &$params)
    {
        if ($originator != 'Core') {
            return;
        }

        switch ($eventname) {
        case 'ContentEditPost':
            if (empty($params['content'])) {
                return;
            }
            $content = $params['content'];
            if (!is_object($content)) {
                return;
            }

            //Ruud suggestion: defer deletion to next AddWords() call
            Utils::DeleteWords($this->GetName(), $content->Id(), 'content');
            if ($content->Active() && $content->IsSearchable()) {
                $text = str_repeat(' '.$content->Name(), 2) . ' ';
                $text .= str_repeat(' '.$content->MenuText(), 2) . ' ';

                $props = $content->Properties();
                if ($props) {
                    foreach ($props as $k => $v) {
                        $text .= $v.' ';
                    }
                }

                // here check for a string to see
                // if module content is indexable at all
                $non_indexable = (strpos($text, NON_INDEXABLE_CONTENT) !== false);
                $text = trim(strip_tags($text));
                if ($text && !$non_indexable) {
                    Utils::AddWords($this, $this->GetName(), $content->Id(), 'content', $text);
                }
            }
            break;

        case 'ContentDeletePost':
            if (!empty($params['content'])) {
                $content = $params['content'];
                Utils::DeleteWords($this->GetName(), $content->Id(), 'content');
            }
            break;

        case 'ModuleUninstalled':
            Utils::DeleteWords($params['name']);
            break;
        }
    }

    public function HasCapability($capability,$params = [])
    {
        switch( $capability ) {
        case CoreCapabilities::CORE_MODULE:
//        case CoreCapabilities::TASKS:
        case CoreCapabilities::SEARCH_MODULE:
        case CoreCapabilities::PLUGIN_MODULE:
        case CoreCapabilities::SITE_SETTINGS:
            return true;
//        case 'clicommands':
//            return class_exists('CMSMS\CLI\App'); //TODO better namespace
        }
        return false;
    }

/*  public function get_tasks()
    {
        return [new PruneJob()];
    }
*/
    /* *
     * @since MAYBE IN FUTURE
     * @throws LogicException
     * @param CMSMS\CLI\App $app (exists only in App mode) TODO better namespace
     * @return array
     * /
    public function get_cli_commands( $app ) : array
    {
        $out = [];
        if( parent::get_cli_commands($app) !== null ) {
            $out[] = new ReindexCommand( $app );
        }
        return $out;
    }
*/
    /**
     * Hook function to populate centralised site-settings UI
     * @internal
     * @since 2.0
     * @return array
     */
    public function ExtraSiteSettings()
    {
        //TODO check permission local or Site Prefs
        return [
         'title'=>$this->Lang('settings_title', $this->GetName()),
         //'desc'=>'useful text goes here', // optional useful text
         'url'=>$this->create_action_url('m1_', 'defaultadmin', ['activetab'=>'options']), // if permitted
         //optional 'text' => custom link-text | explanation e.g need permission
        ];
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
        return Utils::CleanWords($this->Lang('default_stopwords'));
    }

    public function RemoveStopWordsFromArray($words)
    {
        return Utils::RemoveStopWordsFromArray($this, $words);
    }

    public function StemPhrase($phrase)
    {
        return Utils::StemPhrase($this, $phrase);
    }

    public function AddWords($module = 'Search', $id = -1, $attr = '', $content = '', $expires = NULL)
    {
        return Utils::AddWords($this, $module,$id, $attr, $content, $expires);
    }

    public function DeleteWords($module = 'Search', $id = -1, $attr = '')
    {
        return Utils::DeleteWords($module, $id, $attr);
    }

    /**
     * @param $module UNUSED
     * @param $id UNUSED
     * @param $attr UNUSED
     */
    public function DeleteAllWords($module = 'Search', $id = -1, $attr = '')
    {
        Utils::DeleteAllWords();
    }

    public function Reindex()
    {
        return Utils::Reindex($this);
    }

    public static function page_type_lang_callback($str)
    {
        $mod = AppUtils::get_module('Search');
        if( is_object($mod) ) {
            return $mod->Lang('type_'.$str);
        }
    }

    public static function reset_page_type_defaults(TemplateType $type)
    {
        if( $type->get_originator() != 'Search' ) {
            throw new LogicException('Cannot reset content for template-type '.$type->get_name());
        }
        $mod = AppUtils::get_module('Search');
        if( is_object($mod) ) {
            switch( $type->get_name() ) {
            case 'searchform':
                return $mod->GetSearchHtmlTemplate();
            case 'searchresults':
                return $mod->GetResultsHtmlTemplate();
            }
        }
    }
} // class
