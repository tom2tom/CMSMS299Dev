<?php
/*
Browse category action for CMSMS News module.
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
use News\Ops;

if (!isset($gCms)) exit;

$template = null;
if (isset($params['browsecattemplate'])) {
  $template = trim($params['browsecattemplate']);
}
else {
  $tpl = TemplateOperations::get_default_template_by_type('News::browsecat');
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
