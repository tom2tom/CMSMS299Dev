<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','DesignManager\\Design'));
// autoload this one if possible
class_alias('DesignManager\Design', 'CmsLayoutCollection');
