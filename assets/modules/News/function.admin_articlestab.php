<?php

use News\news_admin_ops;
use News\news_ops;

if( !isset($gCms) ) exit;

$tpl = $smarty->createTemplate($this->GetTemplateResource('articlelist.tpl'),null,null,$smarty);
$tpl->assign('formstart',$this->CreateFormStart($id,'defaultadmin'));

if (isset($params['bulk_action']) ) {
    if( !isset($params['sel']) || !is_array($params['sel']) || count($params['sel']) == 0 ) {
        $this->ShowErrors($this->Lang('error_noarticlesselected'));
    }
    else {

        $sel = [];
        foreach( $params['sel'] as $one ) {
            $one = (int)$one;
            if( $one < 1 ) continue;
            if( in_array($one,$sel) ) continue;
            $sel[] = $one;
        }

        switch($params['bulk_action']) {
        case 'delete':
            if (!$this->CheckPermission('Delete News')) {
                $this->ShowErrors($this->Lang('needpermission', 'Modify News'));
            }
            else {
                foreach( $sel as $news_id ) {
                    news_admin_ops::delete_article( $news_id );
                }
                $this->ShowMessage($this->Lang('msg_success'));
            }
            break;

        case 'setcategory':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET news_category_id = ?, modified_date = NOW()
                WHERE news_id IN ('.implode(',',$sel).')';
            $parms = [(int)$params['category']];
            $db7->Execute($query,$parms);
            audit('',$this->GetName(),'category changed on '.count($sel).' articles');
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        case 'setpublished':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status = ?, modified_date = NOW()
                WHERE news_id IN ('.implode(',',$sel).')';
            $db->Execute($query,['published']);
            audit('',$this->GetName(),'status changed on '.count($sel).' articles');
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        case 'setdraft':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status = ?, modified_date = NOW()
                WHERE news_id IN ('.implode(',',$sel).')';
            $db->Execute($query,['draft']);
            audit('',$this->GetName(),'status changed on '.count($sel).' articles');
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        default:
            break;
        }
    }
}

$categorylist = [];
$categorylist[$this->Lang('allcategories')] = '';
$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
$dbresult = $db->Execute($query);
while ($dbresult && $row = $dbresult->FetchRow()) {
    $categorylist[$row['long_name']] = $row['long_name'];
}

$pagenumber = 1;
if( isset($_SESSION['news_pagenumber']) ) {
    $pagenumber = (int)$_SESSION['news_pagenumber'];
}
if( isset( $params['pagenumber'] ) ) {
    $pagenumber = (int)$params['pagenumber'];
    $_SESSION['news_pagenumber'] = $pagenumber;
}

if( isset($params['submitfilter']) ) {
    if( isset( $params['category']) ) {
        $this->SetPreference('article_category',trim($params['category']));
    }
    if( isset( $params['sortby'] ) ) {
        $this->SetPreference('article_sortby', str_replace("'",'_',$params['sortby']));
    }
    if( isset( $params['pagelimit'] ) ) {
        $this->SetPreference('article_pagelimit',(int)$params['pagelimit']);
    }
    $allcategories = $params['allcategories'] ?? 'no';
    $this->SetPreference('allcategories',$allcategories);
    unset($_SESSION['news_pagenumber']);
    $pagenumber = 1;
}
else if( isset($params['resetfilter']) ) {
    $this->SetPreference('article_category','');
    $this->SetPreference('article_pagelimit',50);
    $this->SetPreference('article_sortby','news_date DESC');
    $this->SetPreference('allcategories','no');
    unset($_SESSION['news_pagenumber']);
    $pagenumber = 1;
}

$curcategory = $this->GetPreference('article_category');
$pagelimit = (int) $this->GetPreference('article_pagelimit',50);
$allcategories = $this->GetPreference('allcategories','no');

$sortby = $this->GetPreference('article_sortby','news_date DESC');
$sortlist = [];
$sortlist[$this->Lang('post_date_desc')]='news_date DESC';
$sortlist[$this->Lang('post_date_asc')]='news_date ASC';
$sortlist[$this->Lang('expiry_date_desc')]='end_time DESC';
$sortlist[$this->Lang('expiry_date_asc')]='end_time ASC';
$sortlist[$this->Lang('title_asc')] = 'news_title ASC';
$sortlist[$this->Lang('title_desc')] = 'news_title DESC';
$sortlist[$this->Lang('status_asc')] = 'status ASC';
$sortlist[$this->Lang('status_desc')] = 'status DESC';

$tpl->assign('prompt_category',$this->Lang('category'))
 ->assign('categorylist',array_flip($categorylist))
 ->assign('curcategory',$curcategory)
 ->assign('allcategories',$allcategories)
 ->assign('filterimage',cms_join_path(__DIR__,'images','filter'))
 ->assign('sortlist',array_flip($sortlist))
 ->assign('pagelimits',[10=>10,25=>25,50=>50,250=>250,500=>500,1000=>1000])
 ->assign('pagelimit',$pagelimit)
 ->assign('sortby',$sortby)
 ->assign('prompt_showchildcategories',$this->Lang('showchildcategories'))
 ->assign('prompt_sorting',$this->Lang('prompt_sorting'))
//see template ->assign('submitfilter',
//                $this->CreateInputSubmit($id,'submitfilter',$this->Lang('submit')))
 ->assign('prompt_pagelimit', $this->Lang('prompt_pagelimit'))

 ->assign('formend',$this->CreateFormEnd());

//Load the current articles
$entryarray = [];

$dbresult = '';

$query1 = 'SELECT SQL_CALC_FOUND_ROWS n.*, nc.long_name FROM '.CMS_DB_PREFIX.'module_news n LEFT OUTER JOIN '.CMS_DB_PREFIX.'module_news_categories nc ON n.news_category_id = nc.news_category_id ';
$parms = [];
if ($curcategory != '') {
    $query1 .= ' WHERE nc.long_name LIKE ?';
    if( $allcategories == 'yes' ) {
        $parms[] = $curcategory.'%';
    }
    else {
        $parms[] = $curcategory;
    }
}
$query1 .= ' ORDER by '.$sortby;

$pagenumber = max(1,$pagenumber);
$startelement = ($pagenumber-1) * $pagelimit;
$dbresult = $db->SelectLimit( $query1, $pagelimit, $startelement, $parms);
$numrows = (int) $db->GetOne('SELECT FOUND_ROWS()');
$pagecount = (int)ceil($numrows/$pagelimit);

$tpl->assign('pagenumber',$pagenumber)
 ->assign('pagecount',$pagecount)
 ->assign('oftext',$this->Lang('prompt_of'));

$rowclass = 'row1';

$admintheme = cms_utils::get_theme_object();

while ($dbresult && $row = $dbresult->FetchRow()) {
    $onerow = new stdClass();

    $onerow->id = $row['news_id'];
    $onerow->news_title = $row['news_title'];
    $onerow->title = $this->CreateLink($id, 'editarticle', $returnid, $row['news_title'], ['articleid'=>$row['news_id']]);
    $onerow->data = $row['news_data'];
    $onerow->expired = 0;
    if( ($row['end_time'] != '') && ($db->UnixTimeStamp($row['end_time']) < time()) ) $onerow->expired = 1;
    $onerow->postdate = $row['news_date'];
    $onerow->startdate = $row['start_time'];
    $onerow->enddate = $row['end_time'];
    $onerow->u_postdate = $db->UnixTimeStamp($row['news_date']);
    $onerow->u_startdate = $db->UnixTimeStamp($row['start_time']);
    $onerow->u_enddate = $db->UnixTimeStamp($row['end_time']);
    $onerow->status = $this->Lang($row['status']);
    if( $this->CheckPermission('Approve News') ) {
        if( $row['status'] == 'published' ) {
            $onerow->approve_link = $this->CreateLink($id,'approvearticle',
                                                      $returnid,
                                                      $admintheme->DisplayImage('icons/system/true.gif',$this->Lang('revert'),null,'','systemicon'),['approve'=>0,'articleid'=>$row['news_id']]);
        }
        else {
            $onerow->approve_link = $this->CreateLink($id,'approvearticle',
                                                      $returnid,
                                                      $admintheme->DisplayImage('icons/system/false.gif',$this->Lang('approve'),null,'','systemicon'),['approve'=>1,'articleid'=>$row['news_id']]);
        }
    }
    $onerow->category = $row['long_name'];

    $onerow->rowclass = $rowclass;

    if( $this->CheckPermission('Modify News') ) {
        $onerow->edit_url = $this->create_url($id,'editarticle',$returnid,
                                              ['articleid'=>$row['news_id']]);
        $onerow->editlink = $this->CreateLink($id, 'editarticle', $returnid, $admintheme->DisplayImage('icons/system/edit.gif', $this->Lang('edit'),'','','systemicon'), ['articleid'=>$row['news_id']]);
    }
    if( $this->CheckPermission('Delete News') ) {
        $onerow->delete_url = $this->create_url($id,'deletearticle',$returnid, ['articleid'=>$row['news_id']]);
    }

    $entryarray[] = $onerow;
    ($rowclass=='row1'?$rowclass='row2':$rowclass='row1');
}

$tpl->assign('items', $entryarray)
 ->assign('itemcount', count($entryarray));

if( $this->CheckPermission('Modify News') ) {
    $tpl->assign('addlink', $this->CreateLink($id, 'addarticle', $returnid, $admintheme->DisplayImage('icons/system/newobject.gif', $this->Lang('addarticle'),'','','systemicon'), [], '', false, false, '') .' '. $this->CreateLink($id, 'addarticle', $returnid, $this->Lang('addarticle'), [], '', false, false, 'class="pageoptions"'));
}

$tpl->assign('can_add',$this->CheckPermission('Modify News'))
 ->assign('form2start',$this->CreateFormStart($id,'defaultadmin',$returnid))
 ->assign('form2end',$this->CreateFormEnd());
//see template ->assign('submit_reassign', $this->CreateInputSubmit($id,'submit_reassign',$this->Lang('submit')));
$categorylist = news_ops::get_category_list();
$tpl->assign('categoryinput',$this->CreateInputDropdown($id,'category',$categorylist));
if( $this->CheckPermission('Delete News') ) {
//see template    $tpl->assign('submit_massdelete',
//                    $this->CreateInputSubmit($id,'submit_massdelete',$this->Lang('delete_selected'),
//                                             '','',$this->Lang('areyousure_deletemultiple')));
}

$tpl->assign('reassigntext',$this->Lang('reassign_category'))
 ->assign('selecttext',$this->Lang('select'))
 ->assign('filtertext',$this->Lang('title_filter'))
 ->assign('statustext',$this->Lang('status'))
 ->assign('startdatetext',$this->Lang('startdate'))
 ->assign('enddatetext',$this->Lang('enddate'))
 ->assign('titletext',$this->Lang('title'))
 ->assign('postdatetext',$this->Lang('postdate'))
 ->assign('categorytext',$this->Lang('category'));

$config = $this->GetConfig();
$themedir = $config['admin_url'].'/themes/'.$admintheme->themeName.'/images/icons/system';
$tpl->assign('iconurl',$themedir);

$yes = $this->Lang('yes');
$s1 = json_encode($this->Lang('areyousure'));
$s2 = json_encode($this->Lang('areyousure_multiple'));

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
  $('#bulkactions').hide();
  $('#bulk_category').hide();
  $('#selall').cmsms_checkall();
  $('#toggle_filter').on('click', function() {
    cms_dialog($('#filter'), {
      width: 'auto',
      modal: true
    });
  });
  $('a.delete_article').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this,$s1,'$yes');
    return false;
  });
  $('#articlelist [type=checkbox]').on('cms_checkall_toggle', function() {
    var l = $('#articlelist :checked').length;
    if(l === 0) {
      $('#bulkactions').hide(50);
    } else {
      $('#bulkactions').show(50);
    }
  });
  $('#bulk_action').on('change', function() {
    var v = $(this).val();
    if(v === 'setcategory') {
      $('#bulk_category').show(50);
    } else {
      $('#bulk_category').hide(50);
    }
  });
  $('#bulkactions #submit_bulkaction').on('click', function(ev) {
    var form = $(this).closest('form');
    ev.preventDefault();
    cms_confirm($s2,'$yes').done(function() {
      form.submit();
    });
  });
});
//]]>
</script>
EOS;
$this->AdminBottomContent($js);
//TODO ensure flexbox css for .hbox.expand, .boxchild

// display template
$tpl->display();
