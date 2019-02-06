<?php
#Smarty security policy
#Copyright (C) 2011-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use Smarty_Security;

/**
 * Generic smarty security policy.
 *
 * @since		1.11
 * @package		CMS
 * @internal
 * @ignore
 */
final class smarty_security_policy extends Smarty_Security
{
    public $php_handling = Smarty::PHP_REMOVE;

    public $secure_dir = null; // this is the magic that stops stuff from happening outside of the specified directories.
    public $php_modifiers = [];
    //public $php_modifiers = array('escape','count','preg_replace','lang', 'ucwords','print_r','var_dump','trim','htmlspecialchars','explode','htmlspecialchars_decode','strpos','strrpos','startswith','endswith','substr);
    public $streams = null;
    public $allow_constants = false;
    //public $allow_super_globals = false;
    public $allow_php_tag = false;

    public function __construct($smarty)
    {
        parent::__construct($smarty);
        $this->allow_php_tag = false;
        $gCms = CmsApp::get_instance();
        if($gCms->is_frontend_request() ) {
            $this->static_classes = []; // allow all static classes
            $this->php_functions = []; // allow any php functions
            $config = $gCms->GetConfig();
            if( !$config['permissive_smarty'] ) {
                $this->static_classes = null;
                // this should allow most stuff that does modification to data or formatting.
                // i.e: string searches, array searches, string comparison, sorting, etc.
                $this->php_functions = [
'isset','implode','explode','empty','count','sizeof','in_array', 'is_array','time','lang',
'str_replace','is_string','strpos','substr','strtolower','strtoupper','strcmp','strcasecmp','strlen','array_search','sort','ksort','asort',
'nl2br','file_exists','is_object','is_file','is_dir','print_r','var_dump','array_reverse','array_flip','shuffle','array_rand',
'debug_display','startswith','endswith','urlencode','json_encode','json_decode','mt_jsbool',
'htmlspecialchars','htmlspecialchars_decode','htmlentities','html_entity_decode','cms_html_entity_decode'
];
            }
        }
        else {
            $this->php_functions = [];
            $this->static_classes = [];
            $this->allow_constants = true;
        }
    }
} // class
