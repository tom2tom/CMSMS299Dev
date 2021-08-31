<?php
/*
CMSMS News module action: display a browsable category list.
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
use function CMSMS\specialize_array;

//if( some worthy test fails ) exit;

// TODO icon/image display

if( isset($params['browsecattemplate']) ) {
    $template = trim($params['browsecattemplate']);
}
else {
    $me = $this->GetName();
    $tpl = TemplateOperations::get_default_template_by_type($me.'::browsecat');
    if( !is_object($tpl) ) {
        cms_error('', $me.'..browsecat', 'No usable news-categories-template found');
        $this->ShowErrorPage('No usable news-categories-template found');
        return;
    }
    $template = $tpl->get_name();
}

$items = Utils::get_categories($id, $params, $returnid);
specialize_array($items);

// display template
$tpl = $smarty->createTemplate($this->GetTemplateResource($template)); //,null,null,$smarty);

$tpl->assign('count', count($items))
 ->assign('cats', $items);

$tpl->display();
