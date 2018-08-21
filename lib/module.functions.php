<?php
#module-related methods available for every request
#Copyright (C) 2004-2010 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2011-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

/**
 * Module-directories lister. Checks for directories existence, including $modname if provided.
 *
 * @since 2.3
 * @param string $modname Optional name of a module
 * @return array of absolute filepaths, no trailing separators, or maybe empty.
 *  Core-modules-path first, deprecated last.
 */
function cms_module_places(string $modname = '') : array
{
    $dirlist = [];
    $path = cms_join_path(CMS_ROOT_PATH,'lib','modules');
    if ($modname) {
        $path .= DIRECTORY_SEPARATOR . $modname;
    }
    if (is_dir($path)) {
        $dirlist[] = $path;
    }
    $path = cms_join_path(CMS_ASSETS_PATH,'modules');
    if ($modname) {
        $path .= DIRECTORY_SEPARATOR . $modname;
    }
    if (is_dir($path)) {
        $dirlist[] = $path;
    }
    // pre-2.3, deprecated
    $path = cms_join_path(CMS_ROOT_PATH,'modules');
    if ($modname) {
        $path .= DIRECTORY_SEPARATOR . $modname;
    }
    return $dirlist;
}

/**
 * Module-file locator which doesn't need the module to be loaded.
 *
 * @since 2.3
 * @param string $modname name of the module.
 * @return string (maybe empty)
 */
function cms_module_path(string $modname) : string
{
    // core-modules place
    $path = cms_join_path(CMS_ROOT_PATH,'lib','modules',$modname,$modname.'.module.php');
    if (is_file($path)) {
        return $path;
    }
    // other-modules place
    $path = cms_join_path(CMS_ASSETS_PATH,'modules',$modname,$modname.'.module.php');
    if (is_file($path)) {
        return $path;
    }
    // pre-2.3, deprecated
    $path = cms_join_path(CMS_ROOT_PATH,'modules',$modname,$modname.'.module.php');
    if (is_file($path)) {
        return $path;
    }
    return '';
}

/**
 * Call a module as a smarty plugin
 * This method is used by the {cms_module} plugin, and internally when {ModuleName} is called
 *
 * @internal
 * @access private
 * @param array A hash of parameters
 * @param object A Smarty_Internal_Template object
 * @return mixed The module output string or null
 */
function cms_module_plugin(array $params, $template)
{
    //if( get_class($smarty) == 'Smarty_Parser' ) return; // if we are in the parser, we don't process module calls.
    $modulename = '';
    $action = 'default';
    $inline = false;
    $returnid = CmsApp::get_instance()->get_content_id();
    $id = null;
    if (isset($params['module'])) {
        $modulename = $params['module'];
        unset($params['module']);
    }
    else {
        return '<!-- ERROR: module name not specified -->';
    }

    // get a unique id/prefix for this modle call.
    if( isset( $params['idprefix']) ) {
        $id = $params['idprefix'];
        unset($params['idprefix']);
    } else {
        $mid_cache = cms_utils::get_app_data('mid_cache');
        if( empty($mid_cache) ) $mid_cache = [];
        $tmp = serialize( $params );
        for( $i = 0; $i < 10; $i++ ) {
            $id = 'm'.substr(md5($tmp.$i),0,5);
            if( !isset($mid_cache[$id]) ) {
                $mid_cache[$id] = $id;
                break;
            }
        }
        cms_utils::set_app_data('mid_cache',$mid_cache);
    }

    if (!empty($params['action'])) {
        // action was set in the module tag
        $action = $params['action'];
        unset( $params['action']);
    }

    if (isset($_REQUEST['mact'])) {
        // we're handling an action.  check if it is for this call.
        // we may be calling module plugins multiple times in the template,
        // but a POST or GET mact can only be for one of them.
        $checkid = null;
        $mact = filter_var($_REQUEST['mact'], FILTER_SANITIZE_STRING);
        $ary = explode(',', $mact, 4);
        $mactmodulename = $ary[0] ?? '';
        if( 0 == strcasecmp($mactmodulename, $modulename) ) {
            $checkid = $ary[1] ?? '';
            $mactaction = $ary[2] ?? '';
            $inline = isset($ary[3]) && $ary[3] === 1;

            if ($checkid == $id && $inline == true ) {
                // the action is for this instance of the module and we're inline
				// (i.e. the results are supposed to replace the tag, not {content}
                $action = $mactaction;
                $params = array_merge($params, ModuleOperations::get_instance()->GetModuleParameters($id));
            }
        }
    }

    class_exists($modulename); // autoload? why
    $module = cms_utils::get_module($modulename);
    global $CMS_ADMIN_PAGE, $CMS_LOGIN_PAGE, $CMS_INSTALL;
    if( $module && ($module->isPluginModule() || (isset($CMS_ADMIN_PAGE) && !isset($CMS_INSTALL) && !isset($CMS_LOGIN_PAGE))) ) {
        @ob_start();
        $result = $module->DoActionBase($action, $id, $params, $returnid, $template);
        if ($result !== FALSE) echo $result;
        $modresult = @ob_get_contents();
        @ob_end_clean();

        if( isset($params['assign']) ) {
            $template->assign(trim($params['assign']),$modresult);
            return '';
        }
        return $modresult;
    }
    else {
        return "<!-- $modulename is not a plugin module -->\n";
    }
}
