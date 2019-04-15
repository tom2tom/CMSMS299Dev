<?php

use News\Adminops;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify News Preferences') ) return;
$this->SetCurrentTab('categories');

function news_reordercats_create_flatlist($tree,$parent_id = -1)
{
  $data = [];
  $order = 1;
  foreach( $tree as &$node ) {
    if( is_array($node) && count($node) == 2 ) {
      $pid = substr($node[0],strlen('cat_'));
      $data[] = ['id'=>$pid,'parent_id'=>$parent_id,'order'=>$order];
      if( isset($node[1]) && $node[1] ) {
        $tmp = news_reordercats_create_flatlist($node[1],$pid);
        if( $tmp ) $data = array_merge($data,$tmp);
      }
    }
    else {
      $pid = substr($node,strlen('cat_'));
      $data[] = ['id'=>$pid,'parent_id'=>$parent_id,'order'=>$order];
    }
    $order++;
  }
  unset($node);
  return $data;
}

if( isset($params['cancel']) ) {
    $this->RedirectToAdminTab('','','admin_settings');
}
else if( isset($params['submit']) ) {
  $data = json_decode($params['data']);
  $flat = news_reordercats_create_flatlist($data);
  if( $flat ) {
    $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_categories SET parent_id = ?, item_order = ?
              WHERE news_category_id = ?';
    foreach( $flat as $rec ) {
      $dbr = $db->Execute($query,[$rec['parent_id'],$rec['order'],$rec['id']]);
    }
    Adminops::UpdateHierarchyPositions();
    $this->SetMessage($this->Lang('msg_categoriesreordered'));
    $this->RedirectToAdminTab('','','admin_settings');
  }
}

$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
$allcats = $db->GetArray($query);

$tpl = $smarty->createTemplate($this->GetTemplateResource('admin_reorder_cats.tpl'),null,null,$smarty);
$tpl->assign('allcats',$allcats);

$base_url = cms_path_to_url(CMS_ASSETS_PATH);
$js = <<<EOS
<script type="text/javascript" src="{$base_url}/js/jquery.mjs.nestedSortable.min.js"></script>
<script type="text/javascript">
//<![CDATA[
function parseTree(ul) {
  var tags = [];
  ul.children('li').each(function() {
    var subtree = $(this).children('ul');
    if(subtree.size() > 0) {
      tags.push([$(this).attr('id'), parseTree(subtree)]);
    } else {
      tags.push($(this).attr('id'));
    }
  });
  return tags;
}

$(function() {
  $('[name={$id}submit]').on('click', function() {
    var tree = JSON.stringify(parseTree($('ul.sortable'))); //IE8+
    $('#submit_data').val(tree);
  });

  $('ul.sortable').nestedSortable({
    disableNesting: 'no-nest',
    forcePlaceholderSize: true,
    handle: 'div',
    items: 'li',
    opacity: 0.6,
    placeholder: 'placeholder',
    tabSize: 25,
    tolerance: 'pointer',
    listType: 'ul',
    toleranceElement: '> div'
  });
});
//]]>
</script>
EOS;
$this->AdminBottomContent($js);

$tpl->display();

