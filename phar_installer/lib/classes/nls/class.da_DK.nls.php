<?php
namespace cms_installer\nls;

use cms_installer\nls;

final class da_DK_nls extends nls
{
    #[\ReturnTypeWillChange]
    public function __construct()
    {
        $this->_fullname = 'Danish';
        $this->_display = 'Dansk';
        $this->_isocode = 'da';
        $this->_locale = 'da_DK';
        $this->_encoding = 'UTF-8';
        $this->_aliases = 'da_DK.utf8,da_DK.utf-8,da_DK.UTF-8,da_DK,danish,Danish_Denmark.1252,ISO8859_4';
    }
}
