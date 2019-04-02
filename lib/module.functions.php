<?php
#module-related methods available for every request
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
    if (is_dir($path)) {
        $dirlist[] = $path;
    }
    return $dirlist;
}

/**
 * Module-file locator which doesn't need the module to be loaded.
 *
 * @since 2.3
 * @param string $modname name of the module.
 * @param bool $folder Optional flag whether to return filepath of folder containing the module Default false
 * @return string filepath of module class, or its parent folder (maybe empty)
 */
function cms_module_path(string $modname, bool $folder = false) : string
{
    $p = DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$modname.DIRECTORY_SEPARATOR.$modname.'.module.php';
    // core-modules place
    $path = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.$p;
    if (is_file($path)) {
        return ($folder) ? dirname($path) : $path;
    }
    // other-modules place
    $path = CMS_ASSETS_PATH.$p;
    if (is_file($path)) {
        return ($folder) ? dirname($path) : $path;
    }
    // pre-2.3, deprecated
    $path = CMS_ROOT_PATH.$p;
    if (is_file($path)) {
        return ($folder) ? dirname($path) : $path;
    }
    return '';
}

/**
 * Call a module as a smarty plugin
 * This method is used by the {cms_module} plugin and to process {ModuleName} tags
 *
 * @internal
 * @access private
 * @param array A hash of parameters
 * @param object A Smarty_Internal_Template object
 * @return mixed The module output string or an error message string or null
 */
function cms_module_plugin(array $params, $template)
{
//  if (get_class($smarty) == 'Smarty_Parser') return; // if we are in the parser, we don't process module calls.
    if (isset($params['module'])) {
        $module = $params['module'];
        unset($params['module']);
    }
    else {
        return '<!-- ERROR: module name not specified -->';
    }

    if (!empty($params['action'])) {
        // action was set in the module tag
        $action = $params['action'];
//       unset($params['action']);  unfortunate 2.3 deprecation
    }
    else {
        $params['action'] = $action = 'default'; //2.3 deprecation
    }
    if (!empty($params['idprefix'])) {
        // idprefix was set in the module tag
        $id = $params['idprefix'];
        $setid = true;
    }
    else {
        $setid = false;
        // get a random(ish) id for this module operation
        //CHECKME what relevance here for the per-request cache? Same tag repeated ?
        $mid_cache = cms_utils::get_app_data('mid_cache');
        if (!is_array($mid_cache)) $mid_cache = [];
        while (1) {
            $id = 'm    ';
            for ($i=1; $i<5; ++$i) {
                $n = mt_rand(48, 122); // 0 .. z
                if (!(($n > 57 && $n < 66) || ($n > 90 && $n < 97))) {
                    $id[$i] = chr($n); // ASCII alphanum
                } else {
                    --$i; // try again
                }
            }
            if (!isset($mid_cache[$id])) {
                $mid_cache[$id] = $id; // seems useless
            }
            break;
        }
        cms_utils::set_app_data('mid_cache',$mid_cache);
    }

    if (isset($_REQUEST['mact'])) {
        // We're handling an action.  Check if it is for this call.
        // We may be calling module plugins multiple times in the template,
        // but a POST or GET mact can only be for one of them.
        $mact = filter_var($_REQUEST['mact'], FILTER_SANITIZE_STRING);
        $ary = explode(',', $mact, 4);
        $mactmodulename = $ary[0] ?? '';
        if (strcasecmp($mactmodulename, $module) == 0) {
            $checkid = $ary[1] ?? '';
            $inline = isset($ary[3]) && $ary[3] === 1;
            if ($inline && $checkid == $id) { // presumbly $setid true i.e. not a random id
                // the action is for this instance of the module and we're inline
                // i.e. the results are supposed to replace the tag, not {content}
                $action = $ary[2] ?? 'default';
                $params['action'] = $action; // deprecated since 2.3
                $params = array_merge($params, (new ModuleOperations())->GetModuleParameters($id));
            }
        }
    }

//    class_exists($module); // autoload? why
    $modinst = cms_utils::get_module($module);
    if (!$modinst) {
        return "<!-- ERROR: $module is not a recognized module -->\n";
    }

    global $CMS_ADMIN_PAGE, $CMS_LOGIN_PAGE, $CMS_INSTALL;
    // WHAAAT ? admin-request accepts ALL modules as plugins (lazy/bad module init?)
    if ($modinst->isPluginModule() || (isset($CMS_ADMIN_PAGE) && !isset($CMS_INSTALL) && !isset($CMS_LOGIN_PAGE))) {
        $params['id'] = $id; // deprecated since 2.3
        if ($setid) {
            $params['idprefix'] = $id; // might be needed per se, probably not
            $modinst->SetParameterType('idprefix',CLEAN_STRING); // in case it's a frontend request
        }
        $returnid = CmsApp::get_instance()->get_content_id();
        $params['returnid'] = $returnid;

        @ob_start(); // probably redundant
        $result = $modinst->DoActionBase($action, $id, $params, $returnid, $template);
        if ($result !== false) {
            echo $result;
        }
        $out = @ob_get_contents();
        @ob_end_clean();

        if (isset($params['assign'])) {
            $template->assign(trim($params['assign']),$out);
            return '';
        }
        return $out;
    }
    elseif (!$modinst->isPluginModule()) {
        return "<!-- ERROR: $module is not a plugin module -->\n";
    }
}
