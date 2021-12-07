<?php
/*
OutMailer module un-installation process
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple module OutMailer.

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\SingleItem;
use OutMailer\PrefCrypter;

if (empty($this) || !($this instanceof OutMailer)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

$dict = $db->NewDataDictionary(); // old NewDataDictionary($db);

$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_outmailer_platforms');
$dict->ExecuteSQLArray($sqlarray);

$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_outmailer_props');
$dict->ExecuteSQLArray($sqlarray);

PrefCrypter::remove_preference($this, PrefCrypter::MKEY);
$this->RemovePreference();

$this->RemovePermission('Modify Mail Preferences');
$this->RemovePermission('Modify Email Gateways');
$this->RemovePermission('View Email Gateways');
//$this->RemovePermission('Modify Email Templates');

$this->RemoveEvent($this->GetName(), 'EmailDeliveryReported');

$ops = SingleItem::ModuleOperations();
$alias = $ops->get_module_classname('CMSMailer');
$mine = get_class($this);
if ($alias && strpos($alias, $mine) !== false) {
	$ops->set_module_classname('CMSMailer', null);
}
