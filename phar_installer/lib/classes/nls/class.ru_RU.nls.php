<?php
namespace cms_installer\nls;

use cms_installer\nls;

final class ru_RU_nls extends nls
{
    public function __construct()
    {
        $this->_fullname = 'Russian';
        $this->_display = 'Русский';
        $this->_isocode = 'ru';
        $this->_locale = 'ru_RU';
        $this->_encoding = 'UTF-8';
        $this->_aliases = 'russian,ru,rus,ru_RU,ru_RU.ISO3166-2'; // main ru nls has more
    }
}
