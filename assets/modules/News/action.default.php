<?php
/*
CMSMS News module default action.
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\TemplateOperations;
use News\Utils;
use function CMSMS\de_specialize;
use function CMSMS\log_error;
//use function CMSMS\specialize;

//if( some worthy test fails ) exit;

if( !empty ($params['browsecat']) ) {
    return $this->DoAction('browsecat', $id, $params, $returnid);
}

// TODO icon/image display

$me = $this->GetName();

if( !empty($params['summarytemplate']) ) {
    $template = trim($params['summarytemplate']);
}
else {
    $tpl = TemplateOperations::get_default_template_by_type($me.'::summary');
    if( !is_object($tpl) ) {
        log_error('No default summary template found', $me.'::default');
        $this->ShowErrorPage('No default summary template found');
        return;
    }
    $template = $tpl->get_name();
}

$detailpage = '';
$tmp = $this->GetPreference('detail_returnid', -1);
if( $tmp > 0 ) $detailpage = $tmp;
if( isset($params['detailpage']) ) {
    $hm = $gCms->GetHierarchyManager();
    $id = $hm->find_by_identifier($params['detailpage'], FALSE);
    if( $id ) {
        $params['detailpage'] = $id;
    }
    else {
       // the page is not known
        unset($params['detailpage']);
    }
}

$tbl = 'module_news';
$grptbl = 'module_news_categories';
$pref = CMS_DB_PREFIX;
$query1 = <<<EOS
SELECT N.*,G.news_category_name,G.long_name,U.username,U.first_name,U.last_name
FROM {$pref}{$tbl} N
LEFT OUTER JOIN {$pref}{$grptbl} G ON N.news_category_id = G.news_category_id
LEFT OUTER JOIN {$pref}users U ON U.user_id = N.author_id
WHERE status = 'published' AND

EOS;
$args = []; // for parameterizing non-int components of the query

if( !empty($params['idlist']) ) { //id = 0 N/A
    if( is_numeric($params['idlist'])) {
        $query1 .= ' (N.news_id = '.(int)$params['idlist'].') AND ';
    }
    elseif( is_string($params['idlist']) ) {
        $tmp = explode(',', $params['idlist']);
        $idlist = [];
        for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
            $val = (int)$tmp[$i];
            if( $val > 0 && !in_array($val, $idlist) ) $idlist[] = $val;
        }
        if ($idlist) {
            $query1 .= ' (N.news_id IN ('.implode(',', $idlist).')) AND ';
        }
    }
}

if( isset($params['category_id']) ) {
    $query1 .= ' (G.news_category_id = '.(int)$params['category_id'].') AND ';
}
elseif( !empty($params['category']) ) {
    $category = de_specialize(trim($params['category']));
    $categories = explode(',', $category);
    $query1 .= ' (';
    $count = 0;
    foreach( $categories as $onecat ) {
        if ($count > 0) $query1 .= ' OR ';
        if (strpos($onecat, '|') !== FALSE || strpos($onecat, '*') !== FALSE) {
            $query1 .= 'G.long_name LIKE ?'; // table field is _ci so this is caseless
            $tmp = $db->qStr(trim(str_replace(['*', '?', "'"], ['%', '_', '_'], $onecat))); // '_' is wildcard here
            $args[] = '%'.$tmp.'%';
        }
        else {
            $query1 .= 'G.news_category_name = ?'; // also a case-insensitive match
            $args[] = $db->qStr(trim(str_replace("'", '_', $onecat))); // '_' is NOT wildcard here ?
        }
        $count++;
    }
    $query1 .= ') AND ';
}

$longnow = $db->DbTimeStamp(time());
if( isset($params['showall']) ) {
    // show everything regardless of end time
    $query1 .= 'start_time IS NOT NULL ';
}
else // we're concerned about start_time, end_time and create_date
  if( isset($params['showarchive']) ) {
    // show only expired entries
    $query1 .= 'end_time IS NOT NULL AND end_time <= '.$longnow;
}
else {
    $query1 .= 'start_time IS NOT NULL AND (end_time IS NULL OR '.$longnow.' BETWEEN start_time AND end_time) ';
}

$sortrandom = false;
$val = $params['sortby'] ?? '';
$sortby = ($val) ? trim($val) : 'start_time';

switch( $sortby ) {
    case 'news_category':
        if (isset($params['sortasc']) && (strtolower($params['sortasc']) == 'true')) {
            $query1 .= 'ORDER BY G.long_name, N.start_time';
        }
        else {
            $query1 .= 'ORDER BY G.long_name DESC, N.start_time';
        }
    break;

    case 'random':
        $query1 .= 'ORDER BY RAND()';
        $sortrandom = true;
        break;

    case 'summary':
    case 'news_data':
    case 'news_category':
    case 'news_title':
    case 'end_time':
    case 'news_extra':
        $query1 .= 'ORDER BY '.$sortby;
        break;
    default:
        $query1 .= 'ORDER BY start_time';
        break;
}

if( !$sortrandom ) {
    if( !isset($params['sortasc']) || !cms_to_bool($params['sortasc']) ) {
        $query1 .= ' DESC';
    }
}

if( isset( $params['pagelimit'] ) ) {
    $pagelimit = (int)$params['pagelimit'];
}
elseif( isset( $params['number'] ) ) {
    $pagelimit = (int)$params['number'];
}
else {
    $pagelimit = 1000;
}
$pagelimit = max(1, min(1000, $pagelimit)); // maximum of 1000 items

// Get the number of rows (so we can determine the numer of pages)
$pagecount = -1;
$startelement = 0;
$pagenumber = 1;

if( !empty($params['pagenumber']) ) {
    // if given a page number, determine a start element
    $pagenumber = (int)$params['pagenumber'];
    $startelement = ($pagenumber-1) * $pagelimit;
}
if( isset( $params['start'] ) ) {
    // given a start element, determine a page number
    $startelement = $startelement + (int)$params['start'];
}

$rst = $db->SelectLimit($query1, $pagelimit, $startelement, $args);
$entryarray = [];
if( $rst ) {
    $dopretty = $config['url_rewriting'] != 'none';

    // build a list of news id's so we can preload stuff from other tables
    $result_ids = [];
    while( !$rst->EOF() ) {
        $result_ids[] = $rst->fields['news_id'];
        $rst->MoveNext();
    }

    $fmt = $this->GetDateFormat();
    $rst->MoveFirst();
    while( !$rst->EOF() ) {
        $row = $rst->fields;
        $onerow = new stdClass();

        $onerow->author_id = $row['author_id'];
        if( $onerow->author_id > 0 ) {
            $onerow->author = $row['username'];
            $onerow->authorname = trim($row['first_name'].' '.$row['last_name']);
        }
        else {
            $onerow->author = $this->Lang('anonymous');
            $onerow->authorname = $this->Lang('unknown');
        }

        $onerow->id = $row['news_id'];
        $onerow->title = $row['news_title'];
        $onerow->content = $row['news_data'];
        $onerow->summary = (trim($row['summary'])!='<br />'?$row['summary']:'');
        if( !empty($row['news_extra']) ) $onerow->extra = $row['news_extra'];
        $onerow->startdate = $this->FormatforDisplay($row['start_time']);
        $onerow->postdate = $onerow->startdate; //deprecated since 3.0
        $onerow->enddate = $this->FormatforDisplay($row['end_time']);
        $onerow->created =  $this->FormatforDisplay($row['create_date']);
        $onerow->modified = $this->FormatforDisplay($row['modified_date']);
        if( !$onerow->modified ) { $onerow->modified = $onerow->created; }
        $onerow->category = $row['news_category_name'];

        $urlparms = ['articleid'=>$row['news_id']];
        if( isset($params['category_id']) ) { $urlparms['category_id'] = $params['category_id']; }
        if( isset($params['detailpage']) ) { $urlparms['origid'] = $returnid; }
        if( !empty($params['detailtemplate']) ) {
            $urlparms['detailtemplate'] = trim($params['detailtemplate']);
        }
        if( isset($params['lang']) ) { $urlparms['lang'] = $params['lang']; }
        if( isset($params['pagelimit']) ) { $urlparms['pagelimit'] = $params['pagelimit']; }
        if( isset($params['showall']) ) { $urlparms['showall'] = $params['showall']; }

        $moretext = $params['moretext'] ?? $this->Lang('moreprompt');
        $backto = ($detailpage) ? $detailpage : $returnid;
        $onerow->detail_url = $this->create_url($id, 'detail', $backto, $urlparms, false, false, '', false, 2);
        if( $dopretty ) {
            $prettyurl = $row['news_url'];
            if( !$prettyurl ) {
                $aliased_title = munge_string_to_url($row['news_title']);
                $prettyurl = 'News/'.$row['news_id'].'/'.($detailpage!=''?$detailpage:$returnid)."/$aliased_title";
                if( !empty($urlparms['detailtemplate']) ) {
                    $prettyurl .= '/d, ' . $urlparms['detailtemplate'];
                }
            }
            $onerow->moreurl = $this->CreateLink($id, 'detail', $backto,
                $moretext, $urlparms, '', true, false, '', true, $prettyurl);
            $onerow->link = $this->CreateLink($id, 'detail', $backto,
                '',        $urlparms, '', true, false, '', true, $prettyurl);
            $onerow->titlelink = $this->CreateLink($id, 'detail', $backto,
                $row['news_title'], $urlparms, '', false, false, '', true, $prettyurl);
            $onerow->morelink = $this->CreateLink($id, 'detail', $backto,
                $moretext, $urlparms, '', false, false, '', true, $prettyurl);
        }
        else {
            $urlparms = [
                'articleid' => $row['news_id'],
                'returnid' => ($detailpage) ? $detailpage : $returnid,
            ];
            if( !empty($urlparms['detailtemplate']) ) {
                $urlparms['detailtemplate'] = $urlparms['detailtemplate'];
            }
            $onerow->moreurl = $this->CreateLink($id, 'detail', $backto,
                $moretext, $urlparms, '', true);
            $onerow->link = $this->CreateLink($id, 'detail', $backto,
                '',        $urlparms, '', true);
            $onerow->titlelink = $this->CreateLink($id, 'detail', $backto,
                $row['news_title'], $urlparms, '', false);
            $onerow->morelink = $this->CreateLink($id, 'detail', $backto,
                $moretext, $urlparms, '', false);
        }
        $entryarray[] = $onerow;
        $rst->MoveNext();
    } // while

    // determine number of pages
    $ecount = count($entryarray);
    if( isset( $params['start'] ) ) { $ecount -= (int)$params['start']; }
    $pagecount = (int)($ecount / $pagelimit);
    if( ($ecount % $pagelimit) != 0 ) { $pagecount++; }
} // resultset
else {
    $ecount = 0;
    $pagecount = 0;
}

$tpl = $smarty->createTemplate($this->GetTemplateResource($template)); //, null, null, $smarty);

// TODO specialize() relevant ->assign()'d values
// pagination variables for the template
if( $pagenumber == 1 ) {
    $tpl->assign('prevpage', $this->Lang('prevpage'))
      ->assign('firstpage', $this->Lang('firstpage'));
}
else {
    $params['pagenumber'] = $pagenumber-1;
    $tpl->assign('prevpage', $this->CreateFrontendLink($id, $returnid, 'default', $this->Lang('prevpage'), $params))
      ->assign('prevurl', $this->CreateFrontendLink($id, $returnid, 'default', '', $params, '', true));
    $params['pagenumber'] = 1;
    $tpl->assign('firstpage', $this->CreateFrontendLink($id, $returnid, 'default', $this->Lang('firstpage'), $params))
     ->assign('firsturl', $this->CreateFrontendLink($id, $returnid, 'default', '', $params, '', true));
}

if( $pagenumber >= $pagecount ) {
    $tpl->assign('nextpage', $this->Lang('nextpage'))
      ->assign('lastpage', $this->Lang('lastpage'));
}
else {
    $params['pagenumber'] = $pagenumber+1;
    $tpl->assign('nextpage', $this->CreateFrontendLink($id, $returnid, 'default', $this->Lang('nextpage'), $params))
      ->assign('nexturl', $this->CreateFrontendLink($id, $returnid, 'default', '', $params, '', true));
    $params['pagenumber'] = $pagecount;
    $tpl->assign('lastpage', $this->CreateFrontendLink($id, $returnid, 'default', $this->Lang('lastpage'), $params))
      ->assign('lasturl', $this->CreateFrontendLink($id, $returnid, 'default', '', $params, '', true));
}
$tpl->assign('pagenumber', $pagenumber)
 ->assign('pagecount', $pagecount)
 ->assign('oftext', $this->Lang('prompt_of'))
 ->assign('pagetext', $this->Lang('prompt_page'))

 ->assign('items', $entryarray)
 ->assign('itemcount', $ecount)
 ->assign('category_label', $this->Lang('category_label'))
 ->assign('author_label', $this->Lang('author_label'));

foreach( $params as $key => $value ) {
    if( $key == 'mact' || $key == 'action' ) continue;
    $tpl->assign('param_'.$key, $value);
}
unset($params['pagenumber']);

$items = Utils::get_categories($id, $params, $returnid);
$c = ($items) ? count($items) : 0;
$catName = '';
if( isset($params['category']) ) {
    $catName = $params['category'];
}
elseif( isset($params['category_id']) && $items ) {
    foreach( $items as $item ) {
        if( $item['news_category_id'] == $params['category_id'] ) {
            $catName = $item['news_category_name'];
            break;
        }
    }
//  $catName = $db->getOne('SELECT news_category_name FROM '.CMS_DB_PREFIX . 'module_news_categories where news_category_id=?', array($params['category_id']));
}

$tpl->assign('category_name', $catName)
 ->assign('cats', $items)
 ->assign('count', $c);

$tpl->display();
