<?php
/*
Class for consolidating stylesheets into a single file
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
namespace CMSMS;

use CMSMS\Crypto;
use CMSMS\Events;
use const TMP_CACHE_LOCATION;
use function cms_get_css;
use function cms_path_to_url;
use function file_put_contents;

//TODO an async Job to clear old consolidations ? how old ? c.f. TMP_CACHE_LOCATION cleaner

/**
 * A class for consolidating specified stylesheet files and/or strings
 * into a single file. Might be useful for modularizing admin css resources.
 * REMEMBER: relative urls/paths in css are almost never relocatable, so
 * this cannot be used for such.
 *
 * @since 2.99
 * @package CMS
 */
class StylesMerger
{
    private $_items = [];
    private $_item_priority = 2;

    /**
     * Get default priority for items to be merged
     *
     * @return int 1..5 The current default priority
     */
    public function get_style_priority() : int
    {
        return $this->_item_priority;
    }

    /**
     * Set default priority for items to be merged
     *
     * @param int $val The new default priority value (constrained to 1..5)
     */
    public function set_style_priority(int $val)
    {
        $this->_item_priority = max(1,min(5,$val));
    }

    /**
     * Revert to initial state
     */
    public function reset()
    {
        $this->_items = [];
        $this->_item_priority = 2;
    }

    /**
     * Record a string to be merged
     *
     * @param string $output   css string
     * @param int    $priority Optional priority 1..3 for the style. Default 0
     *  hence use current default
     * @param bool   $min Optional flag whether to force minimize this
     *  string in the merged file. Default false
     * @param bool   $force    Optional flag whether to force recreation of the merged file. Default false
     */
    public function queue_string(string $output, int $priority = 0, bool $min = false, bool $force = false)
    {
        $sig = Crypto::hash_string(__FILE__.$output);
        $output_file = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR."cms_$sig.css";
        if ($force || !is_file($output_file)) {
            file_put_contents($output_file, $output, LOCK_EX);
        }
        $this->queue_file($output_file, $priority, $min);
    }

    /**
     * Record a file to be merged if necessary
     *
     * @param string $filename Filesystem path of styles file
     * @param int    $priority Optional priority 1... for the file. Default 0
     *  hence use current default
     * @param bool   $min Optional flag whether to force minimize
     *  this file's content in the merged file. Default false
     * @return bool indicating success
     */
    public function queue_file(string $filename, int $priority = 0, bool $min = false)
    {
//TODO  $filename = $this->url_to_path($filename); // migrate URL-formatted filename to filepath
        if (!is_file($filename)) return false;

        $sig = Crypto::hash_string($filename);
        if (isset($this->_items[$sig])) return false;

        if ($priority < 1) {
            $priority = $this->_item_priority;
        } else {
            $priority = (int)$priority;
        }

        $this->_items[$sig] = [
            'file' => $filename,
            'mtime' => filemtime($filename),
            'priority' => $priority,
            'index' => count($this->_items),
            'min' => $min,
        ];
        return true;
    }

    /**
     * Find and record a style-file to be merged if necessary
     *
     * @param string $filename absolute or relative filepath of the wanted
     *  styles file, optionally including [.-]min before its .css extension
     *  If searching is needed, a discovered mMin-format version will be
     *  preferred over non-min.
     * @param int    $priority Optional priority 1..3 for the style. Default 0
     *  hence use current default
     * @param bool   $min  Optional flag whether to force-minimize the
     *  specified script in the merged file. Default false
     * @param mixed  $custompaths Optional string | string[] custom places to search before defaults
     * @return bool indicating success
     */
    public function queue_matchedfile(string $filename, int $priority = 0, bool $min = false, $custompaths = '') : bool
    {
//TODO  $filename = $this->url_to_path($filename); // migrate URL-formatted filename to filepath
        $cache_filename = cms_get_css($filename, false, $custompaths);
        if ($cache_filename) {
            return $this->queue_file($cache_filename, $priority, $min);
        }
        return false;
    }

    /**
     * Convert $content to minimal size
     * @internal
     * @param string $content
     * @return string
     */
    protected function minimize(string $content) : string
    {
        // not perfect, but very close ...
        $str = preg_replace(
            ['~^\s+~', '~\s+$~', '~\s+~', '~/\*[^!](\*(?!\/)|[^*])*\*/~'],
            [''      , ''      , ' '    , ''],
            $content);
        $str = strtr($str, ['\r' => '', '\n' => '']);
        return str_replace(
            ['  ', ': ', ', ', '{ ', '; ', '( ', '} ', ' :', ' {', '; }', ';}', ' }', ' )'],
            [' ' , ':' , ',' , '{' , ';' , '(' , '}' , ':' , '{' , '}'  , '}' , '}' , ')' ],
            $str);
    }

    /**
     * Construct a merged file from previously-queued files, if such file
     * doesn't exist or is out-of-date.
     * Hooks 'Core::PreProcessStyles' and 'Core::PostProcessStyles' are
     * run respectively before and after the content merge.
     *
     * @param string $output_path Optional Filesystem absolute path of folder to hold the merged file. Default '' (use TMP_CACHE_LOCATION)
     * @param bool   $force       Optional flag whether to force recreation of the merged file. Default false
     * @return mixed string basename of the merged-items file | null upon error
     */
    public function render_styles(string $output_path = '', bool $force = false)
    {
        if (!$this->_items) return; // nothing to do
        $base_path = ($output_path) ? rtrim($output_path, ' \/') : TMP_CACHE_LOCATION;
        if (!is_dir($base_path)) return; // nowhere to put it

        $tmp = Events::SendEvent('Core', 'PreProcessStyles', $this->_items);
        $items = ($tmp) ? $tmp : $this->_items;

        if ($items) {
            if (count($items) > 1) {
                // sort the items by priority, then index (to preserve order)
                uasort($items, function($a, $b) {
                    if ($a['priority'] != $b['priority']) return $a['priority'] <=> $b['priority'];
                    return $a['index'] <=> $b['index'];
                });
            }

            $t_sig = '';
            $t_mtime = -1;
            foreach($items as $sig => $rec) {
                $t_sig .= $sig;
                $t_mtime = max($rec['mtime'], $t_mtime);
            }
            $sig = Crypto::hash_string(__FILE__.$t_sig.$t_mtime);
            $cache_filename = "combined_$sig.css";
            $output_file = $base_path.DIRECTORY_SEPARATOR.$cache_filename;

            if ($force || !is_file($output_file) || filemtime($output_file) < $t_mtime) {
                $output = '';
                foreach ($items as $rec) {
                    $content = @file_get_contents($rec['file']);
                    if ($content) {
                        if ($rec['min']) {
                            $fn = basename($rec['file']);
                            if (($p = stripos($fn, 'min')) === false ||
                                ($p > 0 && $fn[$p-1] != '.' && $fn[$p-1] != '-') ||
                                ($p < strlen($fn) - 3 && $fn[$p+4] != '.' && $fn[$p+4] != '-')) {
                                $content = $this->minimize($content);
                            }
                        }
                        $output .= $content.PHP_EOL;
                    }
                }

                $tmp = Events::SendEvent('Core', 'PostProcessStyles', $output);
                if ($tmp) $output = $tmp;
                file_put_contents($output_file, $output, LOCK_EX);
            }
            return $cache_filename;
        }
    }

    /**
     * Construct a merged file from previously-queued files, if such file
     * doesn't exist or is out-of-date. Then generate the corresponding
     * page-content.
     * @see also StylesMerger::render_styles()
     *
     * @param string $output_path Optional file system absolute path of folder
     *  to hold the generated styles file. Default '' hence use TMP_CACHE_LOCATION
     * @param bool   $force       Optional flag whether to force re-creation
     *  of the merged file. Default false
     * @return string html like <link ...  /> | ''
     */
    public function page_content(string $output_path = '', bool $force = false) : string
    {
        $base_path = ($output_path) ? rtrim($output_path, ' \/') : TMP_CACHE_LOCATION;
        $cache_filename = $this->render_styles($base_path, $force);
        if ($cache_filename) {
            $output_file = $base_path.DIRECTORY_SEPARATOR.$cache_filename;
            $url = cms_path_to_url($output_file);
            return "<link rel=\"stylesheet\" type=\"text/css\" href=\"$url\" media=\"all\" />\n";
        }
        return '';
    }

    /**
     * Convert the supplied path, if it's an URL, to the corresponding filepath
     *
     * @param string
     * @return string
     */
    protected function url_to_path(string $path) : string
    {
        //TODO
        return $path;
    }
} // class
