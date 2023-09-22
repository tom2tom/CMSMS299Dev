<?php
/*
ContentManager module action: settings
Copyright (C) 2013-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use ContentManager\Utils;
use CMSMS\Lone;
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;

if (!$this->CheckContext()) {
	exit;
}

if (!$this->CheckPermission('Modify Site Preferences')) {
	exit;
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('settings.tpl')); //,null,null,$smarty);

// general tab

$opts = [
	'all' => $this->Lang('opt_alltemplates'),
	'allpage' => $this->Lang('opt_allpage')
];

$tpl->assign('locktimeout', $this->GetPreference('locktimeout'))
	->assign('lockrefresh', $this->GetPreference('lockrefresh'))
	->assign('template_list_opts', $opts)
	->assign('template_list_mode', $this->GetPreference('template_list_mode', 'allpage'));

// listsettings tab

$opts = [
 'title' => $this->Lang('prompt_page_title'),
 'menutext' => $this->Lang('prompt_page_menutext')
];
$tpl->assign('namecolumnopts', $opts)
	->assign('list_namecolumn', $this->GetPreference('list_namecolumn', 'title'));
$choosecols = 'hier,page,alias,url,template,type,owner,active,default,created,modified,move,view,copy,addchild,edit,delete';
$tmp = explode(',', $choosecols);
$opts = [];
foreach ($tmp as $one) {
	$opts[$one] = $this->Lang('colhdr_'.$one);
}
$tpl->assign('visible_column_opts', $opts);
$dflts = 'hier,page,alias,template,type,active,default';
$tmp = explode(',', $this->GetPreference('list_visiblecolumns', $dflts));
$tpl->assign('list_visiblecolumns', $tmp);

// pagedefaults tab

$prefs = Utils::get_pagedefaults();
$templates = TemplateOperations::template_query(['originator' => TemplateType::CORE, 'as_list' => 1]);
if ($templates) {
	natcasesort($templates); //TODO sort by name
}
$eds = Lone::get('ContentOperations')->ListAdditionalEditors();
if ($eds) {
	natcasesort($eds); //TODO sort by UTF-8 name etc
}
$realm = $this->GetName();
$types = Lone::get('ContentTypeOperations')->ListContentTypes(false, false, false, $realm);

if ($types) {
	//exclude types which are nonsense for default (maybe make this a preference?)
	foreach ([
	 'errorpage',
	 'link',
	 'pagelink',
	 'sectionheader',
	 'separator',
	] as $one) {
		unset($types[$one]);
	}
	natcasesort($types);
}

[$stylerows, $grouped, $js] = Utils::get_sheets_data($prefs['styles'] ?? []);
if ($js) {
	add_page_foottext($js);
}

$tpl->assign('page_prefs', $prefs)
	->assign('contenttypes_list', $types)
	->assign('sheets', $stylerows)
	->assign('grouped', $grouped)
	->assign('template_list', $templates)
	->assign('addteditor_list', $eds);

$tpl->display();