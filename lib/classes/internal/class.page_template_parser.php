<?php
/*
Smarty template sub-class
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CmsEditContentException;
use Smarty_Internal_Template;
use SmartyException;
use function startswith;

/**
 * Currently used only by CMSContentManager::Content class, for
 * backend-page or frontend-page-preview
 */
class page_template_parser extends Smarty_Internal_Template
{
    // static properties here >> StaticProperties class ?
    /**
     * @ignore
     * @var int
     */
    protected static $_priority = 100;

    /**
     * @ignore
     * @var array, each member like 'blockname' => [blockparms]
     * intra-request block-parameters cache
     */
    protected static $_contentBlocks = [];

    /* *
     * @ignore
     * @var strings array
     */
//    private static $_allowed_static_plugins = ['global_content'];

    /**
     * Class constructor.
     * Used only by CMSContentManager::content class i.e. for backend-page or
     * frontend-page-preview. No smarty caching.
     * @param string $template_resource template identifier
     * @param mixed $smarty
     * @param mixed $_parent optional, default null
     * @param mixed $_cache_id  optional, default null
     * @param mixed $_compile_id string|null optional, default null UNUSED
     * @param boolean $_caching  optional, default false
     * @param int $_cache_lifetime  optional, default 0
     */
    public function __construct(
        string $template_resource,
        $smarty,
        $_parent = null,
        $_cache_id = null,
        $_compile_id = null,
        bool $_caching = false,
        int $_cache_lifetime = 0
    ) {
        $_compile_id = 'cmsms_parser_'.microtime();
        parent::__construct($template_resource, $smarty, $_parent, $_cache_id, $_compile_id, $_caching, $_cache_lifetime);

        $this->registerDefaultPluginHandler([$this,'defaultPluginHandler']);
        $this->merge_compiled_includes = true;

        try {
            //the first of these is a variation of the plugin registered in CMSMS\internal\Smarty constructor for f/e pages
            //each just sets parameters in local cache, no use in smarty cache too (even if were used for frontend?)
            $this->registerPlugin('compiler', 'content', [$this,'compile_contentblock'], false)
                 ->registerPlugin('compiler', 'content_image', [$this,'compile_imageblock'], false)
                 ->registerPlugin('compiler', 'content_module', [$this,'compile_moduleblock'], false)
                 ->registerPlugin('compiler', 'content_text', [$this,'compile_contenttext'], false);
        } catch (SmartyException $e) {
            // ignore these... throws an error in Smarty 3.1.16 if plugin is
            // already registered because plugin registration is global.
        }
    }

    /**
     * Callable for the default plugin-handler
     * @param array $params
     * @param mixed $template
     * @return string (empty)
     */
    public static function _dflt_plugin(array $params, $template) : string
    {
        return '';
    }

    /**
     * Setup a default smarty-compiler-plugin handler for page-templates
     * @param string $name UNUSED
     * @param string $type
     * @param mixed $template UNUSED
     * @param callable $callback
     * @param type $script UNUSED
     * @param boolean $cachable
     * @return boolean
     */
    public function defaultPluginHandler(string $name, string $type, $template, &$callback, &$script, &$cachable) : bool
    {
        if ($type == 'compiler') {
            $callback = [self::class,'_dflt_plugin'];
            $cachable = false;
            return true;
        }

        return false;
    }

    /**
     * Default fetcher - should never be called. None of any supplied params is used.
     * @param mixed $template
     * @param mixed $cache_id
     * @param mixed $compile_id
     * @param mixed $parent
     * @param bool  $display
     * @param bool  $merge_tpl_vars
     * @param bool  $no_output_filter
     */
    public function fetch(
        $template = null,
        $cache_id = null,
        $compile_id = null,
        $parent = null,
        $display = false,
        $merge_tpl_vars = true,
        $no_output_filter = false
    ) {
        die(__FILE__.'::'.__LINE__.' CRITICAL: This method should never be called');
    }

    /**
     * Set object properties back to respective defaults
     */
    public static function reset()
    {
        self::$_priority = 100;
        self::$_contentBlocks = [];
    }

    /**
     * Get recorded content blocks
     * @return strings array
     */
    public static function get_content_blocks() : array
    {
        return self::$_contentBlocks;
    }

    /* * EXPORTED TO class.content_plugins
     * Generate PHP code to compile a content block tag.
     * This is the registered handler for frontend {content} tags
     *
     * @param array $params
     * @param Smarty_Internal_SmartyTemplateCompiler $template UNUSED
     * @return string
     */
/*    public static function compile_fecontentblock(array $params, $template) : string
    {
        $tmp = [];
        foreach ($params as $k => $v) {
            //CHECKME if $v is a string, quote it?
            $tmp[] = "'$k'=>".$v;
        }
        $ptext = implode(',', $tmp);
        return '<?php \\CMSMS\\internal\\content_plugins::fetch_contentblock(['.$ptext.'],$_smarty_tpl); ?>';
    }
*/
    /**
     * Process a {content} tag.
     * Adds parameters array to intra-request local cache. TODO cache in smarty instead?
     *
     * @param array $params
     * @param mixed $template UNUSED
     */
    public static function compile_contentblock(array $params, $template)
    {
        $rec = [
            'adminonly'=>0,
            'cssname'=>'',
            'default'=>'',
            'id'=>'',
            'label'=>'',
            'maxlength'=>'255',
            'name'=>'',
            'noedit'=>false,
            'oneline'=>false, //CHECKME was string 'false'
            'placeholder'=>'',
            'priority'=>'',
            'required'=>0,
            'size'=>'50',
            'tab'=>'',
            'type'=>'text',
            'usewysiwyg'=>true, //CHECKME was string 'true'
        ];
        foreach ($params as $key => $value) {
            $value = trim($value, '"\'');
            if (startswith($key, 'data-')) {
                $rec[$key] = $value;
            } else {
                if ($key == 'type') {
                    continue;
                }
                if ($key == 'block') {
                    $key = 'name';
                }
                if ($key == 'wysiwyg') {
                    $key = 'usewysiwyg';
                }
                if (isset($rec[$key])) {
                    $rec[$key] = $value;
                }
            }
        }

        if (!$rec['name']) {
            $rec['name'] = $rec['id'] = 'content_en';
        }
        if (strpos($rec['name'], ' ') !== false) {
            if (!$rec['label']) {
                $rec['label'] = $rec['name'];
            }
            $rec['name'] = str_replace(' ', '_', $rec['name']);
        }
        if (!$rec['id']) {
            $rec['id'] = str_replace(' ', '_', $rec['name']);
        }
/*
        // check for duplicate
        if( isset(self::$_contentBlocks[$rec['name']]) ) throw new CmsEditContentException('Duplicate content block: '.$rec['name']);
*/
        // set priority
        if (empty($rec['priority'])) {
            $rec['priority'] = self::$_priority++;
        }

        self::$_contentBlocks[$rec['name']] = $rec;
    }

    /**
     * Process a {content_image} tag
     * Adds parameters array to intra-request cache. TODO cache in smarty instead?
     *
     * @param array $params
     * @param mixed $template UNUSED
     * @throws CmsEditContentException
     */
    public static function compile_imageblock(array $params, $template)
    {
        if (!isset($params['block']) || empty($params['block'])) {
            throw new CmsEditContentException('{content_image} tag requires block parameter');
        }

        $rec = [
            'type'=>'image',
            'name'=>'',
            'label'=>'',
            'upload'=>true,
            'dir'=>'',
            'default'=>'',
            'tab'=>'',
            'priority'=>'',
            'exclude'=>'',
            'sort'=>0,
            'profile'=>'',
        ];
        foreach ($params as $key => $value) {
            if ($key == 'type') {
                continue;
            }
            if ($key == 'block') {
                $key = 'name';
            }
            if (isset($rec[$key])) {
                $rec[$key] = trim($value, "'\"");
            }
        }

        if (!$rec['name']) {
            $n = count(self::$_contentBlocks)+1;
            $rec['name'] = 'image_'.$n;
        }
        if (strpos($rec['name'], ' ') !== false) {
            if (!$rec['label']) {
                $rec['label'] = $rec['name'];
            }
            $rec['name'] = str_replace(' ', '_', $rec['name']);
        }
        if (empty($rec['id'])) {
            $rec['id'] = str_replace(' ', '_', $rec['name']);
        }

        // set priority
        if (empty($rec['priority'])) {
            $rec['priority'] = self::$_priority++;
        }

        self::$_contentBlocks[$rec['name']] = $rec;
    }

    /**
     * Process {content_module} tag
     * Adds parameters array to intra-request cache. TODO cache in smarty instead?
     *
     * @param array $params
     * @param mixed $template UNUSED
     * @throws CmsEditContentException
     */
    public static function compile_moduleblock(array $params, $template)
    {
        if (!isset($params['block']) || empty($params['block'])) {
            throw new CmsEditContentException('{content_module} tag requires block parameter');
        }

        $rec = [
            'type'=>'module',
            'id'=>'',
            'name'=>'',
            'module'=>'',
            'label'=>'',
            'blocktype'=>'',
            'tab'=>'',
            'priority'=>'',
        ];
        $parms = [];
        foreach ($params as $key => $value) {
            if ($key == 'block') {
                $key = 'name';
            }

            $value = trim(trim($value, '"\''));
            if (isset($rec[$key])) {
                $rec[$key] = $value;
            } else {
                $parms[$key] = $value;
            }
        }

        if (!$rec['name']) {
            $n = count(self::$_contentBlocks)+1;
            $rec['id'] = $rec['name'] = 'module_'.$n;
        }
        if (strpos($rec['name'], ' ') !== false) {
            if (!$rec['label']) {
                $rec['label'] = $rec['name'];
            }
            $rec['name'] = str_replace(' ', '_', $rec['name']);
        }
        if (!$rec['id']) {
            $rec['id'] = str_replace(' ', '_', $rec['name']);
        }
        $rec['params'] = $parms;
        if ($rec['module'] == '') {
            throw new CmsEditContentException('Missing module param for content_module tag');
        }

        // set priority
        if (empty($rec['priority'])) {
            $rec['priority'] = self::$_priority++;
        }

        self::$_contentBlocks[$rec['name']] = $rec;
    }

    /**
     * Process {content_text} tag
     * Adds parameters array to intra-request cache.
     *
     * @param array $params
     * @param mixed $template UNUSED
     */
    public static function compile_contenttext(array $params, $template)
    {
        //if( !isset($params['block']) || empty($params['block']) ) throw new \CmsEditContentException('{content_text} smarty block tag requires block parameter');

        $rec = [
            'type'=>'static',
            'name'=>'',
            'label'=>'',
            'upload'=>true,
            'dir'=>'',
            'default'=>'',
            'tab'=>'',
            'priority'=>'',
            'exclude'=>'',
            'sort'=>0,
            'profile'=>'',
            'text'=>'',
        ];
        foreach ($params as $key => $value) {
            if ($key == 'type') {
                continue;
            }
            if ($key == 'block') {
                $key = 'name';
            }
            if (isset($rec[$key])) {
                $rec[$key] = trim($value, "'\"");
            }
        }

        if (!$rec['name'] || !$rec['text']) {
            return; // ignore it
        }

        if (!$rec['name']) {
            $n = count(self::$_contentBlocks)+1;
            $rec['name'] = 'static_'.$n;
        }
        if (strpos($rec['name'], ' ') !== false) {
            if (!$rec['label']) {
                $rec['label'] = $rec['name'];
            }
            $rec['name'] = str_replace(' ', '_', $rec['name']);
        }
        if (empty($rec['id'])) {
            $rec['id'] = str_replace(' ', '_', $rec['name']);
        }

        // set priority
        if (empty($rec['priority'])) {
            $rec['priority'] = self::$_priority++;
        }

        $rec['static_content'] = trim(strip_tags($rec['text']));

        self::$_contentBlocks[$rec['name']] = $rec;
    }
}
