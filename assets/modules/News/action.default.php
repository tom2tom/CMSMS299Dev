<?php
/*
Default action for CMSMS News module.
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

use CMSMS\TemplateOperations;
use News\Utils;

if( !isset($gCms) ) exit;

if( !empty ($params['browsecat']) ) {
    return $this->DoAction('browsecat', $id, $params, $returnid);
}

$me = $this->GetName();

if( isset($params['summarytemplate']) ) {
    $template = trim($params['summarytemplate']);
}
else {
    $tpl = TemplateOperations::get_default_template_by_type($me.'::summary');
    if( !is_object($tpl) ) {
        audit('',$me,'No default summary template found');
        return;
    }
    $template = $tpl->get_name();
}

$detailpage = '';
$tmp = $this->GetPreference('detail_returnid',-1);
if( $tmp > 0 ) $detailpage = $tmp;
if( isset($params['detailpage']) ) {
    $hm = $gCms->GetHierarchyManager();
    $id = $hm->find_by_identifier($params['detailpage'],FALSE);
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

$query1 = 'SELECT N.*,G.news_category_name,G.long_name,U.username,U.first_name,U.last_name
FROM ' . CMS_DB_PREFIX . $tbl. ' N
LEFT OUTER JOIN ' . CMS_DB_PREFIX . $grptbl . ' G ON N.news_category_id = G.news_category_id
LEFT OUTER JOIN ' . CMS_DB_PREFIX . 'users U ON U.user_id = N.author_id
WHERE status = \'published\' AND ';

if( !empty($params['idlist']) ) { //id = 0 N/A
	if( is_numeric($params['idlist'])) {
        $query1 .= ' (N.news_id = '.(int)$params['idlist'].') AND '; }
    }
    elseif( is_string($params['idlist']) ) {
        $tmp = explode(',',$params['idlist']);
        $idlist = [];
        for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
            $val = (int)$tmp[$i];
            if( $val > 0 && !in_array($val,$idlist) ) $idlist[] = $val;
        }
        if ($idlist) {
            $query1 .= ' (N.news_id IN ('.implode(',',$idlist).')) AND ';
        }
    }
}

if( isset($params['category_id']) ) {
    $query1 .= " ( G.news_category_id = '".(int)$params['category_id']."' ) AND ";
}
elseif( !empty($params['category']) ) {
    $category = cms_html_entity_decode(trim($params['category']));
    $categories = explode(',', $category);
    $query1 .= ' (';
    $count = 0;
    foreach( $categories as $onecat ) {
        if ($count > 0) $query1 .= ' OR ';
        if (strpos($onecat, '|') !== FALSE || strpos($onecat, '*') !== FALSE) {
            $tmp = $db->qStr(trim(str_replace(['*',"'"],['%','_'],$onecat)));
            $query1 .= "UPPER(G.long_name) LIKE UPPER({$tmp})"; // BAH! redundant if field is ci, useless if multibyte content present !
        }
        else {
            $tmp = $db->qStr(trim(str_replace("'",'_',$onecat)));
            $query1 .= "G.news_category_name = {$tmp}";
        }
        $count++;
    }
    $query1 .= ') AND ';
}

$now = time();
if( isset($params['showall']) ) {
    // show everything regardless of end time
    $query1 .= 'start_time IS NOT NULL AND start_time>0 ';
}
else // we're concerned about start time, end time, and created_date
if( isset($params['showarchive']) ) {
    // show only expired entries
    $query1 .= 'end_time IS NOT NULL AND end_time BETWEEN 1 AND '.$now.' ';
}
else {
    $query1 .= 'start_time IS NOT NULL AND start_time>0 AND (end_time IS NULL OR end_time=0 OR '.$now.' BETWEEN start_time AND end_time) ';
}

$sortrandom = false;
$sortby = trim(get_parameter_value($params,'sortby','start_time'));
switch( $sortby ) {
  case 'news_category':
    if (isset($params['sortasc']) && (strtolower($params['sortasc']) == 'true')) {
        $query1 .= 'ORDER BY G.long_name ASC, N.start_time ';
    }
    else {
        $query1 .= 'ORDER BY G.long_name DESC, N.start_time ';
    }
    break;

  case 'random':
    $query1 .= 'ORDER BY RAND() ';
    $sortrandom = true;
    break;

  case 'summary':
  case 'news_data':
  case 'news_category':
  case 'news_title':
  case 'end_time':
  case 'news_extra':
    $query1 .= "ORDER BY $sortby ";
    break;
  default:
    $query1 .= 'ORDER BY start_time ';
    break;
}

if( !$sortrandom ) {
    if( !isset($params['sortasc']) || !cms_to_bool($params['sortasc']) ) {
        $query1 .= 'DESC';
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
$pagelimit = max(1,min(1000,$pagelimit)); // maximum of 1000 items

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

$rst = $db->SelectLimit($query1,$pagelimit,$startelement);
$entryarray = [];
if( $rst ) {
    // build a list of news id's so we can preload stuff from other tables.
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
        $onerow->start = $row['start_time'];
        $onerow->startdate = strftime($fmt,$onerow->start);
        $onerow->postdate = $onerow->startdate; //deprecated since 3.0
        $onerow->stop = $row['end_time'];
        $onerow->enddate = strftime($fmt,$onerow->stop);
        $onerow->created = $row['create_date'];
        $onerow->create_date = strftime($fmt,$onerow->created);
        $onerow->modified = $row['modified_date'];
        if( !$onerow->modified ) $onerow->modified = $onerow->created;
        $onerow->modified_date = strftime($fmt,$onerow->modified);
        $onerow->category = $row['news_category_name'];

        $sendtodetail = ['articleid'=>$row['news_id']];
        if( isset($params['category_id']) ) { $sendtodetail['category_id'] = $params['category_id']; }
        if( isset($params['detailpage']) ) { $sendtodetail['origid'] = $returnid; }
        if( isset($params['detailtemplate']) ) { $sendtodetail['detailtemplate'] = $params['detailtemplate']; }
        if( isset($params['lang']) ) { $sendtodetail['lang'] = $params['lang']; }
        if( isset($params['pagelimit']) ) { $sendtodetail['pagelimit'] = $params['pagelimit']; }
        if( isset($params['showall']) ) { $sendtodetail['showall'] = $params['showall']; }

        $prettyurl = $row['news_url'];
        if( !$prettyurl ) {
            $aliased_title = munge_string_to_url($row['news_title']);
            $prettyurl = 'news/'.$row['news_id'].'/'.($detailpage!=''?$detailpage:$returnid)."/$aliased_title";
            if( isset($sendtodetail['detailtemplate']) ) {
                $prettyurl .= '/d,' . $sendtodetail['detailtemplate'];
            }
        }

        $moretext = $params['moretext'] ?? $this->Lang('moreprompt');
        $backto = ($detailpage) ? $detailpage : $returnid;
        $onerow->detail_url = $this->create_url($id,'detail',$backto,
			$sendtodetail);
        $onerow->moreurl = $this->CreateLink($id,'detail',$backto,
			$moretext,$sendtodetail,'',true,false,'',true,$prettyurl);
        $onerow->link = $this->CreateLink($id,'detail',$backto,
			'',$sendtodetail,'',true,false,'',true,$prettyurl);
        $onerow->titlelink = $this->CreateLink($id,'detail',$backto,
			$row['news_title'],$sendtodetail,'',false,false,'',true,$prettyurl);
        $onerow->morelink = $this->CreateLink($id,'detail',$backto,
			$moretext,$sendtodetail,'',false,false,'',true,$prettyurl);

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

$tpl = $smarty->createTemplate($this->GetTemplateResource($template),null,null,$smarty);

// pagination variables for the template
if( $pagenumber == 1 ) {
    $tpl->assign('prevpage',$this->Lang('prevpage'))
     ->assign('firstpage',$this->Lang('firstpage'));
}
else {
    $params['pagenumber'] = $pagenumber-1;
    $tpl->assign('prevpage',$this->CreateFrontendLink($id,$returnid,'default',$this->Lang('prevpage'),$params))
     ->assign('prevurl',$this->CreateFrontendLink($id,$returnid,'default','',$params, '', true));
    $params['pagenumber'] = 1;
    $tpl->assign('firstpage',$this->CreateFrontendLink($id,$returnid,'default',$this->Lang('firstpage'),$params))
     ->assign('firsturl',$this->CreateFrontendLink($id,$returnid,'default','',$params, '', true));
}

if( $pagenumber >= $pagecount ) {
    $tpl->assign('nextpage',$this->Lang('nextpage'))
     ->assign('lastpage',$this->Lang('lastpage'));
}
else {
    $params['pagenumber'] = $pagenumber+1;
    $tpl->assign('nextpage',$this->CreateFrontendLink($id,$returnid,'default',$this->Lang('nextpage'),$params))
     ->assign('nexturl',$this->CreateFrontendLink($id,$returnid,'default','',$params, '', true));
    $params['pagenumber'] = $pagecount;
    $tpl->assign('lastpage',$this->CreateFrontendLink($id,$returnid,'default',$this->Lang('lastpage'),$params))
     ->assign('lasturl',$this->CreateFrontendLink($id,$returnid,'default','',$params, '', true));
}
$tpl->assign('pagenumber',$pagenumber)
 ->assign('pagecount',$pagecount)
 ->assign('oftext',$this->Lang('prompt_of'))
 ->assign('pagetext',$this->Lang('prompt_page'))

 ->assign('items', $entryarray)
 ->assign('itemcount', $ecount)
 ->assign('category_label', $this->Lang('category_label'))
 ->assign('author_label', $this->Lang('author_label'));

foreach( $params as $key => $value ) {
    if( $key == 'mact' || $key == 'action' ) continue;
    $tpl->assign('param_'.$key,$value);
}
unset($params['pagenumber']);

$items = Utils::get_categories($id,$params,$returnid);
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
//    $catName = $db->GetOne('SELECT news_category_name FROM '.CMS_DB_PREFIX . 'module_news_categories where news_category_id=?',array($params['category_id']));
}
$tpl->assign('category_name',$catName)
 ->assign('cats',$items)
 ->assign('count',((items) ? count($items) : 0));

$tpl->display();
