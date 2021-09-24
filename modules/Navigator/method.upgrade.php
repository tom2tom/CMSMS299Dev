<?php

use CMSMS\TemplateType;
use function CMSMS\log_error;

if( !isset($gCms) ) exit;

if( version_compare($oldversion,'1.0.5') < 0 ) {
    try {
        $types = TemplateType::load_all_by_originator($this->GetName());
        if( $types ) {
            foreach( $types as $type_obj ) {
                $type_obj->set_help_callback('Navigator::template_help_callback');
                $type_obj->save();
            }
        }
    }
    catch (Throwable $t) {
        log_error($this->GetName(),'Upgrade error: '.$t->GetMessage());
        return $t->GetMessage();
    }
/* TODO robust migration from Menu-Manager module ..
$this->RegisterSmartyPlugin('menu', 'function', 'function_plugin');
$this->RegisterSmartyPlugin('cms_breadcrumbs', 'function', 'nav_breadcrumbs');
*/
}
