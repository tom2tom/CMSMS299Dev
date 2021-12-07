<?php
/*
Class for consolidating specified javascript's into a single file
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\Crypto;
use CMSMS\Events;
use const CMS_SCRIPTS_PATH;
use const TMP_CACHE_LOCATION;
use function cms_get_script;
use function cms_path_to_url;
use function file_put_contents;

//TODO an async Job to clear old consolidations ? how old ? c.f. TMP_CACHE_LOCATION cleaner

/**
 * A class for consolidating specified javascript files and/or strings
 * into a single file.
 *
 * @since 2.99
 * @package CMS
 */
class ScriptsMerger
{
    private $_items = [];
    private $_item_priority = 2;

    /**
     * Get default priority for items to be merged
     *
     * @return int 1..3 The current default priority
     */
    public function get_script_priority() : int
    {
        return $this->_item_priority;
    }

    /**
     * Set default priority for items to be merged
     *
     * @param int $val The new default priority value (constrained to 1..3)
     */
    public function set_script_priority(int $val)
    {
        $this->_item_priority = max(1,min(3,$val));
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
     * @param string $output   js string
     * @param int    $priority Optional priority 1..3 for the script. Default 0 (use current default)
     * @param bool   $force    Optional flag whether to force recreation of the merged file. Default false
     */
    public function queue_string(string $output, int $priority = 0, bool $force = false)
    {
        $sig = Crypto::hash_string(__FILE__.$output);
        $output_file = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR."cms_$sig.js";
        if ($force || !is_file($output_file)) {
            file_put_contents($output_file, $output, LOCK_EX);
        }
        $this->queue_file($output_file, $priority);
    }

    /**
     * Record a file to be merged if necessary
     *
     * @param string $filename Filesystem path of script file
     * @param int    $priority Optional priority 1... for the file. Default 0 (use current default)
     * @return bool indicating success
     */
    public function queue_file(string $filename, int $priority = 0)
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
            'index' => count($this->_items)
        ];
        return true;
    }

    /**
     * Find and record a script-file to be merged if necessary
     *
     * @param string $filename absolute or relative filepath or (base)name
     *  of the wanted script file, optionally including [.-]min before
     *  the .js extension. If the name includes a version, that will be
     *  taken into account. Otherwise, any found version will be used.
     *  Min-format will be preferred over non-min.
     * @param int    $priority Optional priority 1..3 for the script. Default 0 (use current default)
     * @param mixed  $custompaths Optional string | string[] custom places to search before defaults
     * @return bool indicating success
     */
    public function queue_matchedfile(string $filename, int $priority = 0, $custompaths = '') : bool
    {
//TODO  $filename = $this->url_to_path($filename); // migrate URL-formatted filename to filepath
        $cache_filename = cms_get_script($filename, false, $custompaths);
        if ($cache_filename) {
            return $this->queue_file($cache_filename, $priority);
        }
        return false;
    }

    /**
     * Construct a merged file from previously-queued files, if such file
     * doesn't exist or is out-of-date.
     * Hooks 'Core::PreProcessScripts' and 'Core::PostProcessScripts' are
     * run respectively before and after the content merge.
     *
     * @param string $output_path Optional Filesystem absolute path of folder
     *  to hold the merged file. Default '' hence use TMP_CACHE_LOCATION
     * @param bool   $force       Optional flag whether to force re-creation
     *  of the merged file. Default false
     * @param bool   $defer       Optional flag whether to automatically
     *  include jquery.cmsms_defer.js. Default true
     * @return mixed string basename of the merged-items file | null upon error
     */
    public function render_scripts(string $output_path = '', bool $force = false, bool $defer = true)
    {
        if (!$this->_items) return; // nothing to do
        $base_path = ($output_path) ? rtrim($output_path, ' \/') : TMP_CACHE_LOCATION;
        if (!is_dir($base_path)) return; // nowhere to put it

        // auto-append the defer/migrate script
        if ($defer) {
            $defer_script = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery.cmsms_defer.js';
            $this->queue_file($defer_script, 3);
        }

        $tmp = Events::SendEvent('Core', 'PreProcessScripts', $this->_items);
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
            $cache_filename = "combined_$sig.js";
            $output_file = $base_path.DIRECTORY_SEPARATOR.$cache_filename;

            if ($force || !is_file($output_file) || filemtime($output_file) < $t_mtime) {
                $output = '';
                foreach($items as $rec) {
                    $content = @file_get_contents($rec['file']);
                    if ($content) {
                        $output .= $content.PHP_EOL;
                    }
                }

                $tmp = Events::SendEvent('Core', 'PostProcessScripts', $output);
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
     * @see also ScriptsMerger::render_scripts()
     *
     * @param string $output_path Optional Filesystem absolute path of folder
     *  to hold the merged-scripts file. Default '' hence use TMP_CACHE_LOCATION
     * @param bool   $force       Optional flag whether to force re-creation
     *  of the merged file. Default false
     * @param bool   $defer Optional flag whether to automatically include
     *  script jquery.cmsms_defer.js. Default false
     * @return string html like <script ... </script> | ''
     */
    public function page_content(string $output_path = '', bool $force = false, bool $defer = false) : string
    {
        $base_path = ($output_path) ? rtrim($output_path, ' \/') : TMP_CACHE_LOCATION;
        $cache_filename = $this->render_scripts($base_path, $force, $defer);
        if ($cache_filename) {
            $output_file = $base_path.DIRECTORY_SEPARATOR.$cache_filename;
            $url = cms_path_to_url($output_file);
            return "<script type=\"text/javascript\" src=\"$url\"></script>\n";
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
