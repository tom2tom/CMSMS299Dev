<?php
namespace cms_installer\nls;

use cms_installer\nls;

final class uk_UA_nls extends nls
{
    public function __construct()
    {
        $this->_fullname = 'Ukrainian';
        $this->_display = 'украї́нська мо́ва';
        $this->_isocode = 'uk';
        $this->_locale = 'uk_UA';
        $this->_encoding = 'UTF-8';
        $this->_aliases = 'uk_UA.utf8,uk_UA.utf-8,uk_UA.UTF-8,uk_UA,KOI8-U,ISO8859_5';
    }
}
