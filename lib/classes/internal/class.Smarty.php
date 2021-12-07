<?php
/*
Class to tailor Smarty for CMSMS.
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

use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\internal\ModulePluginOperations;
use CMSMS\SingleItem;
use Exception;
use LogicException;
use Smarty_Internal_Template;
use SmartyBC as SmartyParent; //deprecated since 2.99
//use Smarty as SmartyParent; //in future
use const CMS_ADMIN_PATH;
use const CMS_ASSETS_PATH;
use const CMS_DEBUG;
use const CMS_ROOT_PATH;
use const TMP_CACHE_LOCATION;
use const TMP_TEMPLATES_C_LOCATION;
use function CMSMS\log_error;
use function cms_join_path;
use function get_userid;
use function is_sitedown;
use function startswith;

/*
if not using composer-installed smarty ...
 require_once cms_join_path(CMS_ROOT_PATH,'lib','smarty','SmartyBC.class.php'); //deprecated - support for Smarty2 API
OR
 require_once cms_join_path(CMS_ROOT_PATH,'lib','smarty','Smarty.class.php'); //when BC not needed
else
*/
require_once cms_join_path(CMS_ROOT_PATH,'lib','vendor','smarty','smarty','libs','SmartyBC.class.php'); //deprecated - support for Smarty2 API

/**
 * Class to tailor Smarty for CMSMS use.
 * This retains support for the Smarty2 API, but that's deprecated since 2.99
 *
 * @package CMS
 * @since 0.1
 */
class Smarty extends SmartyParent
{
    /**
     * @ignore
     */
    private static $_instance = null;

    /**
     * Constructor
     * Although this is a singleton, the constructor must be public to conform with class ancestors
     */
    public function __construct()
    {
        parent::__construct();

        $this->direct_access_security = true;
        $this->assignGlobal('app_name','CMSMS');

        if( CMS_DEBUG ) {
            $this->error_reporting = 'E_ALL';
        }
/* NO
some sort of corruption when retrieving ?
some things (smarty? CMSMS?) write to file anyway
some generated content is uncomfortably large for a shared public cache
smarty cache lifetime != global cache ttl, probably
        else {
            try {
                $this->registerCacheResource('globalcache', new cache_resource());
                $this->caching_type = 'globalcache';
            }
            catch( Throwable $t ) {
                // nothing here
            }
        }
*/
        // default template class
        $this->template_class = 'CMSMS\internal\template_wrapper';

        // default plugin handler
        $this->registerDefaultPluginHandler([$this,'defaultPluginHandler']);

        // dirs for compiled (i.e. non-cached), cached and config
        $this->setCompileDir(TMP_TEMPLATES_C_LOCATION)
             ->setCacheDir(TMP_CACHE_LOCATION)
             ->addConfigDir(CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'configs');

        // common resources are now in resource.* files in the main plugins folder
//        $this->registerResource('module_db_tpl',new module_db_template_resource())
//             ->registerResource('module_file_tpl',new module_file_template_resource())
//merged processing ->registerResource('cms_file',new file_template_resource())
//             ->registerResource('cms_template',new layout_template_resource())
//             ->registerResource('cms_stylesheet',new layout_stylesheet_resource()) //maybe some plugin would like to use this ??
//             ->registerResource('theme', new theme_resource())
//             ->setDefaultResourceType('cms_file') MEH... edge-case, only when explicit

        $this->addPluginsDir(CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'plugins') //plugin-assets prevail
             ->addPluginsDir(CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'plugins')
             ->addPluginsDir(CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'plugins') // deprecated

             ->setTemplateDir(CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'templates') //template-assets prevail
             ->addTemplateDir(CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'templates') // internal, never renamed
             ->addTemplateDir(CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'styles');

        if( SingleItem::App()->is_frontend_request() ) {
            // just for frontend actions
            // Check if we are at install page, don't register anything if so, as nothing below is needed.
            if( AppState::test(AppState::INSTALL) ) return;

            if( is_sitedown() ) {
                $this->setCaching(SmartyParent::CACHING_OFF); //actually, Smarty::
                $this->force_compile = true;
            }
            else {
                // Setup caching
                $v = (int)AppParams::get('smarty_cachelife',-1);
                switch( $v ) {
                    case -1:
                        $this->setCaching(SmartyParent::CACHING_LIFETIME_CURRENT);
                        break;
                    case 0:
                        $this->setCaching(SmartyParent::CACHING_OFF);
                        break;
                    default:
                        $this->setCaching(SmartyParent::CACHING_LIFETIME_SAVED);
                        $this->setCacheLifetime($v);
                }

                if( CMS_DEBUG ) {
                    $this->setCompileCheck(SmartyParent::COMPILECHECK_ON);
                    $this->setDebugging(true);
                }
                elseif( $v != 0 && AppParams::get('smarty_compilecheck',1) ) {
                    $this->setCompileCheck(SmartyParent::COMPILECHECK_CACHEMISS);
                }
                else {
                    $this->setCompileCheck(SmartyParent::COMPILECHECK_OFF);
                }
            }

            // Make site places available for general use
            $config = SingleItem::Config();
            $this->assignGlobal('_site_root_path',CMS_ROOT_PATH)
                 ->assignGlobal('_site_root_url',CMS_ROOT_URL)
                 ->assignGlobal('_site_themes_path',CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'themes')
                 ->assignGlobal('_site_themes_url',$config['assets_url'].'/themes')
                 ->assignGlobal('_site_uploads_path',$config['uploads_path'])
                 ->assignGlobal('_site_uploads_url',CMS_UPLOADS_URL);

            // Load resources
//            $this->registerResource('content',new content_resource())
            $this->setDefaultResourceType('content');
            $this->registerPlugin('compiler','content','CMSMS\internal\content_plugins::compile_fecontentblock',false) //CHECKME any point in Smarty caching for this
                 ->registerPlugin('function','content_image','CMSMS\internal\content_plugins::fetch_imageblock',true)
                 ->registerPlugin('function','content_module','CMSMS\internal\content_plugins::fetch_moduleblock',true)
                 ->registerPlugin('function','content_text','CMSMS\internal\content_plugins::fetch_textblock',true)
                 ->registerPlugin('function','fetch_pagedata','CMSMS\internal\content_plugins::fetch_pagedata',true) // redundant, deprecated since 2.99
                 ->registerPlugin('function','process_pagedata','CMSMS\internal\content_plugins::process_pagedata',true); // redundant, deprecated since 2.99

            // Autoload filters
            $this->autoloadFilters();

            if( !$config['permissive_smarty'] ) {
                // Apply our security object
                $this->enableSecurity('CMSMS\internal\SmartySecurityPolicy');
            }
        }
        elseif( AppState::test(AppState::ADMIN_PAGE) ) {
/*/DEBUG admin caching
            $v = (int)AppParams::get('smarty_cachelife',-1);
            switch( $v ) {
                case -1:
                    $v = SmartyParent::CACHING_LIFETIME_CURRENT;
                    break;
                case 0:
                    $v = SmartyParent::CACHING_OFF;
                    break;
                default:
                    $this->setCacheLifetime($v);
                    $v = SmartyParent::CACHING_LIFETIME_SAVED;
            }
*/
            // our configs folder could be added (i.e. to smarty's own config dir - but that doesn't exist in 3.1.33 at least)
            $this->setConfigDir(CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'configs')
                 ->addPluginsDir(CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'plugins')
                 ->addTemplateDir(CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'templates')
//TODO where appropriate (currently in each theme-method which generates content)
//               ->addTemplateDir(CMS_THEMES_PATH.DIRECTORY_SEPARATOR.TODOcurrenttheme.DIRECTORY_SEPARATOR.'templates')
//               ->setDefaultResourceType('TODO')
                 ->setCaching(SmartyParent::CACHING_OFF); //($v) TODO enable admin caching with in-context disabling
//c.f. frontend   ->enableSecurity('CMSMS\internal\SmartySecurityPolicy');

            // force re-compile after template change
            //Events::AddDynamicHandler('Core','EditTemplatePost',$TODOcallback);
            //Events::AddDynamicHandler('Core','AddTemplatePost',$TODOcallback);
        }
    }

    /**
     * Load filters from smarty-plugins folders
     */
    private function autoloadFilters()
    {
        $pre = [];
        $post = [];
        $output = [];
        // Filters will be applied in same order as registered
        // TODO manage processing order here, if it matters
        foreach( $this->getPluginsDir() as $dir ) {
            if( !is_dir($dir) ) continue;

            $files = glob($dir.'*.php');
            if( !$files ) continue;

            foreach( $files as $file ) {
                $parts = explode('.',basename($file));
                if( count($parts) != 3 ) continue;

                switch( $parts[0] ) {
                case 'output':
                    $output[] = $parts[1];
                    break;

                case 'prefilter':
                    $pre[] = $parts[1];
                    break;

                case 'postfilter':
                    $post[] = $parts[1];
                    break;
                }
            }
        }

        $this->autoload_filters = ['pre'=>$pre,'post'=>$post,'output'=>$output];
    }

    public function registerClass($obj,$name)
    {
        if( $this->security_policy ) $this->security_policy->static_classes[] = $obj;
        parent::registerClass($obj,$name);
    }

    /**
     * Our default plugin-handler
     *
     * @param string  $name      name of the tag being sought
     * @param string  $type      tag type (e.g. Smarty::PLUGIN_FUNCTION, Smarty::PLUGIN_BLOCK,
     *    Smarty::PLUGIN_COMPILER, Smarty::PLUGIN_MODIFIER, Smarty::PLUGIN_MODIFIERCOMPILER)
     * @param Smarty_Internal_Template   $template template object UNUSED
     * @param string  &$callback returned callable
     * @param string  &$script   optional returned script filepath if function is external
     * @param bool    &$cachable true by default, set it false here if relevant
     * @return bool indicating success
     */
    public function defaultPluginHandler($name,$type,$template,&$callback,&$script,&$cachable)
    {
/* NOTE plugin-dirs scan is done within smarty, this never finds an actual plugin
   so we cannot use this to force $cachable to false
        $base = $type.'.'.$name.'.php';
        $basef = $type.'_'.$name;

        // walk plugin dirs to try to find a match
        foreach( $this->getPluginsDir() as $dir ) {
            $file = $dir.$base;
            if( !is_file($file) ) continue;

            require_once $file;

            foreach( [
            'smarty_',
            'smarty_cms_', // deprecated, NOT compatible with smarty 3.1.32+
            'smarty_nocache_', // ditto
            ] as $i => $pref ) {
                $func = $pref.$basef;
                if( !function_exists($func) ) continue;
                if( $i > 0 ) {
                    //TODO generate smarty-compatible runtime func whose name-prefix is just 'smarty_',
                    $func2 = 'smarty_'.$basef;
                    function $func2(...$args) { return $func($args); }
                    $func = $func2;
                }

                $callback = $func;
                $script = $file;
//TODO CHECKME plugins never cachable? smarty prevents this?
                $cachable = false;
                return true;
            }
        }
*/
        $cachable = false;
        if( $type != 'function' ) {
            return false;
        }

        //Deprecated pre-2.99 approach - non-system plugins were never cachable
        //In future, allow caching and expect users to override that in templates where needed
        //Otherwise, module-plugin cachability is opaque to page-builders
        if( SingleItem::App()->is_frontend_request() ) {
            // check if it's a module-plugin (tabled or not)
            $row = ModulePluginOperations::load_plugin($name,$type);
            if( $row && is_callable($row['callable']) ) {
                $callback = $row['callable'];
//                if (0) {
                    $val = AppParams::get('smarty_cachemodules',0);
                    if ($val != 2) {
                        $cachable = (bool)$val;
                    } else {
                        $cachable = !empty($row['cachable']);
                    }
//                }
                return true;
            }

            // check if it's a user-plugin
            $callback = SingleItem::UserTagOperations()->CreateTagFunction($name);
            if( $callback ) {
//                if (0) {
                    $val = AppParams::get('smarty_cacheusertags',false);
                    $cachable = (bool)$val;
//                }
                return true;
            }
        }
        return false;
    }

    /**
     * Report whether a smarty plugin (regular, not module- or user-)
     * having the specified name exists.
     * @since 2.99
     *
     * @param string the plugin identifier
     * @param string Optional plugin-type, default 'function' TODO support '*'/'any'
     * @return bool
     */
    public function is_plugin(string $name,string $type = 'function') : bool
    {
        if( isset($this->registered_plugins[$type][$name]) ) {
            return true;
        }
        // walk plugin dirs to try to find a match
        $base = $type.'.'.$name.'.php';
        $basef = $type.'_'.$name;
        foreach( $this->getPluginsDir() as $dir ) {
            $file = $dir.$base;
            if( !is_file($file) ) continue;

            require_once $file;

            foreach( [
            'smarty_',
            'smarty_cms_', // deprecated, NOT compatible with smarty 3.1.32+
            'smarty_nocache_', // ditto
            ] as $i => $pref ) {
                if( function_exists($pref.$basef) ) {
                    if( $i == 0 ) return true;
                    //TODO compatibilty stuff
                }
            }
        }
        return false;
    }

    /**
     * Report whether a smarty plugin with the specified name has been registered.
     *
     * @param string the plugin name
     * @return bool
     */
    public function is_registered(string $name) : bool
    {
        return isset($this->registered_plugins['function'][$name]);
    }

    /**
     * Create a template object
     *
     * @param  string  $template   the resource handle of the template
     * @param  mixed   $cache_id   optional cache id to be used with this template
     * @param  mixed   $compile_id optional compile id to be used with this template
     * @param  object  $parent     optional next-higher level of Smarty variables
     * @param  bool    $do_clone   optional flag whether to clone the Smarty object
     * @return Smarty_Internal_Template template object
     * @throws LogicException
     */
    public function createTemplate($template,$cache_id = null,$compile_id = null,$parent = null,$do_clone = true)
    {
        foreach( ['eval:','string:','cms_file:','extends:'] as $type ) {
            if( startswith($template,$type) ) {
                return parent::createTemplate($template,$cache_id,$compile_id,$parent,$do_clone);
            }
        }
        if( strpos($template,'*') === false && strpos($template,'/') === false ) {
            return parent::createTemplate($template,$cache_id,$compile_id,$parent,$do_clone);
        }
        throw new LogicException($template.' is not a valid Smarty resource in CMSMS');
    }

    /**
     * Return content for an error page
     *
     * @author Stikki
     * @param Exception object $e
     * @param bool $show_trace Optional flag whether to include a backtrace in the displayed report. Default true
     * @return string
     */
    public function errorConsole(Exception $e,bool $show_trace = true) : string
    {
        $this->force_compile = true;

        # do not show smarty debug console popup to users not logged in
        //$this->debugging = get_userid(false);
        $this->assign('e_line',$e->getLine())
             ->assign('e_file',$e->getFile())
             ->assign('e_message',$e->getMessage())
             ->assign('loggedin',get_userid(false));
        if( $show_trace ) {
            $this->assign('e_trace',\htmlentities($e->getTraceAsString()));
        }
        else {
            $this->assign('e_trace',null);
        }

        // put mention into the admin log
        log_error($e->getMessage,'Smarty');

        $output = $this->fetch('cmsms-error-console.tpl');

        $this->force_compile = false;
        $this->debugging = false;

        return $output;
    }
} // class

//when Smarty2 (BC) no longer needed
//class_alias('CMSMS\internal\CmsSmarty', 'CMSMS\internal\Smarty', false);
