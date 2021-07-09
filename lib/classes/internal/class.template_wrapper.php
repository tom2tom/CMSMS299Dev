<?php
/*
Default Smarty template class for CMSMS
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\internal;

use CMSMS\AppSingle;
use CMSMS\Events;
use Smarty_Internal_Template;

/**
 * Default Smarty template class for CMSMS
 * Enables intervention in template-processing
 */
class template_wrapper extends Smarty_Internal_Template
{
    public function createTemplate($template, $cache_id = null, $compile_id = null, $parent = null, $do_clone = true)
    {
        if( $parent == null ) {
            $parent = $this;
        }
//        try {
            return $this->smarty->createTemplate($template, $cache_id, $compile_id, $parent, $do_clone);
//        } catch (Throwable $t) {
//            //TODO handle error nicely
//        }
    }

    /**
     * fetch a rendered Smarty template
     *
     * @param  string $template   optional resource handle of the template file or template object
     * @param  mixed  $cache_id   optional cache id to be used with this template
     * @param  mixed  $compile_id optional compile id to be used with this template
     * @param  object $parent     optional next-higher level of Smarty variables
     * @return string rendered template output
     */
    public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        // send an event before fetching...this allows us to change template stuff.
        if( AppSingle::App()->is_frontend_request() ) {
            $display = false;
            $parms = [
             'template'=>&$template,
             'cache_id'=>&$cache_id,
             'compile_id'=>&$compile_id,
             'display'=>&$display,
            ];
            Events::SendEvent( 'Core', 'TemplatePrefetch', $parms );
        }
        return parent::fetch($template,$cache_id,$compile_id,$parent);
    }
}
