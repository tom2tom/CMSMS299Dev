<?php
/*
Smarty security policy
Copyright (C) 2011-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\internal;

use CMSMS\Lone;
//use Smarty;
use Smarty_Security;
use function CMSMS\is_frontend_request;

/**
 * Generic smarty security policy.
 * @final
 * @since    3.0
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
//Smarty 2/3 $this->php_handling = Smarty::PHP_REMOVE; // escape literal <?php ... ? > tags in templates
        $this->php_modifiers = []; // allow all
        $this->secure_dir = null; // block stuff happening outside the specified directories
        $this->streams = null; // no streams allowed
//      $this->allow_super_globals = false;
        if( is_frontend_request() ) {
            $this->allow_constants = false;
            if( !Lone::get('Config')['permissive_smarty'] ) {
                $this->static_classes = null;
                // allow most methods that do data interpretation,
                // modification or formatting ahead of or during display
                // e.g. string searches, array searches, string comparison, sorting, etc.
                $this->php_functions = [
                '_la', //since 3.0
                '_ld', //since 3.0
                '_lm', //since 3.0
//              'addcslashes', //since 3.0 escaper for vars in js strings BUT otherwise dangerous?
                'array_flip',
                'array_rand',
                'array_reverse',
                'array_search',
                'asort',
                'cms_join_path', //since 3.0
                'CMSMS\entitize', //since 3.0
                'CMSMS\specialize', //since 3.0
                'CMSMS\sanitizeVal', //since 3.0
                'CMSMS\tailorpage', //since 3.0
                'count',
                'date',
                'debug_display',
                'empty',
                'endswith',
                'explode',
                'file_exists',
                'html_entity_decode', //unused input-cleaner, deprecated since 3.0
                'htmlentities',
                'htmlspecialchars_decode', //unused input-cleaner, deprecated since 3.0
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
                'lang_by_realm', //since 3.0
                'mt_jsbool', //Microtiny-module method
                'nl2br',
                'print_r',
                'shuffle',
                'sizeof',
                'sort',
                'startswith',
                'str_contains', //since 3.0 PHP8+
                'str_ends_with', //since 3.0 PHP8+
                'str_starts_with', //since 3.0 PHP8+
                'str_replace',
                'strcasecmp',
                'strcmp',
                'stripos', //since 3.0
                'strlen',
                'strpos',
                'strripos', //since 3.0
                'strrpos', //since 3.0
                'strtolower',
                'strtotime',
                'strtoupper',
                'substr',
                'time',
                'trim',
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
