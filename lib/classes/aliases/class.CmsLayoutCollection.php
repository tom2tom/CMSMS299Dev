<?php
assert(empty(CMS_DEPREC), new CMSMS\DeprecationNotice('class', 'DesignManager\\Design'));
$obj = cms_utils::get_module('DesignManager');
if ($obj) {
    //try to autoload this one
    class_alias('DesignManager\Design', 'CmsLayoutCollection', true);
} else {
    throw new Exception('The DesignManager module is needed when using a CmsLayoutCollection object');
}
