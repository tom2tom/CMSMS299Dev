<?php
/*
Reorder categories action for CMSMS News module.
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

use News\AdminOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify News Preferences') ) exit;

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
    $this->RedirectToAdminTab('groups');
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
    AdminOperations::UpdateHierarchyPositions();
    $this->SetMessage($this->Lang('msg_categoriesreordered'));
    $this->RedirectToAdminTab('groups');
  }
}

$query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
$allcats = $db->GetArray($query);

$tpl = $smarty->createTemplate($this->GetTemplateResource('reorder_cats.tpl'),null,null,$smarty);
$tpl->assign('allcats',$allcats);

$out = cms_get_script('jquery.mjs.nestedSortable.js');
$js = <<<EOS
<script type="text/javascript" src="$out"></script>
<script type="text/javascript">
//<![CDATA[
function parseTree(ul) {
  var tags = [];
  ul.children('li').each(function() {
    var subtree = $(this).children('ul');
    if(subtree.size() > 0) {
      tags.push([this.id, parseTree(subtree)]);
    } else {
      tags.push(this.id);
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
add_page_foottext($js);

$tpl->display();
return '';
