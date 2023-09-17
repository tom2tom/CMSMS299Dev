<?php
/*
News module un-installation process
Copyright (C) 2005-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\Database\DataDictionary;
use CMSMS\RouteOperations;
use CMSMS\TemplateType;
use function CMSMS\log_error;

if( empty($this) || !($this instanceof News) ) exit;
//$installing = AppState::test(AppState::INSTALL);
//if( !($installing || $this->CheckPermission('Modify Modules')) ) exit;

$dict = new DataDictionary($db);

$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_news');
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_news_categories');
$dict->ExecuteSQLArray($sqlarray);

$db->DropSequence(CMS_DB_PREFIX.'module_news_seq');
$db->DropSequence(CMS_DB_PREFIX.'module_news_categories_seq');

// Remove all permissions for this module
$this->RemovePermission('Modify News');
$this->RemovePermission('Approve News');
$this->RemovePermission('Delete News');
$this->RemovePermission('Modify News Preferences');

// And all preferences
$this->RemovePreference();

// And events
$this->RemoveEvent('NewsArticleAdded');
$this->RemoveEvent('NewsArticleEdited');
$this->RemoveEvent('NewsArticleDeleted');
$this->RemoveEvent('NewsCategoryAdded');
$this->RemoveEvent('NewsCategoryEdited');
$this->RemoveEvent('NewsCategoryDeleted');
$this->RemoveEventHandler('Core','DeleteUserPre');

$me = $this->GetName();

// And uploads
$fp = $config['uploads_path'];
if( $fp && is_dir($fp) ) {
    $fp2 = $fp.DIRECTORY_SEPARATOR.$me;
    if( is_dir($fp2) ) {
        recursive_delete($fp2);
    }
    else {
        $fp2 = $fp.DIRECTORY_SEPARATOR.'news';
        if( is_dir($fp2) ) {
            recursive_delete($fp2);
        }
    }
}

$this->RemoveSmartyPlugin();

RouteOperations::del_static('',$me);

// And templates and template types
$this->DeleteTemplate();
//$this->DeleteTemplate('displaysummary');
//$this->DeleteTemplate('displaydetail');

try {
    $types = TemplateType::load_all_by_originator($me);
    if( $types ) {
        foreach( $types as $type ) {
            $templates = $type->get_template_list();
            if( $templates ) {
                foreach( $templates as $template ) {
                    $template->delete();
                }
            }
            $type->delete();
        }
    }
}
catch (Throwable $t) {
    log_error($me,'Uninstall error: '.$t->getMessage());
}
