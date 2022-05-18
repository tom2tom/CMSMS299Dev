<?php
namespace cms_installer\nls;

use cms_installer\nls;

final class sk_SK_nls extends nls
{
    #[\ReturnTypeWillChange]
    public function __construct()
    {
        $this->_fullname = 'Slovak';
        $this->_display = 'Slovenčina';
        $this->_isocode = 'sk';
        $this->_locale = 'sk_SK';
        $this->_encoding = 'UTF-8';
        $this->_aliases = 'sk_SK.utf8,sk_SK.UTF-8,sk_SK.utf-8,sk_SK,slovak,Slovak_Slovakia.1250,Cp1250,ISO8859_2';
    }
}
