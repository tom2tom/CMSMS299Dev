<?php

namespace CMSMS\internal;

use CmsApp;
use CMSMS\Events;
use Smarty_Internal_Template;

class template_wrapper extends Smarty_Internal_Template
{
    public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        // send an event before fetching...this allows us to change template stuff.
        if( CmsApp::get_instance()->is_frontend_request() ) {
            $parms = ['template'=>&$template,'cache_id'=>&$cache_id,'compile_id'=>&$compile_id,'display'=>&$display];
            Events::SendEvent( 'Core', 'TemplatePrefetch', $parms );
        }
        return parent::fetch($template,$cache_id,$compile_id,$parent);
    }
}
