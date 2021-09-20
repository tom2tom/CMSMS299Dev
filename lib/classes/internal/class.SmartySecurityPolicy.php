<?php
/*
Smarty security policy
Copyright (C) 2011-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\SingleItem;
use Smarty;
use Smarty_Security;

/**
 * Generic smarty security policy.
 * @final
 * @since    2.99
 * @since    1.11 as smarty_security_policy
 * @package  CMS
 * @internal
 * @ignore
 */
final class SmartySecurityPolicy extends Smarty_Security
{
    public function __construct($smarty)
    {
        parent::__construct($smarty);
        $this->php_handling = Smarty::PHP_REMOVE; // escape literal <?php ... ? > tags in templates
        $this->php_modifiers = []; // allow all
        $this->secure_dir = null; // block stuff happening outside the specified directories
        $this->streams = null; // no streams allowed
//      $this->allow_super_globals = false;
        if( SingleItem::App()->is_frontend_request() ) { // TODO AppState...
            $this->allow_constants = false;
            if( !SingleItem::Config()['permissive_smarty'] ) {
                $this->static_classes = null;
                // allow most methods that do data interpretation,
                // modification or formatting ahead of or during display
                // e.g. string searches, array searches, string comparison, sorting, etc.
                $this->php_functions = [
                '_la', //since 2.99
                '_ld', //since 2.99
                '_lm', //since 2.99
                'array_flip',
                'array_rand',
                'array_reverse',
                'array_search',
                'asort',
                'CMSMS\entitize', //since 2.99
                'CMSMS\specialize', //since 2.99
                'CMSMS\sanitizeVal', //since 2.99
                'CMSMS\tailorpage', //since 2.99
                'count',
                'date',
                'debug_display',
                'empty',
                'endswith',
                'explode',
                'file_exists',
                'html_entity_decode', //unused input-cleaner, deprecated since 2.99
                'htmlentities',
                'htmlspecialchars_decode', //unused input-cleaner, deprecated since 2.99
                'htmlspecialchars',
                'implode',
                'in_array',
                'is_array',
                'is_dir',
                'is_file',
                'is_object',
                'is_string',
                'isset',
                'json_decode',
                'json_encode',
                'ksort',
                'lang',
                'lang_by_realm', //since 2.99
                'mt_jsbool', //Microtiny module method
                'nl2br',
                'print_r',
                'shuffle',
                'sizeof',
                'sort',
                'startswith',
                'str_replace',
                'strcasecmp',
                'strcmp',
                'strlen',
                'strpos',
                'strtolower',
                'strtotime',
                'strtoupper',
                'substr',
                'time',
                'urlencode',
                'var_dump',
                ];
            }
            else {
                $this->php_functions = []; // allow any php method
            }
        }
        else {
            $this->php_functions = [];
        }
    }
}
