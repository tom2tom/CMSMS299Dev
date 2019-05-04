<?php

use CMSMS\ScriptOperations;
use News\Adminops;
use News\Ops;

if(!isset($gCms) ) exit;
$papp = $this->CheckPermission('Approve News');
$pmod = $this->CheckPermission('Modify News');
$pdel = $pmod || $this->CheckPermission('Delete News');
if( !($papp || $pmod || $pdel) ) return;

if (isset($params['bulk_action']) ) {
    if( !isset($params['sel']) || !$params['sel'] ) {
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
            if( $pdel ) {
                foreach( $sel as $news_id ) {
                    Adminops::delete_article( $news_id );
                }
                $this->ShowMessage($this->Lang('msg_success'));
            }
            else {
                $this->ShowErrors($this->Lang('needpermission', 'Modify News'));
            }
            break;

        case 'setcategory':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET news_category_id = ?, modified_date = ?
WHERE news_id IN ('.implode(',',$sel).')';
            $parms = [(int)$params['category'], time()];
            $db->Execute($query,$parms);
            audit('',$this->GetName(),'category changed on '.count($sel).' articles');
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        case 'setpublished':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status = ?, modified_date = ?
WHERE news_id IN ('.implode(',',$sel).')';
            $db->Execute($query,['published', time()]);
            audit('',$this->GetName(),'status changed on '.count($sel).' articles');
            $this->ShowMessage($this->Lang('msg_success'));
            break;

        case 'setdraft':
            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status = ?, modified_date = ?
WHERE news_id IN ('.implode(',',$sel).')';
            $db->Execute($query,['draft', time()]);
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
}
else if( isset($params['resetfilter']) ) {
    $this->SetPreference('article_category','');
    $this->SetPreference('article_sortby','start_time DESC');
    $this->SetPreference('allcategories','no');
}

$curcategory = $this->GetPreference('article_category');
$allcategories = $this->GetPreference('allcategories','no');

$tpl = $smarty->createTemplate($this->GetTemplateResource('articlelist.tpl'),null,null,$smarty);
$tpl->assign('formstart',$this->CreateFormStart($id,'defaultadmin'))
 ->assign('prompt_category',$this->Lang('category'))
 ->assign('categorylist',array_flip($categorylist))
 ->assign('curcategory',$curcategory)
 ->assign('allcategories',$allcategories)
 ->assign('filterimage',cms_join_path(__DIR__,'images','filter'))
 ->assign('prompt_showchildcategories',$this->Lang('showchildcategories'))
 ->assign('prompt_sorting',$this->Lang('prompt_sorting'))
//see template ->assign('submitfilter',
//                $this->CreateInputSubmit($id,'submitfilter',$this->Lang('submit')))
 ->assign('prompt_pagelimit', $this->Lang('prompt_pagelimit'))

 ->assign('formend',$this->CreateFormEnd());

//Load the current articles
$query1 = 'SELECT n.news_id,n.news_title,n.start_time,n.end_time,n.status, nc.long_name
FROM '.CMS_DB_PREFIX.'module_news n
LEFT OUTER JOIN '.CMS_DB_PREFIX.'module_news_categories nc
ON n.news_category_id = nc.news_category_id';
$parms = [];
if( $curcategory ) {
    $query1 .= ' WHERE nc.long_name ';
    if( $allcategories == 'yes' ) {
        $query1 .= 'LIKE %?%';
    }
    else {
        $query1 .= '= ?';
    }
    $parms[] = $curcategory;
}
$query1 .= ' ORDER by n.news_title';

$dbresult = $db->Execute($query1,$parms);

if( $dbresult ) {
    $admintheme = cms_utils::get_theme_object();
    if( $papp ) {
        $iconcancel = $admintheme->DisplayImage('icons/system/true',$this->Lang('revert'),null,'','systemicon');
        $iconapprove = $admintheme->DisplayImage('icons/system/false',$this->Lang('approve'),null,'','systemicon');
    }
    else {
        $stati = [
		'draft' => $this->Lang('draft'),
		'final' => $this->Lang('final'),
		'published' => $this->Lang('published'),
		'archived' => $this->Lang('archived'),
        ];
    }
    if( $pmod ) {
        $iconedit = $admintheme->DisplayImage('icons/system/edit',$this->Lang('edit'),'','','systemicon');
        $iconcopy = $admintheme->DisplayImage('icons/system/copy',$this->Lang('copy'),'','','systemicon');
    }
    if( $pdel ) {
        $icondel = $admintheme->DisplayImage('icons/system/delete',$this->Lang('delete'),'','','systemicon');
    }
    $now = time();

    while( $dbresult && $row = $dbresult->FetchRow() ) {
        $onerow = new stdClass();

        $onerow->id = $row['news_id'];
        if( $pmod ) {
            $onerow->title = $this->CreateLink($id, 'editarticle', $returnid, $row['news_title'], ['articleid'=>$row['news_id']]);
        }
        else {
            $onerow->title = $row['news_title'];
        }
        $onerow->startdate = $row['start_time'] ? date('Y-n-j G:i', $row['start_time']) : '';
        $onerow->enddate = $row['end_time'] ? date('Y-n-j G:i', $row['end_time']) : '';
        $onerow->category = $row['long_name'];
        $onerow->expired = ( $row['end_time'] && $row['end_time'] < $now ) ? 1 : 0;
        if( $papp ) {
            if( $row['status'] == 'published' ) {
                $onerow->approve_link = $this->CreateLink(
                    $id,'approvearticle',$returnid,$iconcancel,['approve'=>0,'articleid'=>$row['news_id']]);
            }
            else {
                $onerow->approve_link = $this->CreateLink(
                    $id,'approvearticle',$returnid,$iconapprove,['approve'=>1,'articleid'=>$row['news_id']]);
            }
        }
        else {
            $onerow->approve_link = $stati[$row['status']];
        }

        if( $pmod ) {
            $onerow->edit_url = $this->create_url(
                $id,'editarticle',$returnid,['articleid'=>$row['news_id']]);
            $onerow->editlink = $this->CreateLink(
                $id,'editarticle',$returnid,$iconedit,['articleid'=>$row['news_id']]);
            $onerow->copylink = $this->CreateLink(
                $id,'copyarticle',$returnid,$iconcopy,['articleid'=>$row['news_id']]);
        }
        if( $pdel ) {
            $onerow->deletelink = $this->CreateLink(
                $id,'deletearticle',$returnid,$icondel,['articleid'=>$row['news_id']],'',false,false,'class="delete_article"');
        }

        $entryarray[] = $onerow;
    }

    $numrows = count($entryarray);
    $tpl->assign('items', $entryarray)
     ->assign('itemcount', $numrows);
}
else {
	 $numrows = 0;
     $tpl->assign('items',[])
      ->assign('itemcount',0);
}

if( $pmod ) {
    $tpl->assign('addlink', $this->CreateLink($id, 'addarticle', $returnid, $admintheme->DisplayImage('icons/system/newobject.gif', $this->Lang('addarticle'),'','','systemicon'), [], '', false, false, '') .' '. $this->CreateLink($id, 'addarticle', $returnid, $this->Lang('addarticle'), [], '', false, false, 'class="pageoptions"'));
}
if( $pdel ) {
    $tpl->assign('submit_massdelete',1);
}

$tpl->assign('can_add',$pmod)
 ->assign('form2start',$this->CreateFormStart($id,'defaultadmin',$returnid))
 ->assign('form2end',$this->CreateFormEnd());

$categorylist = Ops::get_category_list();
$tpl->assign('categoryinput',$this->CreateInputDropdown($id,'category',$categorylist));

$tpl->assign('reassigntext',$this->Lang('reassign_category'))
 ->assign('selecttext',$this->Lang('select'))
 ->assign('filtertext',$this->Lang('title_filter'))
 ->assign('statustext',$this->Lang('status'))
 ->assign('startdatetext',$this->Lang('startdate'))
 ->assign('enddatetext',$this->Lang('enddate'))
 ->assign('titletext',$this->Lang('title'))
 ->assign('categorytext',$this->Lang('category'));

$baseurl = $this->GetModuleURLPath();
//TODO ensure flexbox css for .rowbox.expand, .boxchild
$css = <<<EOS
 <link rel="stylesheet" href="{$baseurl}/css/sorts.css">

EOS;
$this->AdminHeaderContent($css);

$s1 = json_encode($this->Lang('areyousure'));
$s2 = json_encode($this->Lang('areyousure_multiple'));
$yes = $this->Lang('yes');

$pagerows = (int) $this->GetPreference('article_pagelimit',20);
$pagerows = 10; //DEBUG

if ($numrows > $pagerows) {
	//setup for SSsort paging
    $tpl->assign('totpg',ceil($numrows/$pagerows));

	$choices = [strval($pagerows) => $pagerows];
	$f = ($pagerows < 4) ? 5 : 2;
	$n = $pagerows * $f;
	if ($n < $numrows) {
		$choices[strval($n)] = $n;
	}
	$n *= 2;
	if ($n < $numrows) {
		$choices[strval($n)] = $n;
	}
	$choices[$this->Lang('all')] = 0;
	$tpl->assign('rowchanger',
		$this->CreateInputDropdown($id, 'pagerows', $choices, -1, $pagerows,
		'onchange="pagerows(this)"'));

	$jsp1 = <<<'EOS'
var pagedtable;

function pagefirst() {
 $.fn.SSsort.movePage(pagedtable,false,true);
}
function pagelast() {
 $.fn.SSsort.movePage(pagedtable,true,true);
}
function pagenext() {
 $.fn.SSsort.movePage(pagedtable,true,false);
}
function pageprev() {
 $.fn.SSsort.movePage(pagedtable,false,false);
}
function pagerows(dd) {
 $.fn.SSsort.setCurrent(pagedtable,'pagesize',parseInt(dd.value));
}
EOS;
	$jsp2 = <<<'EOS'
  pagedtable = document.getElementById('articlelist');
EOS;
	$jsp3 = ",
   paginate: true,
   pagesize: $pagerows,
   currentid: 'cpage',
   countid: 'tpage'";
} else { //no rows-paging
    $jsp1 = $jsp2 = $jsp3 = '';
}

$js = <<<EOS
$jsp1
$(function() {
  $('#bulk_category').hide();
  $('#selall').cmsms_checkall();
  $('#toggle_filter').on('click', function() {
    cms_dialog($('#filter'), {
      width: 'auto',
      modal: true
    });
  });
  $.fn.SSsort.addParser({
   id: 'icon',
   is: function(s,node) {
    var \$i = $(node).find('img');
    return \$i.length > 0;
   },
   format: function(s,node) {
    var \$i = $(node).find('img');
    return \$i[0].src;
   },
   watch: false,
   type: 'text'
  });
  $.fn.SSsort.addParser({
   id: 'publishat',
   is: function(s) {
    return true;
   },
   format: function(s,node) {
    var o = new Date(s),
      u = o ? o.valueOf() : 0;
    return u;
   },
   watch: false,
   type: 'numeric'
  });
  $('#articlelist').SSsort({
   sortClass: 'SortAble',
   ascClass: 'SortUp',
   descClass: 'SortDown',
   oddClass: 'row1',
   evenClass: 'row2',
   oddsortClass: 'row1s',
   evensortClass: 'row2s'{$jsp3}
  });
$jsp2
  $('a.delete_article').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this,$s1);
    return false;
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
    ev.preventDefault();
    var l = $('#articlelist :checked').length;
    if(l > 0) {
      var form = $(this).closest('form');
      cms_confirm($s2).done(function() {
        form.submit();
      });
    }
    return false;
  });
});

EOS;

$sm = new ScriptOperations();
$p = cms_join_path($this->GetModulePath(),'lib','js').DIRECTORY_SEPARATOR;
$sm->queue_file($p.'jquery.metadata.min.js',2);
$sm->queue_file($p.'jquery.SSsort.min.js',2);
$sm->queue_string($js,3);
$out = $sm->render_inclusion('', false, false);
if ($out) {
    $this->AdminBottomContent($out);
}

// display template
$tpl->display();
