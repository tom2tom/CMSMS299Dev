<?php
#Class for consolidating specified javascript's into a single file
#Copyright (C) 2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

namespace CMSMS;

use const CMS_SCRIPTS_PATH;
use const TMP_CACHE_LOCATION;
use function file_put_contents;
use function startswith;

//TODO a job to clear old consolidations ? how old ?

/**
 * A class for consolidating specified javascript's into a single file.
 *
 * @since 2.3
 * @package CMS
 */
class ScriptManager
{
    private $_scripts = [];
    private $_script_priority = 2;

    /**
     * Get default priority for scripts to be merged
     *
     * @return int 1..3 The current default priority
     */
    public function get_script_priority() : int
    {
        return $this->_script_priority;
    }

    /**
     * Set default priority for scripts to be merged
     *
     * @param int $val The new default priority value (constrained to 1..3)
     */
    public function set_script_priority( int $val )
    {
        $this->_script_priority = max(1,min(3,$val));
    }

    /**
     * Revert to initial state
     */
    public function reset()
    {
        $this->_scripts = [];
        $this->_script_priority = 2;
    }

    /**
     * Record a string to be merged
     *
     * @param string $output   js string
     * @param int    $priority Optional priority 1..3 for the script. Default 0 (use current default)
     * @param bool   $force    Optional flag whether to force recreation of the merged file. Default false
     */
    public function queue_string( string $output, int $priority = 0, bool $force = false )
    {
        $sig = md5( __FILE__.$output );
        $output_file = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR."cms_$sig.js";
        if( $force || !is_file($output_file) ) {
            file_put_contents( $output_file, $output, LOCK_EX );
        }
        $this->queue_file($output_file, $priority);
    }

    /**
     * Record a script-file to be merged if necessary
     *
     * @param string $filename Filesystem path of script file
     * @param int    $priority Optional priority 1..3 for the script. Default 0 (use current default)
     */
    public function queue_file( string $filename, int $priority = 0 )
    {
        if( !is_file($filename) ) return;

        $sig = md5( $filename );
        if( isset( $this->_scripts[$sig]) ) return;

        if( $priority < 1 ) {
            $priority = $this->_script_priority;
        } elseif( $priority > 3 ) {
            $priority = 3;
        } else {
            $priority = (int)$priority;
        }

        $this->_scripts[$sig] = [
            'file' => $filename,
            'mtime' => filemtime( $filename ),
            'priority' => $priority,
            'index' => count( $this->_scripts )
        ];
    }

    /**
     * Construct a merged file from previously-queued scripts, if such file
     * doesn't exist or is out-of-date.
     * Hooks 'Core::PreProcessScripts' and 'Core::PostProcessScripts' are
     * run respectively before and after the content merge.
     *
     * @param string $output_path Optional Filesystem path of folder to hold the script file. Default '' (use TMP_CACHE_LOCATION)
     * @param bool   $force       Optional flag whether to force recreation of the merged file. Default false
     * @param bool   $allow_defer Optional flag whether to force-include jquery.cmsms_defer.js. Default true
     * @return string basename of the merged-scripts file
     */
    public function render_scripts( string $output_path = '', bool $force = false, bool $allow_defer = true )
    {
        if( $this->_scripts && !count($this->_scripts) ) return; // nothing to do
        $base_path = ($output_path) ? rtrim($output_path, ' /\\') : TMP_CACHE_LOCATION;
        if( !is_dir( $base_path ) ) return; // nowhere to put it

        // auto append the defer script
        if( $allow_defer ) {
            $defer_script = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery.cmsms_defer.js';
            $this->queue_file( $defer_script, 3 );
        }

        $tmp = Events::SendEvent( 'Core', 'PreProcessScripts', $this->_scripts );
        $scripts = ( $tmp ) ? $tmp : $this->_scripts;

		if( $scripts ) {
			if( count($scripts) > 1) {
				// sort the scripts by priority, then index (to preserve order)
				usort( $scripts, function( $a, $b ) {
					if( $a['priority'] != $b['priority'] ) return $a['priority'] <=> $b['priority'];
					return $a['index'] <=> $b['index'];
				});
			}

			$t_sig = '';
			$t_mtime = -1;
			foreach( $scripts as $sig => $rec ) {
				$t_sig .= $sig;
				$t_mtime = max( $rec['mtime'], $t_mtime );
			}
			$sig = md5( __FILE__.$t_sig.$t_mtime );
			$js_filename = "cms_$sig.js";
			$output_file = $base_path.DIRECTORY_SEPARATOR.$js_filename;

			if( $force || !is_file($output_file) || filemtime($output_file) < $t_mtime ) {
				$output = '';
				foreach( $scripts as $sig => $rec ) {
					$content = @file_get_contents( $rec['file'] );
					if( $content ) $output .= $content."\n\n";
				}

				$tmp = Events::SendEvent( 'Core', 'PostProcessScripts', $output );
				if( $tmp ) $output = $tmp;
				file_put_contents( $output_file, $output, LOCK_EX );
			}
			return $js_filename;
		}
    }
} // class
