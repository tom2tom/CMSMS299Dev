<?php
assert(empty(CMS_DEPREC), new DeprecationNotice('class','DesignManager\\Design'));
class_alias('DesignManager\Design', 'CmsLayoutCollection');
