<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class', 'DesignManager\\Design'));
// try to autoload this one
class_alias('DesignManager\Design', 'CmsLayoutCollection', true);
