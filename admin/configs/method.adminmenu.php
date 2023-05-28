<?php
/*
Default admin-console-menu definition
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
BUT WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

/*
This is a depth-first 2-D array of hierarchical data for generating
admin-menu items. These data will be interpreted, then translated into
a tree using ArrayTree class methods.
Normally, other menu-items will be merged (by modules) at runtime.

Some of the values e.g. lang keys are 'internal', and anybody changing
stuff will need to delve ...

RULES
Members 'name', 'parent' must be present & valid. No non-word chars other than '-','.'. 'parent' = null for the tree-root node
'url' must be present & valid. It will be supplemented by security parameters
  unless the url is absolute (assumed external) then by 'urlparm' (if any)
'labelkey' is the relevant lang key for the item label
'description' | 'descriptionkey' is optional, the latter being the relevant lang key
'priority' os optional, defaults to 1
displayed items will be sorted by current-level-priority then current-level-item-labels
(using PHP strnatcmp(), probably not great when non-ASCII chars are in there)
'final' (i.e. include in menu in spite of no sub-items) false unless explicitly set true here
'show' boolean | argument for AdminTheme::HasPerm() optional, defaults to true (except for root node)
Other members (which might be needed by the menu-builder) e.g. link-attributes, will pass through verbatim
*/

$sp = 1; // section-priority enumerator
$menucontent = [
//======== TREE ROOT NODE ========
[
'name'=>'root',
'parent'=>null, // not ''
],
//============ SECTION ===========
[
'name'=>'main',
'parent'=>'root',
'url'=>'menu.php',
'labelkey'=>'home',
'description'=>'', //'descriptionkey'=>'viewdescription',
'priority'=>$sp++,
],
	[
	'name'=>'site',
	'parent'=>'main',
	'url'=>CMS_ROOT_URL.'/index.php',
	'type'=>'external',
	'target'=>'_blank',
	'labelkey'=>'viewsite',
	'description'=>'',
	'priority'=>1,
	'final'=>true,
	],
	[
	'name'=>'logout',
	'parent'=>'main',
	'url'=>'logout.php',
	'labelkey'=>'logout',
	'description'=>'',
	'priority'=>2,
	'final'=>true,
	],
//============ SECTION ===========
[
'name'=>'content',
'parent'=>'root',
'url'=>'menu.php',
'urlparm'=>'&section=content', // this item's name
'labelkey'=>'content',
'descriptionkey'=>'contentdescription',
'priority'=>$sp++,
'show'=>'contentPerms',
],
//============ SECTION ===========
[
'name'=>'layout',
'parent'=>'root',
'url'=>'menu.php',
'urlparm'=>'&section=layout',
'labelkey'=>'layout',
'descriptionkey'=>'layoutdescription',
'priority'=>$sp++,
'show'=>'layoutPerms',
],
	[
	'name'=>'listtemplates',
	'parent'=>'layout',
	'url'=>'listtemplates.php',
	'labelkey'=>'menulabel_templates',
	'descriptionkey'=>'menutitle_templates',
	'priority'=>1,
	'final'=>true,
	'show'=>'templatePerms',
	],
	[
	'name'=>'liststyles',
	'parent'=>'layout',
	'url'=>'liststyles.php',
	'labelkey'=>'menulabel_styles',
	'descriptionkey'=>'menutitle_styles',
	'priority'=>2,
	'final'=>true,
	'show'=>'stylesPerms',
	],
//============ SECTION ===========
[
'name'=>'services',
'parent'=>'root',
'url'=>'menu.php',
'urlparm'=>'&section=services', // this item's name
'labelkey'=>'services',
'descriptionkey'=>'servicesdescription',
'priority'=>$sp++,
],
	[
	'name'=>'ecommerce',
	'parent'=>'services',
	'url'=>'menu.php',
	'urlparm'=>'&section=ecommerce', // this item's name
	'labelkey'=>'ecommerce',
	'descriptionkey'=>'ecommerce_desc',
	'priority'=>1,
//	'show'=>'TODO relevant perm',
	],
//============ SECTION ===========
[
'name'=>'usersgroups',
'parent'=>'root',
'url'=>'menu.php',
'urlparm'=>'&section=usersgroups', // this item's name
'labelkey'=>'usersgroups',
'descriptionkey'=>'usersgroupsdescription',
'priority'=>$sp++,
'show'=>'usersGroupsPerms',
],
	[
	'name'=>'myprefs',
	'parent'=>'usersgroups',
	'url'=>'menu.php',
	'urlparm'=>'&section=myprefs', // this item's name
	'labelkey'=>'myprefs',
	'descriptionkey'=>'myprefsdescription',
	'priority'=>1,
	'final'=>true,
	'show'=>'myprefPerms',
	],
		[
		'name'=>'myaccount',
		'parent'=>'myprefs',
		'url'=>'useraccount.php',
		'labelkey'=>'myaccount',
		'descriptionkey'=>'myaccountdescription',
		'final'=>true,
		'show'=>'myaccount',
		],
		[
		'name'=>'mysettings',
		'parent'=>'myprefs',
		'url'=>'usersettings.php',
		'labelkey'=>'mysettings',
		'descriptionkey'=>'mysettingsdescription',
		'final'=>true,
		'show'=>'mysettings',
		],
		[
		'name'=>'mybookmarks',
		'parent'=>'myprefs',
		'url'=>'listbookmarks.php',
		'labelkey'=>'mybookmarks',
		'descriptionkey'=>'mybookmarksdescription',
		'final'=>true,
		'show'=>'mybookmarks',
		],
	[
	'name'=>'listusers',
	'parent'=>'usersgroups',
	'url'=>'listusers.php',
	'labelkey'=>'currentusers',
	'descriptionkey'=>'usersdescription',
	'priority'=>2,
	'final'=>true,
	'show'=>'userPerms',
	],
	[
	'name'=>'listgroups',
	'parent'=>'usersgroups',
	'url'=>'listgroups.php',
	'labelkey'=>'currentgroups',
	'descriptionkey'=>'groupsdescription',
	'priority'=>3,
	'final'=>true,
	'show'=>'groupPerms',
	],
	[
	'name'=>'groupmembers',
	'parent'=>'usersgroups',
	'url'=>'changegroupassign.php',
	'labelkey'=>'groupassignments',
	'descriptionkey'=>'groupassignmentdescription',
	'priority'=>4,
	'final'=>true,
	'show'=>'groupPerms',
	],
	[
	'name'=>'groupperms',
	'parent'=>'usersgroups',
	'url'=>'changegroupperm.php',
	'labelkey'=>'grouppermissions',
	'descriptionkey'=>'grouppermsdescription',
	'priority'=>5,
	'final'=>true,
	'show'=>'groupPerms',
	],
//============ SECTION ===========
[
'name'=>'extensions',
'parent'=>'root',
'url'=>'menu.php',
'urlparm'=>'&section=extensions', // this item's name
'labelkey'=>'extensions',
'descriptionkey'=>'extensionsdescription',
'priority'=>$sp++,
'show'=>'extensionsPerms',
],
	[
	'name'=>'tags',
	'parent'=>'extensions',
	'url'=>'listtags.php',
	'labelkey'=>'tags',
	'descriptionkey'=>'tagdescription',
	'final'=>true,
	'show'=>'taghelpPerms',
	],
	[
	'name'=>'usertags',
	'parent'=>'extensions',
	'url'=>'listusertags.php',
	'labelkey'=>'usertags',
	'descriptionkey'=>'usertags_description',
	'final'=>true,
	'show'=>'usertagPerms',
	],
	[
	'name'=>'eventhandlers',
	'parent'=>'extensions',
	'url'=>'listevents.php',
	'labelkey'=>'eventhandlers',
	'descriptionkey'=>'eventhandlerdescription',
	'final'=>true,
	'show'=>'eventPerms',
	],
//============ SECTION ===========
[
'name'=>'siteadmin',
'parent'=>'root',
'url'=>'menu.php',
'urlparm'=>'&section=siteadmin', // this item's name
'labelkey'=>'admin',
'descriptionkey'=>'admindescription',
'priority'=>$sp++,
'show'=>'siteAdminPerms',
],
	[
	'name'=>'siteprefs',
	'parent'=>'siteadmin',
	'url'=>'sitesettings.php',
	'labelkey'=>'globalconfig',
	'descriptionkey'=>'preferencesdescription',
	'priority'=>1,
	'final'=>true,
	'show'=>'sitePrefPerms',
	],
	[
	'name'=>'systeminfo',
	'parent'=>'siteadmin',
	'url'=>'systeminfo.php',
	'labelkey'=>'systeminfo',
	'descriptionkey'=>'systeminfodescription',
	'priority'=>2,
	'final'=>true,
	'show'=>'adminPerms',
	],
	[
	'name'=>'systemmaintenance',
	'parent'=>'siteadmin',
	'url'=>'systemmaintenance.php',
	'labelkey'=>'systemmaintenance',
	'descriptionkey'=>'systemmaintenancedescription',
	'priority'=>3,
	'final'=>true,
	'show'=>'adminPerms',
	],
	[
	'name'=>'checksum',
	'parent'=>'siteadmin',
	'url'=>'checksum.php',
	'labelkey'=>'system_verification',
	'descriptionkey'=>'checksumdescription',
	'priority'=>4,
	'final'=>true,
	'show'=>'adminPerms',
	],
	[
	'name'=>'files',
	'parent'=>'siteadmin',
	'url'=>'menu.php',
	'urlparm'=>'&section=files', // this item's name
	'labelkey'=>'files',
	'descriptionkey'=>'filesdescription',
	'priority'=>5,
	'show'=>'filePerms',
	],
	[
	'name'=>'accesscontrols',
	'parent'=>'siteadmin',
	'url'=>'listaxscontrols.php',
	'labelkey'=>'accesslabel',
	'descriptionkey'=>'accessdescription',
	'priority'=>6,
	'final'=>true,
	'show'=>'adminPerms',
	],
	[
	'name'=>'jobs',
	'parent'=>'siteadmin',
	'url'=>'listjobs.php',
	'labelkey'=>'jobslabel',
	'descriptionkey'=>'jobsdescription',
	'priority'=>6,
	'final'=>true,
	'show'=>'adminPerms', // TODO
	],
	[
	'name'=>'log',
	'parent'=>'siteadmin',
	'url'=>'adminlog.php',
	'labelkey'=>'loglabel',
	'descriptionkey'=>'logdescription',
	'priority'=>6,
	'final'=>true,
	'show'=>'adminPerms', // TODO
	],
];  //$menucontent
