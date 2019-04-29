<?php
# DesignManager module uninstallation process.
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\Database\DataDictionary;
use CMSMS\Events;
use CMSMS\Group;
use DesignManager\Design;

if (!function_exists('cmsms')) exit;

$dict = new DataDictionary($db);
$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.Design::TABLENAME);
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.Design::TPLTABLE);
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.Design::CSSTABLE);
$dict->ExecuteSQLArray($sqlarray);

$group = new Group();
$group->name = 'Designer';
try {
    Events::SendEvent('Core', 'DeleteGroupPre', ['group'=>&$group]);
    if ($group->Delete()) {
        Events::SendEvent('Core', 'DeleteGroupPost', ['group'=>&$group]);
    }
} catch (Exception $e) {
    return $e->GetMessage();
}

//$this->RemovePreference();

//$this->RemovePermission('Add Templates');
$this->RemovePermission('Manage Designs');
//$this->RemovePermission('Manage Stylesheets');
//$this->RemovePermission('Modify Templates');

// unregister events
foreach([
 'AddDesignPost',
 'AddDesignPre',
/*
 'AddStylesheetPost',
 'AddStylesheetPre',
 'AddTemplatePost',
 'AddTemplatePre',
 'AddTemplateTypePost',
 'AddTemplateTypePre',
*/
 'DeleteDesignPost',
 'DeleteDesignPre',
/*
 'DeleteStylesheetPost',
 'DeleteStylesheetPre',
 'DeleteTemplatePost',
 'DeleteTemplatePre',
 'DeleteTemplateTypePost',
 'DeleteTemplateTypePre',
*/
 'EditDesignPost',
 'EditDesignPre',
/*
 'EditStylesheetPost',
 'EditStylesheetPre',
 'EditTemplatePost',
 'EditTemplatePre',
 'EditTemplateTypePost',
 'EditTemplateTypePre',

 'StylesheetPostCompile',
 'StylesheetPostRender',
 'StylesheetPreCompile',

 'TemplatePostCompile',
 'TemplatePreCompile',
 'TemplatePreFetch',
*/
] as $name) {
	// deprecated since 2.3 event originator is 'Core', change to 'DesignManager'
    Events::RemoveEvent('Core',$name);
}
