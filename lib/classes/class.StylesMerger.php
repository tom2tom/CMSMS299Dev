<?php
/*
Class for consolidating stylesheets into a single file
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
namespace CMSMS;

use CMSMS\Crypto;
use CMSMS\Events;
use const CMS_ROOT_PATH;
use const CMS_ROOT_URL;
use const TMP_CACHE_LOCATION;
use function cms_get_css;
use function cms_path_to_url;
use function file_put_contents;
use function startswith;

//TODO async Job to clear old consolidations ? how old ? c.f. TMP_CACHE_LOCATION cleaner

/**
 * A class for consolidating specified stylesheet files and/or strings
 * into a single file. Might be useful for modularizing admin css resources.
 *
 * @since 3.0
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
    public function get_style_priority(): int
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
        $this->_item_priority = max(1, min(5, $val));
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
     * Record a string to be merged.
     * NOTE any relative URL in the string will be adjusted only if a valid
     * $relative_to is provided
     *
     * @param string $output   css string
     * @param int    $priority Optional priority 1..3 for the style. Default 0
     *  hence use current default
     * @param bool   $min Optional flag whether to force minimize this
     *  string in the merged file. Default false
     * @param bool   $force    Optional flag whether to force recreation of the merged file. Default false
     * @param string $relative_to Optional intra-site directory path, or dummy or actual
     * filepath including the appropriate directory, for use when interpeting any
     * relative url in $output. Default ''
     * @return bool indicating success
     */
    public function queue_string(string $output, int $priority = 0, bool $min = false, bool $force = false, string $relative_to = '')
    {
        $sig = Crypto::hash_string(__FILE__.$output);
        $temp_file = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR."cms_$sig.css";
        if ($force || !is_file($temp_file)) {
            if (!@file_put_contents($temp_file, $output, LOCK_EX)) {
                return false;
            }
        }
        if ($this->queue_file($temp_file, $priority, $min)) {
            if ($relative_to) {
                if (startswith($relative_to, CMS_ROOT_PATH)) {
                    $tmp = rtrim($relative_to, ' \\/');
                    if (is_dir($tmp)) {
                        $tmp .= DIRECTORY_SEPARATOR.'x'; // dummy filepath
                    } elseif (!is_dir(dirname($tmp))) {
                        return false;
                    }
                    $sig = Crypto::hash_string($temp_file); // find it again
                    $this->_items[$sig]['relto'] = $tmp;
                }
            }
            return true;
        }
        return false;
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
        if (!is_file($filename)) { return false; }

        $sig = Crypto::hash_string($filename);
        if (isset($this->_items[$sig])) { return false; }

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
    public function queue_matchedfile(string $filename, int $priority = 0, bool $min = false, $custompaths = ''): bool
    {
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
    protected function minimize(string $content): string
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
     * Construct a merged file from previously-queued files/strings,
     * if such file doesn't exist or is out-of-date.
     * Hooks 'Core::PreProcessStyles' and 'Core::PostProcessStyles' are
     * run respectively before and after the content merge.
     *
     * @param string $output_path Optional Filesystem absolute path of
     *  folder to hold the merged file. Default '' (hence use TMP_CACHE_LOCATION)
     * @param bool   $force       Optional flag whether to force
     *  recreation of the merged file. Default false
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
                        if (stripos($content, 'url') !== false) {
                            //migrate file-sourced relative url(s) to absolute
                            if (strpos($rec['file'], TMP_CACHE_LOCATION) !== 0) {
                                $content = $this->clean_urls($content, $rec['file']);
                            } elseif (!$empty($rec['relto'])) {
                                $content = $this->clean_urls($content, $rec['relto']);
                            }
                        }
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
     * @return string html like <link ... > | ''
     */
    public function page_content(string $output_path = '', bool $force = false): string
    {
        $base_path = ($output_path) ? rtrim($output_path, ' \/') : TMP_CACHE_LOCATION;
        $cache_filename = $this->render_styles($base_path, $force);
        if ($cache_filename) {
            $output_file = $base_path.DIRECTORY_SEPARATOR.$cache_filename;
            $url = cms_path_to_url($output_file);
            $sri = base64_encode(hash_file('sha256', $output_file, true));
            return "<link rel=\"stylesheet\" href=\"$url\" media=\"all\" integrity=\"sha256-$sri\" crossorigin=\"anonymous\" referrerpolicy=\"same-origin\">\n";
        }
        return '';
    }

    /**
     * Migrate each relative URL in $content to absolute
     *
     * @param string $content stylesheet content
     * @param string $sourcepath absolute filepath, from where $content was retrieved
     * @return string
     */
    protected function clean_urls(string $content, string $sourcepath): string
    {
        $base = $sourcepath;
        $converted = false;
        return preg_replace_callback(
            '~[Uu][Rr][Ll]\s*\(\s*([\'" ]*)([.\/]+)([\w\/.?&=#\-]+?)([\'" ]*)\)~',
            function($matches) use (&$base, &$converted) {
                //$matches[1] and $matches[4] (if any) are the enclosing quote-char
                //$matches[2] is relative part of url, maybe //
                //$matches[3] is the post-relative part of url (no leading /)
                if ($matches[2] == '//') {
                    $url = CSM_ROOT_URL . '/' . $matches[3];
                } else {
                    if (!$converted) {
                        $base = $this->path_to_url(dirname($base));
                        $converted = true;
                    }
                    $p = $l = strlen($base);
                    $parts = explode('/', strtr($matches[2], [' ' => '']));
                    foreach ($parts as $s) {
                        if ($s == '..') {
                            $p = strrpos($base, '/', $p - $l);
                        }
                    }
                    $url = substr($base, 0, $p) . '/' . $matches[3];
                }
                $q = trim($matches[1]);
                return "url({$q}{$url}{$q})";
            }, $content);
    }

    /**
     * Convert the supplied string, if it's an URL, to the corresponding filepath
     * @see also cms_url_to_path();
     *
     * @param string $in filepath or URL
     * @return string
     */
    protected function url_to_path(string $in): string
    {
        $in = trim($in, " \t\r\n'\"");
        if (strpos($in, '\\') !== false || realpath($in)) {
            return $in; // already a path
        }
        if (startswith($in, CMS_ROOT_URL)) {
            $s = substr($in, strlen(CMS_ROOT_URL));
            $fp = CMS_ROOT_PATH . strtr($s, '/', DIRECTORY_SEPARATOR);
        } else {
            $s = preg_replace('~^(\w+?:)?//~', '', $in);
            $fp = strtr($s, '/', DIRECTORY_SEPARATOR);
        }
        return $fp;
    }

    /**
     * Convert the supplied filepath to the corresponding URL
     * @see also cms_path_to_url()
     *
     * @param string $in
     * @return string
     */
    protected function path_to_url(string $in): string
    {
        if (($p = strpos($in, CMS_ROOT_PATH)) === 0) {
            $s = substr($in, strlen(CMS_ROOT_PATH));
        } elseif ($p > 0) {
            $s = substr($in, strlen(CMS_ROOT_PATH) + $p);
        } else {
            // TODO process relative path or non-site path
            $s = $in;
        }
        $q = str_replace(['%2F', '%5C'], ['/', '/'], rawurlencode($s));
        return CMS_ROOT_URL . $q;
    }
} // class
