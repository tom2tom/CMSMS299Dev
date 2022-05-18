<?php
namespace cms_installer\nls;

use cms_installer\nls;

class en_US_nls extends nls
{
    #[\ReturnTypeWillChange]
    public function __construct()
    {
        $this->_fullname = 'English';
        $this->_isocode = 'en';
        $this->_locale = 'en_US';
        $this->_encoding = 'UTF-8';
        $this->_aliases = 'english,eng,en_CA,en_GB,en_US.ISO8859-1';
    }

    public function foo()
    {
        return 1;
    }
}
