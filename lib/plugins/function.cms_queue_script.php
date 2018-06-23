<?php
#Plugin which accumulates javascript to be include in a page template
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

function smarty_function_cms_queue_script( $params, $template )
{
    if( !isset($params['file']) ) return;
    $combiner = CmsApp::get_instance()->GetScriptManager();

    $file = trim($params['file']);
    if( is_file( $file ) ) {
        $combiner->queue_file( $file );
        return;
    }

    // if it's relative to a CMSMS path
    if( !startswith( $file, DIRECTORY_SEPARATOR ) ) $file = DIRECTORY_SEPARATOR.$file;
    $config = \cms_config::get_instance();
    $paths = [ CMS_ASSETS_PATH.$file, $config['uploads_path'].$file, CMS_ROOT_PATH.$file ];
    foreach( $paths as $one ) {
        if( is_file( $one ) ) $combiner->queue_file( $one );
    }
}

function smarty_cms_help_function_cms_queue_script()
{
    echo lang_by_realm('tags','help_function_queue_script');
}

function smarty_cms_about_function_cms_queue_script()
{
	echo <<<'EOS'
<p>Author: Robert Campbell &lt;calguy1000@cmsmadesimple.org&gt;</p>
<p>Version: 1.0</p>
<p>
Change History:<br />
None
</p>
EOS;
}
