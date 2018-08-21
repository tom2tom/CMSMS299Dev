<?php

use News\news_ops;

if (!isset($gCms)) exit;

$template = null;
if (isset($params['browsecattemplate'])) {
  $template = trim($params['browsecattemplate']);
}
else {
  $tpl = CmsLayoutTemplate::load_dflt_by_type('News::browsecat');
  if( !is_object($tpl) ) {
    audit('',$this->GetName(),'No default summary template found');
    return;
  }
  $template = $tpl->get_name();
}

$items = news_ops::get_categories($id,$params,$returnid);

// Display template
$tpl = $smarty->createTemplate($this->GetTemplateResource($template),null,null,$smarty);

$tpl->assign('count', count($items))
 ->assign('cats', $items);

$tpl->display();
