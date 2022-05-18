<?php
namespace cms_installer\nls;

use cms_installer\nls;

final class de_DE_nls extends nls
{
    #[\ReturnTypeWillChange]
    public function __construct()
    {
        $this->_fullname = 'German';
        $this->_display = 'Deutsch';
        $this->_isocode = 'de';
        $this->_locale = 'de_DE';
        $this->_encoding = 'UTF-8';
        $this->_aliases = 'german,de_DE.ISO8859-1';
    }
}
