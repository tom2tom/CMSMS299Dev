<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'DesignManager\Design'));
$mod = CMSMS\Utils::get_module('DesignManager');
if ($mod) {
    //try to autoload this one
    class_alias('DesignManager\Design', 'CmsLayoutCollection', true);
} else {
    throw new Exception('The DesignManager module is needed to substitute for a CmsLayoutCollection object');
}
