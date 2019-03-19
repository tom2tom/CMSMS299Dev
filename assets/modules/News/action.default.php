<?php

use News\Ops;

if (!isset($gCms)) exit;

if (isset($params['summarytemplate'])) {
    $template = trim($params['summarytemplate']);
}
else {
    $tpl = LayoutTemplateOperations::load_default_template_by_type('News::summary');
    if( !is_object($tpl) ) {
        audit('',$this->GetName(),'No default summary template found');
        return;
    }
    $template = $tpl->get_name();
}

$detailpage = '';
$tmp = $this->GetPreference('detail_returnid',-1);
if( $tmp > 0 ) $detailpage = $tmp;
if( isset($params['detailpage']) ) {
    $hm = $gCms->GetHierarchyManager();
    $id = $hm->find_by_tag_anon($params['detailpage']);
    if( $id ) {
        $params['detailpage'] = $id;
    }
    else {
       // the page is not known
        unset($params['detailpage']);
    }
}
if (isset($params['browsecat']) && $params['browsecat']==1) {
    return $this->DoAction('browsecat', $id, $params, $returnid);
}

$query1 = 'SELECT
mn.*,
mnc.news_category_name,
mnc.long_name,
u.username,
u.first_name,
u.last_name
FROM ' . CMS_DB_PREFIX . 'module_news mn
LEFT OUTER JOIN ' . CMS_DB_PREFIX . 'module_news_categories mnc ON mnc.news_category_id = mn.news_category_id
LEFT OUTER JOIN ' . CMS_DB_PREFIX . 'users u ON u.user_id = mn.author_id
WHERE status = \'published\' AND ';

if( isset($params['idlist']) ) {
    $idlist = $params['idlist'];
    if( is_string($idlist) ) {
        $tmp = explode(',',$idlist);
        for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
            $tmp[$i] = (int)$tmp[$i];
            if( $tmp[$i] < 1 ) unset($tmp[$i]);
        }
        $idlist = array_unique($tmp);
        $query1 .= ' (mn.news_id IN ('.implode(',',$idlist).')) AND ';
    }
}

if( isset($params['category_id']) ) {
    $query1 .= " ( mnc.news_category_id = '".(int)$params['category_id']."' ) AND ";
}
else if( isset($params['category']) && $params['category'] != '' ) {
    $category = cms_html_entity_decode(trim($params['category']));
    $categories = explode(',', $category);
    $query1 .= ' (';
    $count = 0;
    foreach( $categories as $onecat ) {
        if ($count > 0) $query1 .= ' OR ';
        if (strpos($onecat, '|') !== FALSE || strpos($onecat, '*') !== FALSE) {
            $tmp = $db->qstr(trim(str_replace('*', '%', str_replace("'",'_',$onecat))));
            $query1 .= "upper(mnc.long_name) like upper({$tmp})";
        }
        else {
            $tmp = $db->qstr(trim(str_replace("'",'_',$onecat)));
            $query1 .= "mnc.news_category_name = {$tmp}";
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
        $query1 .= 'ORDER BY mnc.long_name ASC, mn.start_time ';
    }
    else {
        $query1 .= 'ORDER BY mnc.long_name DESC, mn.start_time ';
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
    $query1 .= "ORDER BY mn.$sortby ";
    break;
  default:
    $query1 .= 'ORDER BY mn.start_time ';
    break;
}

if( $sortrandom == false ) {
    if (isset($params['sortasc']) && (strtolower($params['sortasc']) == 'true')) {
        $query1 .= 'ASC';
    }
    else {
        $query1 .= 'DESC';
    }
}

$pagelimit = 1000;
if( isset( $params['pagelimit'] ) ) {
    $pagelimit = (int) ($params['pagelimit']);
}
else if( isset( $params['number'] ) ) {
    $pagelimit = (int) ($params['number']);
}
$pagelimit = max(1,min(1000,$pagelimit)); // maximum of 1000 entries.

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
    Ops::preloadFieldData($result_ids);

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
        $onerow->postdate = $row['start_time']; //deprecated since 2.90
        $onerow->startdate = $row['start_time'];
        $onerow->enddate = $row['end_time'];
        $onerow->create_date = $row['create_date'];
        $onerow->modified_date = $row['modified_date'];
        $onerow->category = $row['news_category_name'];

        //
        // Handle the custom fields
        //
        $onerow->fields = Ops::get_fields($row['news_id'],true);
        $onerow->fieldsbyname = $onerow->fields; // dumb, I know.
        $onerow->file_location = $gCms->config['uploads_url'].'/news/id'.$row['news_id'];

        $moretext = $params['moretext'] ?? $this->Lang('more');

        $sendtodetail = ['articleid'=>$row['news_id']];
        if( isset($params['showall']) ) { $sendtodetail['showall'] = $params['showall']; }
        if( isset($params['detailpage']) ) { $sendtodetail['origid'] = $returnid; }
        if( isset($params['detailtemplate']) ) { $sendtodetail['detailtemplate'] = $params['detailtemplate']; }
        if( isset($params['lang']) ) { $sendtodetail['lang'] = $params['lang']; }
        if( isset($params['category_id']) ) { $sendtodetail['category_id'] = $params['category_id']; }
        if( isset($params['pagelimit']) ) { $sendtodetail['pagelimit'] = $params['pagelimit']; }

        $prettyurl = $row['news_url'];
        if( $prettyurl == '' ) {
            $aliased_title = munge_string_to_url($row['news_title']);
            $prettyurl = 'news/'.$row['news_id'].'/'.($detailpage!=''?$detailpage:$returnid)."/$aliased_title";
            if( isset($sendtodetail['detailtemplate']) ) {
                $prettyurl .= '/d,' . $sendtodetail['detailtemplate'];
            }
        }
        $backto = ($detailpage) ? $detailpage : $returnid;
        $onerow->detail_url = $this->create_url(
            $id, 'detail', $backto, $sendtodetail );
        $onerow->link = $this->CreateLink(
            $id, 'detail', $backto, '', $sendtodetail, '', true, false, '', true, $prettyurl);
        $onerow->titlelink = $this->CreateLink(
            $id, 'detail', $backto, $row['news_title'], $sendtodetail, '', false, false, '', true, $prettyurl);
        $onerow->morelink = $this->CreateLink(
            $id, 'detail', $backto, $moretext, $sendtodetail, '', false, false, '', true, $prettyurl);
        $onerow->moreurl = $this->CreateLink(
            $id, 'detail', $backto, $moretext, $sendtodetail, '', true, false, '', true, $prettyurl);

        $entryarray[] = $onerow;
        $rst->MoveNext();
    } // while

    $ecount = count($entryarray);
    // determine a number of pages
    if( isset( $params['start'] ) ) $ecount -= (int)$params['start'];
    $pagecount = (int)($ecount / $pagelimit);
    if( ($ecount % $pagelimit) != 0 ) $pagecount++;
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

$items = Ops::get_categories($id,$params,$returnid);
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
 ->assign('count', count($items))
 ->assign('cats', $items);

$tpl->display();

