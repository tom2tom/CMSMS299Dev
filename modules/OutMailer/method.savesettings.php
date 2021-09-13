<?php
/*
OutMailer module savesettings method
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is part of CMS Made Simple module: OutMailer.
Refer to licence and other details at the top of file OutMailer.module.php
More info at http://dev.cmsmadesimple.org/projects/outmailer
*/

use OutMailer\PrefCrypter;
use function CMSMS\de_specialize;

if (isset($params['masterpass'])) {
    $newpw = de_specialize($params['masterpass']); // AND CMSMS\sanitizeVal() ?
    $pw = PrefCrypter::decrypt_preference($this, PrefCrypter::MKEY);
    if ($newpw != $pw) {
        if (!$TODOvalidnewpw) {
            //TODO redirect with error msg
            $this->Redirect($id, 'defaultadmin', '', ['activetab' => 'settings']);
        }
        //update all current crypted data
        $val = $this->GetPreference('password');
        if ($val !== '') {
            $val = Crypto::decrypt_string(base64_decode($val), $pw);
        }
        $val = base64_encode(Crypto::encrypt_string($val, $newpw));
        $this->SetPreference('password', $val);

        if ($this->platformed) {
        $sql = 'SELECT id,value,encvalue FROM '.CMS_DB_PREFIX.'module_outmailer_props WHERE encrypt>0';
        $rows = $db->getArray($sql);
        if ($rows) {
            if ($newpw) {
                $tofield = 'encvalue';
                $notfield = 'value';
                $encval = 1;
            } else {
                $tofield = 'value';
                $notfield = 'encvalue';
                $encval = 0;
            }
            $sql = 'UPDATE '.CMS_DB_PREFIX.'module_outmailer_props SET '.$tofield.'=?,'.$notfield.'=NULL,encrypt=? WHERE id=?';
            foreach ($rows as &$onerow) {
                if ($oldpw) {
                    $raw = ($onerow['encvalue']) ?
                        Crypto::decrypt_string($onerow['encvalue'], $oldpw) :
                        null;
                } else {
                    $raw = $onerow['value'];
                }
                if ($newpw) {
                    $revised = ($raw) ?
                        Crypto::encrypt_string($raw, $newpw) :
                        null;
                } else {
                    $revised = $raw;
                }
                if (!$revised) {
                    $revised = null;
                }
                $db->execute($sql, [$revised, $encval, $onerow['id']]);
            }
            unset($onerow);
        }
        } // platformed
        PrefCrypter::encrypt_preference(PrefCrypter::MKEY, $newpw);
    }
    unset($newpw, $pw); // faster garbage cleanup
    $newpw = $pw = null;
}
