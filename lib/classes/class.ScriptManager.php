<?php
#Scripts mamagement class
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

class ScriptManager
{
    private $_scripts = [];
    private $_script_priority = 2;

    public function get_script_priority()
    {
        return $this->_script_priority;
    }

    public function set_script_priority( int $val )
    {
        $this->_script_priority = max(1,min(3,$val));
    }

    public function queue_script( string $filename, int $priority = null )
    {
        if( !is_file($filename) ) return;

        $sig = md5( $filename );
        if( isset( $this->_scripts[$sig]) ) return;
        if( is_null( $priority ) ) $priority = $this->_script_priority;

        $this->_scripts[$sig] = [
            'file' => $filename,
            'mtime' => filemtime( $filename ),
            'priority' => $priority,
            'index' => count( $this->_scripts )
            ];
    }

    public function render_scripts( string $output_path, $force = false, $allow_defer = true )
    {
        if( $this->_scripts && !count($this->_scripts) ) return; // nothing to do
        if( !is_dir( $output_path ) ) return; // nowhere to put it

        // auto append the defer script
        if( $allow_defer ) {
            $defer_script = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery.cmsms_defer.js';
            $this->queue_script( $defer_script, 3 );
        }

        $t_scripts = \CMSMS\HookManager::do_hook( 'Core::PreProcessScripts', $this->_scripts );
        if( $t_scripts ) $scripts = $t_scripts;

        // sort the scripts by their priority, then their index (to preserve order)
        // because module actions can be processed first... we 'lower' their priority
        $scripts = $this->_scripts;
        usort( $scripts, function( $a, $b ) {
                if( $a['priority'] < $b['priority'] ) return -1;
                if( $a['priority'] > $b['priority'] ) return 1;
                if( $a['index'] < $b['index'] ) return -1;
                if( $a['index'] > $b['index'] ) return 1;
                return 0;
            });

        $t_sig = $t_mtime = null;
        foreach( $scripts as $sig => $rec ) {
            $t_sig .= $sig;
            $t_mtime = max( $rec['mtime'], $t_mtime );
        }
        $sig = md5( __FILE__.$t_sig.$t_mtime );
        $js_filename = "cms_$sig.js";
        $output_file = "$output_path/$js_filename";
        if( $force || !is_file($output_file) || filemtime($output_file) < $t_mtime ) {
        $output = null;
            foreach( $scripts as $sig => $rec ) {
                $content = file_get_contents( $rec['file'] );
                $output .= $content."\n\n";
            }
            $tmp = \CMSMS\HookManager::do_hook( 'Core::PostProcessScripts', $output );
            if( $tmp ) $output = $tmp;
            file_put_contents( $output_file, $output );
        }
        return $js_filename;
    }
} // class
