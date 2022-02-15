<?php
/*
FileManager module upload-methods class
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
namespace FileManager;

use CMSMS\Events;
use CMSMS\SingleItem;
use CMSMS\Utils as AppUtils;
use FileManager\Utils;
use FilePicker\jquery_upload_handler;
use function cms_join_path;
use function CMSMS\log_info;
use function endswith;
use function startswith;

class UploadHandler extends jquery_upload_handler
{
    public function __construct($options = null)
    {
        if (!is_array($options)) {
            $options = [];
        }

        // remove image handling, we're gonna handle this another way
        $options['orient_image'] = false;  // turn off auto image rotation
        $options['image_versions'] = [];
        $options['upload_dir'] = Utils::get_full_cwd().DIRECTORY_SEPARATOR;
        $options['upload_url'] = Utils::get_cwd_url().'/';

        // set everything up
        parent::__construct($options);
    }

	/**
	 * Minimal check whether the named file is ok
	 * @param string $file
	 * @return boolean
	 */
    protected function is_file_acceptable($file)
    {
        if (!SingleItem::Config()['developer_mode']) {
            $ext = strtolower(substr(strrchr($file, '.'), 1));
            if (startswith($ext, 'php') || endswith($ext, 'php')) {
                return false;
            }
        }
        return true;
    }

	/**
     * This may do image handling and other cruft
	 *
	 * @param type $fileobject
	 */
    protected function after_uploaded_file($fileobject)
    {
        if (is_object($fileobject) && $fileobject->name) {
            $dir = Utils::get_full_cwd();
            $file = cms_join_path($dir, $fileobject->name);
            $parms = ['file' => $file];

            Events::SendEvent('FileManager', 'OnFileUploaded', $parms);
            if (is_array($parms) && isset($parms['file'])) {
                $file = $parms['file']; // file name could have changed
            }

            $thumb = null;
            $mod = AppUtils::get_module('FileManager');
            if ($mod->GetPreference('create_thumbnails')) {
                $thumb = Utils::create_thumbnail($file, null, true);
            }

            $str = basename($file).' uploaded to '.$dir;
            if ($thumb) {
                $str .= ' and a thumbnail was generated';
            }
            log_info('', 'FileManager', $str);
        }
    }
}
