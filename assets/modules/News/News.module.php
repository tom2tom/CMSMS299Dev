<?php
/*
News module for CMSMS
Copyright (C) 2005-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This module is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

This module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AdminMenuItem;
//use CMSMS\AppParams;
use CMSMS\CapabilityType;
use CMSMS\HookOperations;
use CMSMS\Route;
use CMSMS\RouteOperations;
use CMSMS\Lone;
use CMSMS\TemplateType;
use CMSMS\Utils;
use News\AdjustStatusJob;
use News\AdjustStatusTask;
use News\AdminOperations;
use News\CreateDraftAlertJob;
use News\CreateDraftAlertTask;

class News extends CMSModule
{
    // publication-time-granularity enum
    const HOURBLOCK = 1;
    const HALFDAYBLOCK = 2;
    const DAYBLOCK = 3;

/* for CMSMS < 3.0
    public function __construct()
    {
        parent::__construct();
        if( !function_exists('cmsms_spacedloader') ) {
            require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'function.spacedloader.php';
        }
    }
*/
    public function AllowSmartyCaching() { return true; }
    public function GetAdminDescription() { return $this->Lang('description'); }
    public function GetAdminSection() { return 'content'; }
    public function GetAuthor() { return 'Ted Kulp'; }
    public function GetAuthorEmail() { return 'ted@cmsmadesimple.org'; }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetDependencies() { return ['FilePicker' => '1.0']; } // for article- & category-images
    public function GetEventDescription($eventname) { return $this->lang('eventdesc-' . $eventname); }
    public function GetEventHelp($eventname) { return $this->lang('eventhelp-' . $eventname); }
    public function GetFriendlyName() { return $this->Lang('news'); }
    public function GetName() { return 'News'; }
    public function GetVersion() { return '3.1'; }
    public function HandlesEvents() { return true; }
    public function HasAdmin() { return true; }
    public function InstallPostMessage() { return $this->Lang('postinstall');  }
    public function IsPluginModule() { return true; } //deprecated in favour of capability
//  public function LazyLoadAdmin() { return true; }
//  public function LazyLoadFrontend() { return true; }
    public function MinimumCMSVersion() { return '2.999'; }

    public function GetHelp() {
        $this->CreateParameter('action', 'default', $this->Lang('helpaction'));
        $this->CreateParameter('articleid', '', $this->Lang('help_articleid'));
        $this->CreateParameter('browsecat', 0, $this->Lang('helpbrowsecat'));
        $this->CreateParameter('browsecattemplate', '', $this->Lang('helpbrowsecattemplate'));
//      $this->CreateParameter('category_id', ... CLEAN_INT);
        $this->CreateParameter('category', 'category', $this->Lang('helpcategory'));
        $this->CreateParameter('detailpage', 'pagealias', $this->Lang('helpdetailpage'));
        $this->CreateParameter('detailtemplate', '', $this->Lang('helpdetailtemplate'));
        $this->CreateParameter('idlist', '', $this->Lang('help_idlist'));
//      $this->CreateParameter('lang', ... CLEAN_STRING); //TODO explain
        $this->CreateParameter('moretext', $this->Lang('moreprompt'), $this->Lang('helpmoretext'));
        $this->CreateParameter('number', 100000, $this->Lang('helpnumber'));
        $this->CreateParameter('pagelimit', 1000, $this->Lang('help_pagelimit'));
//      $this->CreateParameter('pagenumber', ...CLEAN_INT);
//      $this->CreateParameter('preview', ....CLEAN_STRING); //hashed preview data
        $this->CreateParameter('showall', 0, $this->Lang('helpshowall'));
        $this->CreateParameter('showarchive', 0, $this->Lang('helpshowarchive'));
        $this->CreateParameter('sortasc', 'true', $this->Lang('helpsortasc'));
        $this->CreateParameter('sortby', 'start_time', $this->Lang('helpsortby'));
        $this->CreateParameter('start', 0, $this->lang('helpstart'));
        $this->CreateParameter('summarytemplate', '', $this->Lang('helpsummarytemplate'));
        return $this->Lang('help');
    }

    public function InitializeFrontend()
    {
/*      $this->RestrictUnknownParams(); does nothing in 3.0+
        $this->SetParameterType('articleid', CLEAN_INT);
        $this->SetParameterType('assign', CLEAN_STRING);
        $this->SetParameterType('browsecat', CLEAN_INT);
        $this->SetParameterType('browsecattemplate', CLEAN_STRING);
        $this->SetParameterType('category_id', CLEAN_STRING);
        $this->SetParameterType('category', CLEAN_STRING);
        $this->SetParameterType('detailpage', CLEAN_STRING);
        $this->SetParameterType('detailtemplate', CLEAN_STRING);
        $this->SetParameterType('formtemplate', CLEAN_STRING);
        $this->SetParameterType('idlist', CLEAN_STRING);
        $this->SetParameterType('inline', CLEAN_STRING);
        $this->SetParameterType('moretext', CLEAN_STRING);
        $this->SetParameterType('number', CLEAN_INT);
        $this->SetParameterType('origid', CLEAN_INT);
        $this->SetParameterType('pagelimit', CLEAN_INT);
        $this->SetParameterType('pagenumber', CLEAN_INT);
        $this->SetParameterType('preview', CLEAN_STRING);
        $this->SetParameterType('showall', CLEAN_INT);
        $this->SetParameterType('showarchive', CLEAN_INT);
        $this->SetParameterType('sortasc', CLEAN_STRING); // or _INT or _BOOL?
        $this->SetParameterType('sortby', CLEAN_STRING);
        $this->SetParameterType('start', CLEAN_INT);
        $this->SetParameterType('summarytemplate', CLEAN_STRING);
*/
/*
//$params used in action.default.php
'browsecat'
'category_id'
'category'
'detailpage'
'detailtemplate'
'idlist'
'lang'
'moretext'
'number'
'pagelimit'
'pagenumber'
'showall'
'showarchive'
'sortasc'
?? sortby
'start'
'summarytemplate'
//$params used in or downstream from action.browsecat.php
'browsecattemplate'
'category'
'detailpage'
'showarchive'
//$params used in or downstream from action.detail.php
'articleid'
'category_id'
'detailtemplate'
'origid'
'preview'
*/
        $this->SetParameterType([
        'articleid' => CLEAN_INT,
        'browsecat' => CLEAN_INT, //redirection flag OR _BOOL
        'browsecattemplate' => CLEAN_STRING, //general template name or News template like 'somefilename.tpl'
        'category_id' => CLEAN_INT,
        'category' => CLEAN_STRING, //comma-separated name(s)
        'detailpage' => CLEAN_STRING, //page id or alias
        'detailtemplate' => CLEAN_STRING, //name
        'idlist' => CLEAN_STRING, //??
        'lang' => CLEAN_STRING, //TODO explain
        'moretext' => CLEAN_STRING, //label, TODO ever submitted? just for admin ?
        'number' => CLEAN_INT, //alias for pagenumber ??
        'origid' => CLEAN_INT,
        'pagelimit' => CLEAN_INT,
        'pagenumber' => CLEAN_INT,
        'preview' => CLEAN_STRING, //hashed preview data
//      'returnid' => CLEAN_INT, generic (all actions)
        'showall' => CLEAN_INT, //??
        'showarchive' => CLEAN_INT,
        'sortasc' => CLEAN_STRING, // 'true'|'false'
        'sortby' => CLEAN_STRING, //TODO needed?
        'start' => CLEAN_INT, //offset of 1st displayed item
        'summarytemplate' => CLEAN_STRING, //name
        ]);
    }

    public function InitializeAdmin()
    {
        HookOperations::add_hook('ExtraSiteSettings', [$this, 'ExtraSiteSettings']);
    }

    public function VisibleToAdminUser()
    {
        return $this->CheckPermission('Modify News') ||
            $this->CheckPermission('Propose News') ||
            $this->CheckPermission('Approve News') ||
            $this->CheckPermission('Delete News') ||
            $this->CheckPermission('Modify News Preferences');
    }

    /**
     * Search-module support method - get TBA
     *
     * @param int $returnid page identifier
     * @param int $articleid news article identifier
     * @param string $attr target-identifier. Only 'article' is recognized here
     * @param array $params
     * @return array 3 members
     */
    public function SearchResultWithParams($returnid, $articleid, $attr = '', $params = [])
    {
        $result = [];

        if( $attr == 'article' ) {
            $db = $this->GetDb();
            $query = 'SELECT news_title,news_url FROM '.CMS_DB_PREFIX.'module_news WHERE news_id = ?';
            $row = $db->getRow($query, [$articleid]);

            if( $row ) {
                //position 0 is the prefix displayed in the list results
                $result[0] = $this->GetFriendlyName();

                //position 1 is the title
                $result[1] = $row['news_title'];

                //position 2 is the URL to the title
                $detailpage = $returnid;
                if( isset($params['detailpage']) ) {
                    $ptops = cmsms()->GetHierarchyManager();
                    $id = $ptops->find_by_identifier($params['detailpage'], false);
                    if( $id ) {
                        $detailpage = $id;
                    }
                }

                $detailtemplate = '';
                if( isset($params['detailtemplate']) ) {
                    if( !isset($ptops) ) { $ptops = cmsms()->GetHierarchyManager(); }
                    $node = $ptops->find_by_tag('alias', $params['detailtemplate']);
                    if( $node ) {
                        $detailtemplate = '/d,' . $params['detailtemplate'];
                    }
                }

                $prettyurl = $row['news_url'];
                if( !$prettyurl ) {
                    $str = munge_string_to_url($row['news_title']); // OR some other algorithm e.g. condense()?
                    $prettyurl = 'News/' . $articleid. '/' . $detailpage .'/' .$str . $detailtemplate;
                }

                $parms = [];
                $parms['articleid'] = $articleid;
                if( isset($params['detailtemplate']) ) { $parms['detailtemplate'] = $params['detailtemplate']; }
                $result[2] = $this->CreateLink('cntnt01', 'detail', $detailpage, '', $parms , '', true, false, '', true, $prettyurl);
            }
        }

        return $result;
    }

    /**
     * Search-module support method - update search index
     *
     * @param Search-module object $search
     */
    public function SearchReindex($search)
    {
        $nsexp = $this->GetPreference('expired_searchable', 0) == 0; // TODO also use this for $query
        $db = $this->GetDb();
        $query = 'SELECT news_id,news_title,news_data,summary,end_time FROM '.CMS_DB_PREFIX.
        'module_news WHERE searchable = 1 AND status = \'published\' OR status = \'final\' ORDER BY start_time';
        $rst = $db->execute($query);
        if( $rst ) {
            while( !$rst->EOF() ) {
                $search->AddWords($this->GetName(),
                    $rst->fields['news_id'], 'article',
                    $rst->fields['news_data'] . ' ' . $rst->fields['summary'] . ' ' . $rst->fields['news_title'] . ' ' . $rst->fields['news_title'],
                    ($nsexp && $rst->fields['end_time'] != NULL) ? $rst->fields['end_time'] : NULL);
                $rst->MoveNext();
            }
            $rst->Close();
        }
    }

/*
    public function GetFieldTypes()
    {
        return [
         'textbox'=>$this->Lang('textbox'),
         'checkbox'=>$this->Lang('checkbox'),
         'textarea'=>$this->Lang('textarea'),
         'dropdown'=>$this->Lang('dropdown'),
         'linkedfile'=>$this->Lang('linkedfile'),
         'file'=>$this->Lang('file'),
        ];
    }

    public function GetTypesDropdown( $id, $name, $selected = '' )
    {
        $items = $this->GetFieldTypes();
        return $this->CreateInputDropdown($id, $name, array_flip($items), -1, $selected);
    }
*/
    //TODO some of these might be better placed in the Utils class

    public function GetDateFormat() : string
    {
        return $this->GetPreference('date_format', '%e %B %Y %l:%M %p'); //TODO replace deprecated strftime() formats
    }

    /**
     * Migrate the supplied string from database datetime-field format
     * like 'Y-m-d H:i:s' to the preferred News-presentation format
     *
     * @param mixed $datetime string | null
     * @return string, maybe empty
     */
    public function FormatforDisplay($datetime) : string
    {
        if( $datetime ) {
            $fmt = $this->GetDateFormat();
            $t = strtotime($datetime);
            return locale_ftime($fmt, $t);
        }
        return ''.$datetime;
    }

    public function CreateStaticRoutes()
    {
        $str = $this->GetName();
        RouteOperations::del_static('', $str);

        $db = Lone::get('Db');
        $c = strtoupper($str[0]);
        $x = substr($str, 1);
        $x1 = '['.$c.strtolower($c).']'.$x;

        $route = new Route('/'.$x1.'\/(?<articleid>[0-9]+)\/(?<returnid>[0-9]+)\/(?<junk>.*?)\/d,(?<detailtemplate>.*?)$/',
                              $str);
        RouteOperations::add_static($route);
        $route = new Route('/'.$x1.'\/(?<articleid>[0-9]+)\/(?<returnid>[0-9]+)\/(?<junk>.*?)$/', $str);
        RouteOperations::add_static($route);
        $route = new Route('/'.$x1.'\/(?<articleid>[0-9]+)\/(?<returnid>[0-9]+)$/', $str);
        RouteOperations::add_static($route);
        $route = new Route('/'.$x1.'\/(?<articleid>[0-9]+)$/', $str,
                              ['returnid'=>$this->GetPreference('detail_returnid', -1)]);
        RouteOperations::add_static($route);

        $pref = CMS_DB_PREFIX;
        $longnow = $db->DbTimeStamp(time());
        $nonull = $db->ifNull('start_time', '2000-1-1');
        $query = <<<EOS
SELECT news_id,news_url FROM {$pref}module_news
WHERE status = 'published' AND news_url IS NOT NULL AND news_url != '' AND $nonull <= $longnow AND (end_time IS NULL OR end_time > $longnow)
ORDER BY start_time DESC
EOS;
        $tmp = $db->getArray($query);

        if( $tmp ) {
            foreach( $tmp as $one ) {
                AdminOperations::register_static_route($one['news_url'], $one['news_id']);
            }
        }
    }

    public static function tpltype_lang_callback($str)
    {
        $mod = Utils::get_module('News');
        if( is_object($mod) ) {
            return $mod->Lang('type_'.$str);
        }
        return '';
    }

    public static function tpltype_help_callback($str)
    {
        $mod = Utils::get_module('News');
        if( is_object($mod) ) {
            $file = cms_join_path($mod->GetModulePath(), 'doc', 'tpltype_'.trim($str).'.htm');
            if( is_file($file) ) {
                $base = $mod->GetModuleURLPath();
                // OR $csm = new ... $csm->queue_matchedfile( );
                add_page_headtext('<link rel="stylesheet" href="'.$base.'/css/modhelp.css" />');
                return file_get_contents($file);
            }
        }
        return '';
    }

    public static function reset_tpltype_default(TemplateType $type)
    {
        if( $type->get_originator() != 'News' ) {
            throw new LogicException('Cannot reset contents for this template type');
        }

        switch( $type->get_name() ) {
        case 'summary':
            $fn = 'summary_template.tpl';
            break;
        case 'detail':
            $fn = 'detail_template.tpl';
            break;
        case 'browsecat':
            $fn = 'browsecat.tpl';
            break;
        case 'approvalmessage':
            $fn = 'approval_email.tpl';
            break;
        }

        $fn = cms_join_path(__DIR__, 'templates', $fn);
        if( is_file($fn) ) {
            return @file_get_contents($fn);
        }
        return '';
    }

    public function HasCapability($capability, $params = [])
    {
        switch( $capability ) {
           case CapabilityType::PLUGIN_MODULE:
           case CapabilityType::ADMINSEARCH:
           case CapabilityType::TASKS:
           case CapabilityType::EVENTS:
//         case CapabilityType::ROUTE_MODULE: when defined
           case CapabilityType::SITE_SETTINGS:
              return true;
        }
        return false;
    }

    /**
     * Event handler to adjust ownership during user-removal
     * @since 3.1
     *
     * @param string $originator
     * @param string $eventname
     * @param array $params
     */
    public function DoEvent($originator, $eventname, &$params)
    {
        switch ($eventname) {
            case 'DeleteUserPre':
                if ($originator == 'Core') {
                    $user = $params['user'];
                    $db = $this->GetDb();
                    $db->execute('UPDATE '.CMS_DB_PREFIX.'module_news SET author_id = 1 WHERE author_id = '.(int)$user->id);
                }
        }
    }

    public function get_tasks()
    {
        if( version_compare(CMS_VERSION, '2.2') < 0 ) {
            $out = [new AdjustStatusTask()];
            if( $this->GetPreference('alert_drafts', 1) ) {
                $out[] = new CreateDraftAlertTask();
            }
        }
        else {
            $out = [new AdjustStatusJob()];
            if( $this->GetPreference('alert_drafts', 1) ) {
                $out[] = new CreateDraftAlertJob();
            }
        }
        return $out;
    }

    public function get_adminsearch_slaves()
    {
        return ['News\AdminSearch_slave'];
    }

    public function GetAdminMenuItems()
    {
        if( $this->VisibleToAdminUser() ) {
            return [AdminMenuItem::from_module($this)];
        }
        return [];
    }

    /**
     * Hook function to populate 'centralised' site settings UI
     * @internal
     * @since 3.1
     *
     * @return array
     */
    public function ExtraSiteSettings()
    {
        //TODO check permission local or Site Prefs
        return [
         'title' => $this->Lang('settings_title'),
         //'desc' => 'useful text goes here', // optional useful text
         'url' => $this->create_action_url('', 'defaultadmin', ['activetab'=>'settings']), // if permitted
         //optional 'text' => custom link-text | explanation e.g need permission
        ];
    }
} // class
