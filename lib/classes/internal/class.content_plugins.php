<?php
/*
Methods for fetching content blocks
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp, Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 3 of that license, or (at your option)
any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\internal;

use CMSMS\AppParams;
use CMSMS\CapabilityType;
use CMSMS\Error403Exception;
use CMSMS\Error404Exception;
use CMSMS\internal\template_wrapper;
use CMSMS\RequestParameters;
use CMSMS\Lone;
use CMSMS\Utils;
use const CMS_UPLOADS_URL;
use function cms_join_path;
use function cms_to_bool;
use function cmsms;
use function startswith;

/**
 * Helper class to deal with fetching content blocks.
 *
 * @author   Robert Campbell
 * @since    1.11
 * @ignore
 * @internal
 * @package  CMS
 */
final class content_plugins
{
    // static properties here >> Lone property|ies ?
    private static $_primary_content;    // generated by get_default_content_block_content()

    private function __construct() {}

    /**
     * @ignore
     * @param strihg $content the content
     * @param array $params
     * @param mixed $smarty Smarty_Internal_SmartyTemplateCompiler or CMSMS\internal\template_wrapper
     */
    private static function echo_content(string $content, array &$params, $smarty)
    {
        if( !empty($params['assign']) ) {
            $smarty->assign(trim($params['assign']), $content);
            echo '';
        }
        else {
            echo $content;
        }
    }

    /**
     * Handler for each {content} tag.
     * After determining which content block to render, $smarty->fetch()
     * generates a 'content:' resource to retrieve the value of the block,
     * which is then assigned to a smarty variable, or echo()'d.
     *
     * @since 1.11
     * @param array $params
     * @param Smarty_Internal_SmartyTemplateCompiler $smarty
     * @throws Error403Exception
     */
    public static function fetch_contentblock(array $params, $smarty)
    {
        $result = '';
        $contentobj = cmsms()->get_content_object();
        if( is_object($contentobj) ) {
            if( !$contentobj->IsPermitted() ) throw new Error403Exception();
            $block = $params['block'] ?? 'content_en';
            // if content_en
            //    get primary content
            // otherwise other block
            if( $block == 'content_en' ) {
                // was the data prefetched ?
                $result = self::get_default_content_block_content($contentobj->Id(), $smarty);
            }
            if( !$result ) {
/*
                if( isset($_SESSION[CMS_PREVIEW]) && $contentobj->Id() == CMS_PREVIEW_PAGEID ) {
                    // note: content precompile/postcompile events will not be triggered in preview.
//                  $val = $contentobj->Show($block);
//                  $result = $smarty->fetch('eval:'.$val);
                    $result = $smarty->fetch('content:'.strtr($block, ' ', '_'), '|'.$block, $contentobj->Id().$block);
                }
                else {
*/
                    $result = $smarty->fetch('content:'.strtr($block, ' ', '_'), '|'.$block, $contentobj->Id().$block);
//                }
            }
        }
        self::echo_content($result, $params, $smarty);
    }

    /**
     * Handler for each {fetch_pagedata} tag.
     * Fetch frontend page content and assign it to a smarty variable,
     * or echo() it.
     * @param array $params
     * @param template_wrapper $template
     * @return mixed string or null
     */
    public static function fetch_pagedata(array $params, $template)
    {
        $contentobj = cmsms()->get_content_object();
        if( !is_object($contentobj) || $contentobj->Id() <= 0 ) {
            self::echo_content('', $params, $template);
            return '';
        }

        $result = $template->fetch('content:pagedata', '', $contentobj->Id());
        if( !empty($params['assign']) ) {
            $template->assign(trim($params['assign']), $result);
            return '';
        }
        return $result;
    }

    /**
     * Handler for each {process_pagedata} tag.
     * Fetch/process frontend page content so that smarty stuff can be
     * handled, but don't display the content.
     * The tag has been a mechanism for out-of-order page content processing.
     * @since 3.0
     * @deprecated since 3.0
     * @param array $params
     * @param template_wrapper $template
     * @return empty string
     */
    public static function process_pagedata(array $params, $template)
    {
        $contentobj = cmsms()->get_content_object();
        if( is_object($contentobj) && $contentobj->Id() > 0 ) {
            $template->fetch('content:pagedata', '', $contentobj->Id());
        }
        return '';
    }

    /**
     * Handler for each {content_image} tag.
     * @param array $params
     * @param mixed $template
     * @return mixed string or null
     */
    public static function fetch_imageblock(array $params, $template)
    {
        $contentobj = cmsms()->get_content_object();
        if( !is_object($contentobj) || $contentobj->Id() <= 0 ) {
            self::echo_content('', $params, $template);
            return '';
        }

        $config = Lone::get('Config');
        if( !empty($params['dir']) ) {
            $adddir = $params['dir'];
        }
        else {
            $adddir = AppParams::get('contentimage_path');
        }
        $dir = cms_join_path($config['uploads_path'], $adddir);
        $basename = basename($config['uploads_path']);

        $result = '';
        if( isset($params['block']) ) {
            $result = $template->fetch('content:'.strtr($params['block'], ' ', '_'), '|'.$params['block'], $contentobj->Id().$params['block']);
        }
        $img = $result;

        $out = null;
        if( startswith(realpath($dir), realpath($basename)) ) {
            if( ($img == -1 || empty($img)) && isset($params['default']) && $params['default'] ) {
                $img = $params['default'];
            }
            if( $img && $img != -1 ) {
                // create the absolute url
                $orig_val = $img;
                $img = CMS_UPLOADS_URL.'/';
                if( $adddir ) $img .= $adddir.'/';
                $img .= $orig_val;

                $urlonly = ( !empty($params['urlonly']) ) ? cms_to_bool($params['urlonly']) : false;
                if( $urlonly ) {
                    $out = $img;
                }
                else {
                    $ignored = [
                     'block',
                     'type',
                     'name',
                     'label',
                     'upload',
                     'dir',
                     'default',
                     'tab',
                     'priority',
                     'exclude',
                     'sort',
                     'profile',
                     'urlonly',
                     'assign',
                    ];
                    $tagparms = [];
                    foreach( $params as $key => $val ) {
                        $key = trim($key);
                        if( !$key ) continue;
                        $val = trim($val);
                        if( !$val ) continue;
                        if( in_array($key, $ignored) ) continue;
                        $tagparms[$key] = $val;
                    }

                    $out = "<img src=\"$img\"";
                    foreach( $tagparms as $key => $val ) {
                        $out .= " $key=\"$val\"";
                    }
                    $out .= ' />';
                }
            }
        }
        if( !empty($params['assign']) ){
            $template->assign(trim($params['assign']), $out);
            return '';
        }
        return $out;
    }

    /**
     * Handler for each {content_module} tag.
     * @param array $params
     * @param mixed $template
     * @return mixed string or null
     */
    public static function fetch_moduleblock(array $params, $template)
    {
        if( !isset($params['block']) ) return '';

        $block = $params['block'];
        $result = '';

        $content_obj = cmsms()->get_content_object();
        if( is_object($content_obj) ) {
            $result = $content_obj->GetPropertyValue($block);
            if( $result == -1 ) $result = '';
            $modname = isset($params['module']) ? trim($params['module']) : null;
            if( $modname ) {
                $mod = Utils::get_module($modname);
                if( is_object($mod) ) $result = $mod->RenderContentBlockField($block, $result, $params, $content_obj);
            }
        }

        if( !empty($params['assign']) ) {
            $template->assign(trim($params['assign']), $result);
            return '';
        }
        return $result;
    }

    /**
     * Handler for each {content_text} tag. Does nothing.
     * @param array $params
     * @param mixed $template
     * @return null
     */
    public static function fetch_textblock(array $params, $template)
    {
        return;
    }

    /**
     * @param mixed $page_id int or ''|null
     * @param mixed $smarty CMSMS\internal\Smarty or CMSMS\internal\template_wrapper
     * @return mixed string or null
     * @throws Error404Exception
     */
    public static function get_default_content_block_content($page_id, &$smarty)
    {
        if( self::$_primary_content ) return self::$_primary_content;

        $result = $do_mact = $modname = $id = $action = $inline = null;
        $params = RequestParameters::get_action_params();
        if( $params ) {
            $modname = $params['module'] ?? '';
            $id = $params['id'] ?? '';
            if( $modname && $id == 'cntnt01' && empty($params['inline']) ) $do_mact = true;
        }

        if( $do_mact ) {
            $mod = Lone::get('ModuleOperations')->get_module_instance($modname);
            if( !$mod ) {
                // module not found... couldn't even autoload it.
                @trigger_error('Attempt to access module '.$modname.' which could not be found (is it properly installed and configured?');
                throw new Error404Exception('Attempt to access module '.$modname.' which could not be found (is it properly installed and configured?');
            }
            if( !($mod->HasCapability(CapabilityType::PLUGIN_MODULE) || $mod->IsPluginModule()) ) {
                @trigger_error('Attempt to access module '.$modname.' on a frontend request, which is not a plugin module');
                throw new Error404Exception('Attempt to access module '.$modname.' which could not be found (is it properly installed and configured?');
            }

            $action = $params['action'];
            $params = RequestParameters::get_identified_params($id);
            $params['action'] = $action; //deprecated since 3.0
            $result = $mod->DoActionBase($action, $id, $params, $page_id, $smarty);
        }
        else {
            $result = $smarty->fetch('content:content_en', '|content_en', $page_id.'content_en');
        }
        self::$_primary_content = $result;
        return $result;
    }

    /**
     * Compiler for frontend-page {content} tags.
     * Generates PHP code to compile a content block tag.
     *
     * @param array $params
     * @param Smarty_Internal_SmartyTemplateCompiler $template UNUSED
     * @return string
     */
    public static function compile_fecontentblock(array $params, $template) : string
    {
        $tmp = [];
        foreach( $params as $k => $v ) {
            if( is_numeric($v) ) {
                $v += 0;
            }
            elseif( is_string($v) ) {
                $v = "'".str_replace("'", "\'", $v)."'";
            }
            $tmp[] = "'$k'=>".$v;
        }
        // the following uses long-array-syntax, replicating Smarty
        if( $tmp ) {
            $ptext = implode(',', $tmp);
            return '<?php '.__CLASS__.'::fetch_contentblock(array('.$ptext.'), $_smarty_tpl); ?>';
        }
        return '<?php '.__CLASS__.'::fetch_contentblock(array(), $_smarty_tpl); ?>';
    }
} // class
