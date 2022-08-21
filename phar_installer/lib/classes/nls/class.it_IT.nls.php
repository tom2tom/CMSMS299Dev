<?php
namespace cms_installer\nls;

use cms_installer\nls;

final class it_IT_nls extends nls
{
    public function __construct()
    {
        $this->_fullname = 'Italian';
        $this->_display = 'Italiano';
        $this->_isocode = 'it';
        $this->_locale = 'it_IT';
        $this->_encoding = 'UTF-8';
        $this->_aliases = 'it_IT.utf8,it_IT.utf-8,it_IT.UTF-8,it_IT,it_IT@euro,italian,Italian_Italy.1252,ISO8859_1';
    }
}
