<?php
namespace cms_installer\nls;

use cms_installer\nls;

final class nl_NL_nls extends nls
{
    #[\ReturnTypeWillChange]
    public function __construct()
    {
        $this->_fullname = 'Dutch';
        $this->_display = 'Nederlands';
        $this->_isocode = 'nl';
        $this->_locale = 'nl_NL';
        $this->_encoding = 'UTF-8';
        $this->_aliases = 'dutch,nl_NL.ISO8859-1';
    }
}
