<?php
//deprecated stub file
//class introduced in 2.3, pretty-much useless, unused since 2.9
namespace CMSMS;

use CMSMS\DeprecationNotice;
use const CMS_DEPREC;

assert(empty(CMS_DEPREC), new DeprecationNotice('Class file '.basename(__FILE__).' used'));

class frontend_theme_placeholder
{
    public function get_exported_page_templates() {}
    public function get_location() {}
    public function get_urlbase() {}
    public function has_template() {}
    public function get_template_file() {}
}
