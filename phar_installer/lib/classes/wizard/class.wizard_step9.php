<?php
namespace cms_installer\wizard;

use cms_installer\installer_base;
use cms_installer\wizard\wizard_step;
use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\CapabilityType;
use CMSMS\Lone;
use Exception;
use Throwable;
use const CMS_DB_PREFIX;
use const CMS_ROOT_PATH;
use const CMS_VERSION;
use const CONFIG_FILE_LOCATION;
use const TMP_CACHE_LOCATION;
use function cms_installer\endswith;
use function cms_installer\get_app;
use function cms_installer\get_server_permissions;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\rrmdir;
use function cms_installer\smarty;
use function cms_installer\startswith;
use function cms_module_places;
use function CMSMS\log_notice;

class wizard_step9 extends wizard_step
{
    /**
     *
     * @throws Exception
     */
    protected function display()
    {
        $wiz = $this->get_wizard();
        parent::display();
        $smarty = smarty();
        $smarty->assign('back_url', $wiz->prev_url());
        $smarty->display('wizard_step9.tpl');

        $app = get_app();
        $destdir = $app->get_destdir();

        try {
            if (!$destdir) {
                throw new Exception(lang('error_internal', 905));
            }
            $cust = $app->has_custom_destdir();
            $action = $wiz->get_data('action');
            switch ($action) {
            case 'install':
                $this->do_install($app);
                list($main_url, $admin_url) = $this->get_admin_url($app);
                $msg = ($cust) ?
                    lang('finished_custom_install_msg', $admin_url) :
                    lang('finished_install_msg', $main_url, $admin_url);
                break;
            case 'upgrade':
                $tmp = $wiz->get_data('version_info');
                if (!$tmp) {
                    throw new Exception(lang('error_internal', 910));
                }
                $this->do_upgrade($app, $tmp);
                list($main_url, $admin_url) = $this->get_admin_url($app);
                $msg = ($cust) ?
                    lang('finished_custom_upgrade_msg', $admin_url, $main_url) :
                    lang('finished_upgrade_msg', $main_url, $admin_url);
                break;
            case 'freshen':
                $this->do_freshen($app);
                if ($cust) {
                    $msg = lang('finished_custom_freshen_msg');
                } else {
                    list($main_url, $admin_url) = $this->get_admin_url($app);
                    $msg = lang('finished_freshen_msg', $main_url, $admin_url);
                }
                break;
            default:
                throw new Exception('Installer session has terminated'); // TODO better msg if session N/A now
            }

            sleep(3); //time to absorb this page as is

            $this->alldone($msg); //show 'finished' message and links
            $app->cleanup(); //no more me
        } catch (Throwable $t) {
            $this->error($t->GetMessage());
        }
    }

    /**
     * @internal
     * @param string $destdir site absolute root-path
     */
    private function connect_to_cmsms(string $destdir)
    {
        require_once $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
        AppState::set(AppState::INSTALL);
        $fp = $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
        if (is_file($fp)) {
            include_once $fp;
        } else {
            require_once $destdir.DIRECTORY_SEPARATOR.'include.php';
        }
    }

    /**
     * Try to create local cache directories if they're gone, otherwise
     * try to clear. In either case, dummy index.html(s) are provided.
     * And if they're in a newly-created sub-tree, also add such dummy
     * file in each ancestor folder.
     * @internal
     *
     * @param string $destdir site absolute root-path
     */
    private function clear_filecaches(string $destdir)
    {
        $l = strlen($destdir);
        $perms = get_server_permissions();
        // caches might be anywhere, now
        $places = array_unique(array_filter([
            constant('TMP_CACHE_LOCATION'),
            constant('TMP_TEMPLATES_C_LOCATION'),
            constant('PUBLIC_CACHE_LOCATION'),
        ]));
        // process shallower before deeper
        sort($places, SORT_NATURAL);

        foreach ($places as $fp) {
            if (startswith($fp, $destdir)) {
                if (is_dir($fp)) {
                    if (!is_writable($fp)) {
                        chmod($fp, $perms[3]); // read+write+exec
                    }
                    rrmdir($fp, false);
                } else {
                    mkdir($fp, $perms[3], true);
                }
                // the place might be in a newly-created sub-tree
                do {
                    if (!is_file($fp.DIRECTORY_SEPARATOR.'index.php')) {
                        touch($fp.DIRECTORY_SEPARATOR.'index.html');
                    }
                    $fp = dirname($fp);
                    if (strlen($fp) <= $l || !startswith($fp, $destdir)) {
                        break;
                    }
                    if (!is_writable($fp)) {
                        chmod($fp, $perms[3]);
                    }
                } while (is_dir($fp));
            } else {
                throw new Exception('Invalid location for cache folder');
            }
        }
    }

    /**
     * Install relevant .htaccess or web.config files, and set some
     * restricted file-permissions
     * @internal
     *
     * @param string $destdir site absolute root-path
     * @param bool $upgrade optional flag whether the installer is upgrading Default true
     */
    private function securitize(string $destdir, bool $upgrade = true)
    {
        $perms = get_server_permissions();
        $filemode = $perms[0]; // read-only OR 0444?

        // some security for the config-data files
        @chmod(CONFIG_FILE_LOCATION, $filemode);
        $fp = $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'version.php';
        @chmod($fp, $filemode);

        // ensure current .htaccess or web.config files to limit direct access
        $str = $_SERVER['SERVER_SOFTWARE'] ?? '';
        if (!$str) {
            $str = ''.PHP_SAPI;
        }
        if (stripos($str, 'apache') !== false) {
            $apache = true;
            $tofn = '.htaccess';
        } elseif (stripos($str, 'iis') !== false) {
            $apache = false;
            $tofn = 'web.config';
        } else {
            return;
        }
        if ($upgrade) {
            $config = Lone::get('Config');
        }

        // tmp folder
        $fp = dirname(TMP_CACHE_LOCATION).DIRECTORY_SEPARATOR. $tofn;
        if ($upgrade && is_file($fp)) {
            @chmod($fp, $perms[1]); // ensure writable
        }
        $fromfn = ($apache) ? 'block.tmp.htaccess' : 'block.tmp.config';
        $sp = joinpath($destdir, 'lib', 'security', $fromfn);
        copy($sp, $fp);
        @chmod($fp, $filemode);
        // topmost admin folder
        if ($upgrade) {
            $bp = $destdir.DIRECTORY_SEPARATOR.$config['admin_dir'];
        } else {
            $bp = $destdir.DIRECTORY_SEPARATOR.'admin';
        }
        $fp = $bp.DIRECTORY_SEPARATOR.$tofn;
        if ($upgrade && is_file($fp)) {
            @chmod($fp, $perms[1]);
        }
        $fromfn = ($apache) ? 'block.admin.htaccess' : 'block.admin.config';
        $sp = joinpath($destdir, 'lib', 'security', $fromfn);
        copy($sp, $fp);
        @chmod($fp, $filemode);
        // also the admin/themes sub-folder
        $fp = $bp.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.$tofn;
        if ($upgrade && is_file($fp)) {
            @chmod($fp, $perms[1]);
        }
        copy($sp, $fp);
        @chmod($fp, $filemode);
        // uploads folder
        if ($upgrade) {
            $bp = $config['uploads_path'];
        } else {
            $bp = $destdir.DIRECTORY_SEPARATOR.'uploads';
        }
        $fp = $bp.DIRECTORY_SEPARATOR.$tofn;
        if ($upgrade && is_file($fp)) {
            @chmod($fp, $perms[1]);
        }
        $fromfn = ($apache) ? 'block.exe.htaccess' : 'block.exe.config';
        $sp = joinpath($destdir, 'lib', 'security', $fromfn);
        copy($sp, $fp);
        @chmod($fp, $filemode);
        // modules folder
        $bp = $destdir.DIRECTORY_SEPARATOR.'modules';
        $fp = $bp.DIRECTORY_SEPARATOR.$tofn;
        if ($upgrade && is_file($fp)) {
            @chmod($fp, $perms[1]);
        }
        copy($sp, $fp);
        @chmod($fp, $filemode);
        // topmost lib folder
        $fp = $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.$tofn;
        if ($upgrade && is_file($fp)) {
            @chmod($fp, $perms[1]);
        }
        $fromfn = ($apache) ? 'allow.moduleinterface.htaccess' : 'allow.moduleinterface.config';
        $sp = joinpath($destdir, 'lib', 'security', $fromfn);
        copy($sp, $fp);
        @chmod($fp, $filemode);
        // doc folder
        $fp = $destdir.DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.$tofn;
        if ($upgrade && is_file($fp)) {
            @chmod($fp, $perms[1]);
        }
        $fromfn = ($apache) ? 'block.doc.htaccess' : 'block.doc.config';
        $sp = joinpath($destdir, 'lib', 'security', $fromfn);
        copy($sp, $fp);
        @chmod($fp, $filemode);
        // user_plugins folder
        if ($upgrade) {
            $fp = $config['usertags_path'].DIRECTORY_SEPARATOR.$tofn;
        } else {
            $ops = Lone::get('UserTagOperations');
            $sp = $ops->FilePath($tofn);
            $fp = str_replace($ops::PLUGEXT, '', $sp); // strip trailing fake-extension
        }
        if ($upgrade && is_file($fp)) {
            @chmod($fp, $perms[1]);
        }
        $fromfn = ($apache) ? 'block.plugins.htaccess' : 'block.plugins.config';
        $sp = joinpath($destdir, 'lib', 'security', $fromfn);
        copy($sp, $fp);
        @chmod($fp, $filemode);
        // TODO anywhere else ? modules (c.f. uploads must allow e.g. module-specific css,js) ? any-name-assets ?
    }

    /**
     * @ignore
     * @param int $action (1 = install, 2 = upgrade, 3 = freshen)
     * @param string $destdir site absolute root-path
     */
    private function system_setup(int $action, string $destdir)
    {
        switch ($action) {
            case 1: // install
                // setup some access constraints
                $this->securitize($destdir, false);
                // init content types
                Lone::get('ContentTypeOperations')->RebuildStaticContentTypes();
                //TODO cache data init etc
                break;
            case 2: // upgrade
                $this->securitize($destdir);
                // clear the caches
                $this->message(lang('msg_clearcache'));
                $this->clear_filecaches($destdir); // also populates missing index.html's
//                Lone::get('SystemCache')->clear('*'); // or just delete?
                //CHECKME clear flag so that normal-request module-operations get done
                // BUT this has no effect on constructor-flag in ModuleOprations singleton !
                // AppState::remove(AppState::INSTALL);
                $cache = Lone::get('LoadedData');
                $cache->get('site_params', true); // want 'coremodules' etc
                // re-populate 'troublesome' (runtime-recursive) caches
                $cache->get('modules', true);
                $cache->get('module_deps', true);
                $cache->get('module_depstree', true);

                $cache2 = Lone::get('LoadedMetadata');
                if ($cache2->has('capable_modules')) {
                    $cache2->delete('capable_modules', '*'); // capabilities might have changed now
                }
                if ($cache2->has('methodic_modules')) {
                    $cache2->delete('methodic_modules', '*');
                }
                $cache2->get('capable_modules', true, CapabilityType::PLUGIN_MODULE);
                $cache2->get('methodic_modules', true, 'IsPluginModule');

                $cache->get('module_plugins', true); // uses capable_modules and methodic_modules
                // these ones can wait for on-demand regeneration ?
                if ($cache->has('routes')) {
                    $cache->delete('routes');
                }
                // re-populate content-types cache
                Lone::get('ContentTypeOperations')->RebuildStaticContentTypes(); // uses unforced 'methodic_modules' metadata
                break;
            case 3: // freshen
                // freshen permissions for config files etc
                $this->securitize($destdir);
                // clear file-caches
                $this->message(lang('msg_clearcache'));
                $this->clear_filecaches($destdir); // OR AdminUtils::clear_cached_files()
                // no need to alter caches
                // freshen content-types
// NOPE         Lone::get('ContentTypeOperations')->RebuildStaticContentTypes();
                break;
        }
    }

    /**
     * @ignore
     * @param installer_base $app
     * @throws Exception
     */
    private function do_upgrade(installer_base $app)
    {
        $destdir = $app->get_destdir();
        if (!$destdir) {
            throw new Exception(lang('error_internal', 900));
        }
        $this->connect_to_cmsms($destdir);

        // upgrade recorded version-parameters (before module-upgrades)
        $val = $app->get_dest_schema();
        AppParams::set('cms_schema_version', $val);
        $val = $app->get_dest_name();
        if (!$val) {
            $val = 'Anonymous Alfred';
        }
        AppParams::set('cms_version_name', $val);
        $val = $app->get_dest_version();
        if (!$val) {
            $val = '0.0.0';
        }
        AppParams::set('cms_version', $val);

        $this->message(lang('msg_upgrademodules'));
        // upsert core modules
        $db = Lone::get('Db');
        //(module_name,version,status,admin_only,active,allow_fe_lazyload,allow_admin_lazyload)
        $stmt1 = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.'modules
(module_name,version,admin_only,active) VALUES (?,?,?,1)');
        $stmt2 = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.'module_deps
(parent_module,child_module,minimum_version,create_date) VALUES (?,?,?,NOW())');

        $modops = Lone::get('ModuleOperations');
        $corenames = $app->get_config()['coremodules'];
//abandoned        $modops->RegisterSystemModules($coremodules);

        $choices = $this->get_wizard()->get_data('sessionchoices');
        $installmodules = $choices['havemodules'] ?? []; //cores maybe plus non-cores
        $currentmodules = Lone::get('LoadedData')->get('modules'); // installed-module data

        foreach ($installmodules as $modname) {
            if (isset($currentmodules[$modname])) {
                $res = $modops->UpgradeModule($modname); // ignored if no change is needed
                if ($res[0]) {
                    $this->verbose(lang('msg_upgrade_module', $modname));
                } else {
                    $msg = lang('error_modulebad', $modname).': '.$res[1];
                    $this->error($msg);
                }
            //module not installed, install if it's a new core, otherwise ignore i.e. don't automatically upgrade
            } elseif (in_array($modname, $corenames)) {
                $fp = joinpath($destdir, 'modules', $modname, $modname.'.module.php');
                try {
                    require_once $fp;
                    $classname = '\\'.$modname; // if modules' namespace changes from global, adjust this
                    $mod = new $classname();
                    if ($mod) {
                        $this->mod_install($mod, $db, $stmt1, $stmt2);
                        $this->verbose(lang('install_module', $modname));
                    } else {
                        $this->error(lang('error_modulebad', $modname));
                    }
                } catch (Throwable $t) {
                    $msg = lang('error_modulebad', $modname);
                    $tmp = $t->GetMessage();
                    if (is_string($tmp) && $tmp) {
                        $msg .= ': '.$tmp;
                    }
                    $this->error($msg);
                }
            }
        }
        $stmt1->close();
        $stmt2->close();

        // adjust users' startpage if necessary
        $query = 'SELECT user_id,`value` FROM '.CMS_DB_PREFIX."userprefs WHERE preference='homepage' AND `value` IS NOT NULL AND `value`!=''";
        $data = $db->getArray($query);

        if ($data) {
            $stmt1 = $db->prepare('UPDATE '.CMS_DB_PREFIX."userprefs SET `value`='' WHERE preference='homepage' AND user_id=?");
            foreach ($data as $row) {
                $tmp = $row['value'];
                if (($p = strpos($tmp, 'menu.php?section')) !== false) {
                    //get admin menu data file, if not done before
                    if (!isset($cnt)) {
                        if (!isset($aname)) {
                            require_once CONFIG_FILE_LOCATION;
                            $aname = (!empty($config['admin_path'])) ? $config['admin_path'] : 'admin';
                        }
                        $cnt = file_get_contents(joinpath(CMS_ROOT_PATH, $aname, 'configs', 'method.adminmenu.php'));
                    }
                    $tmp = substr($tmp, $p + 17); // extract section name
                    if (!preg_match("/'name'[ =>]+?'$tmp'/", $cnt)) {
                        $db->execute($stmt1, [$row['user_id']]);
                    }
                } elseif (($p = strpos($tmp, 'moduleinterface.php?mact')) !== false) {
                    $matches = [];
                    if (preg_match('/=(.+?),/', $tmp, $matches, 0, $p + 24)) {
                        if ($matches[1]) {
                            //get all installed active modules, if not done before
                            if (!isset($modinfo)) {
                                $modinfo = $modops->GetInstalledModuleInfo();
                            }
                            $tmp = $matches[1];
                            if (!isset($modinfo[$tmp]) || empty($modinfo[$tmp]['active'])) {
                                $db->execute($stmt1, [$row['user_id']]);
                            }
                        }
                    } else {
                        $db->execute($stmt1, [$row['user_id']]); // just to be sure
                    }
                } elseif (endswith($tmp, '.php')) {
                    //check script CMS_ROOT_PATH/[admin]/WHATEVER.php is present
                    if (!isset($aname)) {
                        require_once CONFIG_FILE_LOCATION;
                        $aname = (!empty($config['admin_path'])) ? $config['admin_path'] : 'admin';
                    }
                    $fp = joinpath(CMS_ROOT_PATH, $aname, $tmp);
                    if (!is_file($fp)) {
                        $db->execute($stmt1, [$row['user_id']]);
                    }
                } else {
                    $db->execute($stmt1, [$row['user_id']]); // just to be sure
                }
            }
            $stmt1->close();
        }

        $this->system_setup(2, $destdir);

        // write history
        log_notice('System Upgraded', 'New version '.CMS_VERSION);
    }

    /**
     * @ignore
     * @throws Exception
     */
    private function do_install(installer_base $app)
    {
        $destdir = $app->get_destdir();
        if (!$destdir) {
            throw new Exception(lang('error_internal', 901));
        }
        $choices = $this->get_wizard()->get_data('sessionchoices');
        if (!$choices) {
            throw new Exception(lang('error_internal', 902));
        }
        $this->connect_to_cmsms($destdir);

        // site content
        if (!empty($choices['samplecontent'])) {
            $arr = installer_base::CONTENTXML;
            $fn = end($arr);
        } else {
            $fn = 'initial.xml';
        }

        $dir = $app->get_assetsdir();
        $xmlfile = $dir.DIRECTORY_SEPARATOR.$fn;
        if (is_file($xmlfile)) {
            if ($fn != 'initial.xml') {
                $this->message(lang('install_samplecontent'));
            }
            // these are irrelevant for 'initial.xml' but the importer API still wants them
            $dir = $app->get_rootdir();
            $arr = installer_base::UPLOADFILESDIR;
            $uploadsfolder = joinpath($dir, ...$arr);
            $arr = installer_base::CUSTOMFILESDIR;
            $workersfolder = joinpath($dir, ...$arr);

            try {
//                if (($fp = $app->get_phar())) {
//                    $fp = joinpath($fp, 'lib', 'iosite.functions.php'); // avoid stream-wrapper
//                }
//                else {
                // __DIR__ might be phar://abspath/to/pharfile/relpath/to/thisfolder
                $fp = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'iosite.functions.php';
//                }
                $space = require_once $fp;
                if ($space === false) { /* TODO handle error */
                } elseif ($space === 1) {
                    $space = '';
                }

                $funcname = ($space) ? $space.'\import_content' : 'import_content';
                if (($res = $funcname($xmlfile, $uploadsfolder, $workersfolder))) {
                    $this->error($res);
                } else {
                    // update pages hierarchy
                    $this->verbose(lang('install_updatehierarchy'));
                    Lone::get('ContentOperations')->SetAllHierarchyPositions();
                }
            } catch (Throwable $t) {
                if ($fn != 'initial.xml') {
                    $msg = 'Demonstration-content';
                } else {
                    $msg = 'Default-content';
                }
                $this->error($msg.' installation error: '.$t->getMessage());
            }
        } else {
            $this->error(lang('error_nocontent', $fn));
        }

        // modules
        $this->message(lang('install_modules'));
        $modops = Lone::get('ModuleOperations');
//abandoned        $coremodules = $app->get_config()['coremodules'];
//abandoned        $modops->RegisterSystemModules($coremodules);

        $db = Lone::get('Db');
        //(module_name,version,status,admin_only,active,allow_fe_lazyload,allow_admin_lazyload)
        $stmt1 = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.'modules
(module_name,version,admin_only,active) VALUES (?,?,?,1)');
        $stmt2 = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.'module_deps
(parent_module,child_module,minimum_version,create_date) VALUES (?,?,?,NOW())');

        $installmodules = $choices['havemodules'] ?? []; // cores maybe plus non-cores
        $modplace = $destdir.DIRECTORY_SEPARATOR.'modules';
        $len = strlen($modplace);
        $dirlist = cms_module_places();
        foreach ($dirlist as $dir) {
            // TODO use $modops->get_module_raw($modname);
            // TODO deal with module deps - some priority mechanism/tree?
            $contents = scandir($dir, SCANDIR_SORT_NONE);
            foreach ($contents as $modname) {
                if ($modname == '.' || $modname == '..' || $modname == 'index.html') {
                    continue;
                }
                if (in_array($modname, $installmodules)) {
                    $fp = $dir.DIRECTORY_SEPARATOR.$modname.DIRECTORY_SEPARATOR.$modname.'.module.php';
                    if (is_file($fp)) {
                        // move modules to normal place (we don't need|use their location to define status)
                        if (strncmp($dir, $modplace, $len) != 0) {
                            $fp = $dir.DIRECTORY_SEPARATOR.$modname;
                            $tp = $modplace.DIRECTORY_SEPARATOR.$modname;
                            if (!@rename($fp, $tp)) {
                                throw new Exception('Failed to migrate module '.$modname);
                            }
                            $fp = $tp.DIRECTORY_SEPARATOR.$modname.'.module.php';
                        }
                        require_once $fp;
                        $classname = '\\'.$modname;
                        $mod = new $classname();
                        if ($mod) {
                            try {
                                $this->mod_install($mod, $db, $stmt1, $stmt2);
                            } catch (Throwable $t) {
                                $msg = lang('error_modulebad', $modname);
                                $tmp = $t->GetMessage();
                                if (is_string($tmp) && $tmp) {
                                    $msg .= ': '.$tmp;
                                }
                                $this->error($msg);
                                continue;
                            }
                            $this->verbose(lang('install_module', $modname));
                        } else {
                            $this->error(lang('error_modulebad', $modname));
                        }
                    }
                } else {
                    rrmdir($dir.DIRECTORY_SEPARATOR.$modname);
                }
            }
        }
        $stmt1->close();
        $stmt2->close();
        foreach ($dirlist as $dir) {
            if ($dir != $modplace) {
                rrmdir($dir);
            }
        }

        $this->system_setup(1, $destdir);

        // write history
        log_notice('System Installed', 'Version '.CMS_VERSION);
    }

    /**
     * @ignore
     * @param installer_base $app
     * @throws Exception
     */
    private function do_freshen(installer_base $app)
    {
        $destdir = $app->get_destdir();
        if (!$destdir) {
            throw new Exception(lang('error_internal', 903));
        }
        $this->connect_to_cmsms($destdir);

        // replace modules
        $modplace = $destdir.DIRECTORY_SEPARATOR.'modules';
        $dirlist = cms_module_places();
        foreach ($dirlist as $dir) {
            if ($dir == $modplace) {
                continue;
            }
            $contents = scandir($dir, SCANDIR_SORT_NONE);
            foreach ($contents as $modname) {
                if ($modname == '.' || $modname == '..' || $modname == 'index.html') {
                    continue;
                }
                $fp = $dir.DIRECTORY_SEPARATOR.$modname;
                $tp = $modplace.DIRECTORY_SEPARATOR.$modname;
                if (is_dir($tp)) {
                    @rename($tp, $tp.'OLD');
                    if (!@rename($fp, $tp)) {
                        @rename($tp.'OLD', $tp);
                        throw new Exception('Failed to migrate module '.$modname);
                    }
                    rrmdir($tp.'OLD');
                 }
            }
        }
        foreach ($dirlist as $dir) {
            if ($dir != $modplace) {
                rrmdir($dir);
            }
        }

        $this->system_setup(3, $destdir);

        // write history
        log_notice('System Freshened', 'All core files renewed');
    }

    /**
     * @ignore
     * @param CmsModule $mod
     * @param Connection $db
     * @param mysqli statement object $stmt1 for updating main table
     * @param mysqli statement object $stmt2 for updating dependencies table
     * @throws Exception
     */
    private function mod_install($mod, $db, $stmt1, $stmt2)
    {
        $result = $mod->Install();
        if (!$result) {
            // a successful installation
            $modname = $mod->GetName();
            $admin = ($mod->IsAdminOnly()) ? 1 : 0;
//          $lazy_fe = ($admin || (method_exists($mod, 'LazyLoadFrontend') && $mod->LazyLoadFrontend())) ? 1 : 0;
//          $lazy_admin = (method_exists($mod, 'LazyLoadAdmin') && $mod->LazyLoadAdmin()) ? 1 : 0;
            $rs = $db->execute($stmt1, [
                $modname, $mod->GetVersion(), $admin//, $lazy_fe, $lazy_admin
            ]);

            $deps = $mod->GetDependencies(); // flat, fairly useless TODO implement deps-tree in cache, not db
            if ($deps) {
                foreach ($deps as $mname => $mversion) {
                    if ($mname && $mversion) {
                        $rs = $db->execute($stmt2, [$mname, $modname, $mversion]);
                    }
                }
            }
        } else {
            throw new Exception($result); //send back numeric code or error-string
        }
    }

    /**
     * @ignore
     * @param installer_base $app
     * @return array [0] = main site URL, [1] = admin-root URL
     */
    private function get_admin_url(installer_base $app) : array
    {
        $main_url = $app->get_root_url();
        if (endswith($main_url, '/')) {
            $admin_url = $main_url;
            $main_url = substr($main_url, -1);
        } else {
            $admin_url = $main_url.'/';
        }
        require_once CONFIG_FILE_LOCATION;
        $aname = (!empty($config['admin_path'])) ? $config['admin_path'] : 'admin';
        $admin_url .= $aname;
        return [$main_url, $admin_url];
    }
} // class
