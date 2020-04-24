<?php
#Smarty security policy
#Copyright (C) 2011-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS\internal;

use CmsApp;
use Smarty;
use Smarty_Security;

/**
 * Generic smarty security policy.
 * @final
 *
 * @since		1.11
 * @package		CMS
 * @internal
 * @ignore
 */
final class smarty_security_policy extends Smarty_Security
{
    public function __construct($smarty)
    {
        parent::__construct($smarty);
        $this->php_handling = Smarty::PHP_REMOVE; // escape literal <?php ... ? > tags in templates
        $this->php_modifiers = []; // allow all
        $this->secure_dir = null; // block stuff happening outside the specified directories
        $this->streams = null; // no streams allowed
//        $this->allow_super_globals = false;
        $gCms = CmsApp::get_instance();
        if( $gCms->is_frontend_request() ) {
            $this->allow_constants = false;
            if( !$gCms->GetConfig()['permissive_smarty'] ) {
                $this->static_classes = null;
                // allow most methods that do data interpretation, modification or formatting
                // e.g. string searches, array searches, string comparison, sorting, etc.
                $this->php_functions = [
                'array_flip',
                'array_rand',
                'array_reverse',
                'array_search',
                'asort',
                'cms_html_entity_decode',
                'count',
                'date',
                'debug_display',
                'empty',
                'endswith',
                'explode',
                'file_exists',
                'html_entity_decode',
                'htmlentities',
                'htmlspecialchars_decode',
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
                'strftime',
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
} // class
