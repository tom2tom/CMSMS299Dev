<?php
/*
CMSMailer module un-installation process
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This CMSMailer module is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This CMSMailer module is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published
by the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

This CMSMailer module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMailer\PrefCrypter;

//if (some worthy test fails) exit;

if ($this->platformed) {
    $dict = $db->NewDataDictionary(); // old NewDataDictionary($db);

    $sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_cmsmailer_platforms');
    $dict->ExecuteSQLArray($sqlarray);

    $sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_cmsmailer_props');
    $dict->ExecuteSQLArray($sqlarray);
}

PrefCrypter::remove_preference($this, PrefCrypter::MKEY);
$this->RemovePreference();

$this->RemovePermission('Modify Mail Preferences');
if ($this->platformed) {
    $this->RemovePermission('ModifyEmailateways');
    $this->RemovePermission('ViewEmailGateways');
//  $this->RemovePermission('ModifyEmailTemplates');
}
$this->RemoveEvent($this->GetName(), 'EmailDeliveryReported');
