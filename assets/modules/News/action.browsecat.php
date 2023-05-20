<?php
/*
CMSMS News module action: display a browsable categories-list.
Copyright (C) 2005-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
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
use function CMSMS\log_error;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize_array;

//if( some worthy test fails ) exit;

if( !empty($params['browsecattemplate']) ) {
    $tmp = trim($params['browsecattemplate']);
    $tplname = sanitizeVal($tmp, CMSSAN_FILE); // TODO more restriction(s) on template names?
}
else {
    $me = $this->GetName();
    $tpl = TemplateOperations::get_default_template_by_type($me.'::browsecat');
    if( is_object($tpl) ) {
        $tplname = $tpl->get_name();
    }
    else {
        log_error('No usable news-categories-template found', $me.'::browsecat');
        $this->ShowErrorPage('No usable news-categories-template found');
        return;
    }
}

// TODO icon/image display for each category, if any
$items = Utils::get_categories($id, $params, $returnid);
specialize_array($items);

// display template
$tpl = $smarty->createTemplate($this->GetTemplateResource($tplname)); //, '', '', $smarty);
$tpl->assign('cats', $items)
 ->assign('count', count($items))
 ->display();
