<?php
namespace cms_installer\nls;

use cms_installer\nls;

final class ca_ES_nls extends nls
{
    #[\ReturnTypeWillChange]
    public function __construct()
    {
        $this->_fullname = 'Catalan';
        $this->_display = 'Catal&agrave;';
        $this->_isocode = 'ca';
        $this->_locale = 'ca_ES';
        $this->_encoding = 'UTF-8';
        $this->_aliases = 'ca_ES.utf8,ca_ES.utf8@valencia,ca_ES.utf-8,ca_ES.UTF-8,ca_ES,ca_ES@euro,ca_ES@valencia,catalan,Catalan_Spain.1252,ISO8859_15';
    }
}
