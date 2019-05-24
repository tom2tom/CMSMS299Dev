<?php
assert(empty(CMS_DEBUG), new DeprecationNotice('class','DesignManager\\Design'));
class_alias('DesignManager\\Design', 'CmsLayoutCollection');
