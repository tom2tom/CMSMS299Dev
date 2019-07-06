<?php
/*
News module for CMSMS
Copyright (C) 2005-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\RouteOperations;
use News\AdjustStatusTask;
use News\AdminOperations;
use News\CreateDraftAlertTask;

class News extends CMSModule
{
    // publication-time-granularity enum
    const HOURBLOCK = 1;
    const HALFDAYBLOCK = 2;
    const DAYBLOCK = 3;

    public function AllowSmartyCaching() { return true; }
    public function GetAdminDescription() { return $this->Lang('description'); }
    public function GetAdminSection() { return 'content'; }
    public function GetAuthor() { return 'Ted Kulp'; }
    public function GetAuthorEmail() { return 'ted@cmsmadesimple.org'; }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetEventDescription($eventname) { return $this->lang('eventdesc-' . $eventname); }
    public function GetEventHelp($eventname) { return $this->lang('eventhelp-' . $eventname); }
    public function GetFriendlyName() { return $this->Lang('news'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetName() { return 'News'; }
    public function GetVersion() { return '3.0'; }
    public function HasAdmin() { return true; }
    public function InstallPostMessage() { return $this->Lang('postinstall');  }
    public function IsPluginModule() { return true; } //deprecated in favour of capability
//    public function LazyLoadAdmin() { return true; }
//    public function LazyLoadFrontend() { return true; }
    public function MinimumCMSVersion() { return '2.2.911'; }

    public function InitializeFrontend()
    {
/*
        $this->SetParameterType('assign', CLEAN_STRING);
        $this->SetParameterType('browsecat', CLEAN_INT);
        $this->SetParameterType('browsecattemplate', CLEAN_STRING);
        $this->SetParameterType('category', CLEAN_STRING);
        $this->SetParameterType('category_id', CLEAN_STRING);
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
        $this->SetParameterType('sortasc', CLEAN_STRING); // or int or boolean ?
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

//$params used in action.browsecat.php
'browsecattemplate'
*/
		//some of these are probably redundant in the frontend
        $this->SetParameterType('articleid', CLEAN_INT);
        $this->SetParameterType('browsecat', CLEAN_INT); //??
        $this->SetParameterType('browsecattemplate', CLEAN_STRING); //name
        $this->SetParameterType('category_id', CLEAN_INT);
        $this->SetParameterType('category', CLEAN_STRING); //??
        $this->SetParameterType('detailpage', CLEAN_STRING); //page id or alias
        $this->SetParameterType('detailtemplate', CLEAN_STRING); //name
        $this->SetParameterType('idlist', CLEAN_STRING); //??
        $this->SetParameterType('lang', CLEAN_STRING); //TODO explain
        $this->SetParameterType('moretext', CLEAN_STRING); //TODO submitted?
        $this->SetParameterType('number', CLEAN_INT); //alias for pagenumber ??
        $this->SetParameterType('pagelimit', CLEAN_INT);
        $this->SetParameterType('pagenumber', CLEAN_INT);
        $this->SetParameterType('returnid', CLEAN_INT);
        $this->SetParameterType('showall', CLEAN_INT); //??
        $this->SetParameterType('showarchive', CLEAN_INT); //??
        $this->SetParameterType('sortasc', CLEAN_STRING); // ''true'|'false'
        $this->SetParameterType('sortby', CLEAN_STRING); //TODO needed?
        $this->SetParameterType('start', CLEAN_INT); //offset of 1st displayed item
        $this->SetParameterType('summarytemplate', CLEAN_STRING); //name
    }

    public function InitializeAdmin()
    {
        $this->CreateParameter('action','default',$this->Lang('helpaction'));
        $this->CreateParameter('articleid','',$this->Lang('help_articleid'));
        $this->CreateParameter('browsecat', 0, $this->Lang('helpbrowsecat'));
        $this->CreateParameter('browsecattemplate', '', $this->Lang('helpbrowsecattemplate'));
        $this->CreateParameter('category', 'category', $this->Lang('helpcategory'));
        $this->CreateParameter('detailpage', 'pagealias', $this->Lang('helpdetailpage'));
        $this->CreateParameter('detailtemplate', '', $this->Lang('helpdetailtemplate'));
        $this->CreateParameter('idlist','',$this->Lang('help_idlist'));
        $this->CreateParameter('moretext', $this->Lang('moreprompt'), $this->Lang('helpmoretext'));
        $this->CreateParameter('number', 100000, $this->Lang('helpnumber'));
        $this->CreateParameter('pagelimit', 1000, $this->Lang('help_pagelimit'));
        $this->CreateParameter('showall', 0, $this->Lang('helpshowall'));
        $this->CreateParameter('showarchive', 0, $this->Lang('helpshowarchive'));
        $this->CreateParameter('sortasc', 'true', $this->Lang('helpsortasc'));
        $this->CreateParameter('sortby', 'start_time', $this->Lang('helpsortby'));
        $this->CreateParameter('start', 0, $this->lang('helpstart'));
        $this->CreateParameter('summarytemplate', '', $this->Lang('helpsummarytemplate'));
    }

    public function VisibleToAdminUser()
    {
        return $this->CheckPermission('Modify News') ||
            $this->CheckPermission('Approve News') ||
            $this->CheckPermission('Delete News') ||
            $this->CheckPermission('Modify News Preferences');
    }
/*
    public function GetDfltEmailTemplate()
    {
        return <<<EOS
A new news article has been posted to the website. The details are as follows:
Title:      {\$title}
IP Address: {\$ipaddress}
Summary:    {\$summary|strip_tags}
Start Date: {\$startdate|cms_date_format}
End Date:   {\$enddate|cms_date_format}
EOS;
    }
*/
    public function SearchResultWithParams($returnid, $articleid, $attr = '', $params = '')
    {
        $result = [];

        if ($attr == 'article') {
            $db = $this->GetDb();
            $q = 'SELECT news_title,news_url FROM '.CMS_DB_PREFIX.'module_news WHERE news_id = ?';
            $row = $db->GetRow( $q, [ $articleid ] );

            if ($row) {
                $gCms = CmsApp::get_instance();
                //0 position is the prefix displayed in the list results.
                $result[0] = $this->GetFriendlyName();

                //1 position is the title
                $result[1] = $row['news_title'];

                //2 position is the URL to the title.
                $detailpage = $returnid;
                if( isset($params['detailpage']) ) {
                    $hm = $gCms->GetHierarchyManager();
                    $id = $hm->find_by_identifier($params['detailpage'],false);
                    if( $id ) {
                        $detailpage = $id;
                    }
                }

                $detailtemplate = '';
                if( isset($params['detailtemplate']) ) {
                    if( !isset($hm) ) $hm = $gCms->GetHierarchyManager();
                    $node = $hm->find_by_tag('alias',$params['detailtemplate']);
                    if( $node ) $detailtemplate = '/d,' . $params['detailtemplate'];
                }

                $prettyurl = $row['news_url'];
                if( $row['news_url'] == '' ) {
                    $aliased_title = munge_string_to_url($row['news_title']);
                    $prettyurl = 'news/' . $articleid.'/'.$detailpage."/$aliased_title".$detailtemplate;
                }

                $parms = [];
                $parms['articleid'] = $articleid;
                if( isset($params['detailtemplate']) ) $parms['detailtemplate'] = $params['detailtemplate'];
                $result[2] = $this->CreateLink('cntnt01', 'detail', $detailpage, '', $parms ,'', true, false, '', true, $prettyurl);
            }
        }

        return $result;
    }

    public function SearchReindex(&$module)
    {
        $db = $this->GetDb();

        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news WHERE searchable = 1 AND status = \'published\' OR status = \'final\' ORDER BY start_time';
        $rst = $db->Execute($query);
		$nsexp = $this->GetPreference('expired_searchable',0) == 0;
        while ($rst && !$rst->EOF) {
            $module->AddWords($this->GetName(),
                              $rst->fields['news_id'], 'article',
                              $rst->fields['news_data'] . ' ' . $rst->fields['summary'] . ' ' . $rst->fields['news_title'] . ' ' . $rst->fields['news_title'],
                              ($nsexp && $rst->fields['end_time'] != NULL) ? $rst->fields['end_time'] : NULL);
            $rst->MoveNext();
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
	public function GetDateFormat() : string
	{
		$fmt = $this->GetPreference('date_format');
		if (!$fmt) {
			$fmt = cms_siteprefs::get('defaultdateformat','%Y-%m-%e %H:%M');
		}
		return $fmt;
	}

    public function GetNotificationOutput($priority = 2)
    {
        // if this user has permission to change News articles from
        // draft to final, and there are draft news articles,
        // then display a nice message.
        // this is a priority 2 item.
        if( $priority >= 2 ) {
            $output = [];
            if( $this->CheckPermission('Approve News') ) {
                $db = $this->GetDb();
                $now = time();
                $query = 'SELECT count(news_id) FROM '.CMS_DB_PREFIX.'module_news WHERE status != \'published\' AND status != \'final\'
                  AND (end_time IS NULL OR end_time > '.$now.')';
                $count = $db->GetOne($query);
                if( $count ) {
                    $obj = new stdClass();
                    $obj->priority = 2;
                    $link = $this->CreateLink('m1_','defaultadmin','', $this->Lang('notify_n_draft_items_sub',$count));
                    $obj->html = $this->Lang('notify_n_draft_items',$link);
                    $output[] = $obj;
                }
            }
        }
        return $output;
    }

    public function CreateStaticRoutes()
    {
        $str = $this->GetName();
        RouteOperations::del_static('',$str);

        $db = CmsApp::get_instance()->GetDb();
        $c = strtoupper($str[0]);
        $x = substr($str,1);
        $x1 = '['.$c.strtolower($c).']'.$x;

        $route = new CmsRoute('/'.$x1.'\/(?P<articleid>[0-9]+)\/(?P<returnid>[0-9]+)\/(?P<junk>.*?)\/d,(?P<detailtemplate>.*?)$/',
                              $str);
        RouteOperations::add_static($route);
        $route = new CmsRoute('/'.$x1.'\/(?P<articleid>[0-9]+)\/(?P<returnid>[0-9]+)\/(?P<junk>.*?)$/',$str);
        RouteOperations::add_static($route);
        $route = new CmsRoute('/'.$x1.'\/(?P<articleid>[0-9]+)\/(?P<returnid>[0-9]+)$/',$str);
        RouteOperations::add_static($route);
        $route = new CmsRoute('/'.$x1.'\/(?P<articleid>[0-9]+)$/',$str,
                              ['returnid'=>$this->GetPreference('detail_returnid',-1)]);
        RouteOperations::add_static($route);

        $now = time();
        $query = 'SELECT news_id,news_url FROM '.CMS_DB_PREFIX.'module_news WHERE status = ? AND news_url != \'\' AND '
            . '('.$db->ifNull('start_time',1).' < '.$now.') AND (end_time IS NULL OR end_time > '.$now.')';
        $query .= ' ORDER BY start_time DESC';
        $tmp = $db->GetArray($query,['published']);

        if( is_array($tmp) ) {
            foreach( $tmp as $one ) {
                AdminOperations::register_static_route($one['news_url'],$one['news_id']);
            }
        }
    }

    public static function page_type_lang_callback($str)
    {
        $mod = cms_utils::get_module('News');
        if( is_object($mod) ) return $mod->Lang('type_'.$str);
    }

    public static function template_help_callback($str)
    {
        $str = trim($str);
        $mod = cms_utils::get_module('News');
        if( is_object($mod) ) {
            $file = $mod->GetModulePath().'/doc/tpltype_'.$str.'.inc';
            if( is_file($file) ) return file_get_contents($file);
        }
    }

    public static function reset_page_type_defaults(CmsLayoutTemplateType $type)
    {
        if( $type->get_originator() != 'News' ) throw new CmsLogicException('Cannot reset contents for this template type');

        $fn = null;
        switch( $type->get_name() ) {
        case 'summary':
            $fn = 'orig_summary_template.tpl';
            break;

        case 'detail':
            $fn = 'orig_detail_template.tpl';
            break;

        case 'form':
            $fn = 'orig_form_template.tpl';
            break;

        case 'browsecat':
            $fn = 'browsecat.tpl';
        }

        $fn = cms_join_path(__DIR__,'templates',$fn);
        if( is_file($fn) ) return @file_get_contents($fn);
    }

    public function HasCapability($capability, $params = [])
    {
        switch( $capability ) {
           case CmsCoreCapabilities::PLUGIN_MODULE:
           case CmsCoreCapabilities::ADMINSEARCH:
           case CmsCoreCapabilities::TASKS:
              return true;
        }
        return false;
    }

    public function get_tasks()
    {
        $out = [new AdjustStatusTask()];
        if( $this->GetPreference('alert_drafts',1) ) {
            $out[] = new CreateDraftAlertTask();
        }
        return $out;
    }

    public function get_adminsearch_slaves()
    {
        return ['News\\AdminSearch_slave'];
    }

    public function GetAdminMenuItems()
    {
        $out = [];
        if( $this->VisibleToAdminUser() ) $out[] = CmsAdminMenuItem::from_module($this);
        return $out;
    }
} // class
