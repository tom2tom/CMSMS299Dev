<?php
/*
CMSMailer module upgrade process
Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AppParams;
use CMSMS\Crypto;

if (!function_exists('cmsms')) exit;

if (version_compare($oldversion,'6.3.0') < 0) {
    // migrate 'core' serialized-settings to this module
    $val = AppParams::get('mailprefs');
    if ($val) {
        $mailprefs = unserialize($val, ['allowed_classes' => false]);
        foreach ($mailprefs as $key => $val) {
            $this->SetPreference($key, $val);
        }
        if (isset($mailprefs['password'])) {
            $this->SetPreference('password', base64_encode(Crypto::encrypt_string($mailprefs['password'])));
        }
    }
}
