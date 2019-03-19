<?php

use News\Ops;

if (!isset($gCms)) exit;

$template = null;
if (isset($params['browsecattemplate'])) {
  $template = trim($params['browsecattemplate']);
}
else {
  $tpl = LayoutTemplateOperations::load_default_template_by_type('News::browsecat');
  if( !is_object($tpl) ) {
    audit('',$this->GetName(),'No default summary template found');
    return;
  }
  $template = $tpl->get_name();
}

$items = Ops::get_categories($id,$params,$returnid);

// Display template
$tpl = $smarty->createTemplate($this->GetTemplateResource($template),null,null,$smarty);

$tpl->assign('count', count($items))
 ->assign('cats', $items);

$tpl->display();
