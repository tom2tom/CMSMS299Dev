<?php
/*
ContentManager module upgrade process.
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

//use CMSMS\Database\DataDictionary;
use CMSMS\SingleItem;
use CMSMS\Events;

if( empty($this) || !($this instanceof ContentManager)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

//$dict = new DataDictionary($db);

if( version_compare($oldversion,'2.0') < 0 ) {
    $me = $this->GetName();
    // register events for which other parts of the system may listen
    foreach([
     'ContentDeletePost',
     'ContentDeletePre',
     'ContentEditPost',
     'ContentEditPre',
     'ContentPostCompile',
     'ContentPostRender',
     'ContentPreCompile',
     'ContentPreRender', // 2.2
    ] as $name) {
        Events::CreateEvent($me,$name); //since 2.0
    }

    // semi-permanent alias for back-compatibility
    $ops = SingleItem::ModuleOperations();
    $ops->set_module_classname('CMSContentManager',get_class($this));
}

SingleItem::ContentTypeOperations()->RebuildStaticContentTypes();
