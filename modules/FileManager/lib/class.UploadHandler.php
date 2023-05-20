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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace FileManager;

use CMSMS\Events;
use CMSMS\FileTypeHelper;
use CMSMS\Lone;
use CMSMS\Utils as AppUtils;
use FileManager\Utils;
use FilePicker\jquery_upload_handler;
use function cms_join_path;
use function CMSMS\log_info;

class UploadHandler extends jquery_upload_handler
{
    public function __construct(/*array */$options = [])
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
     * Minimal check whether the specified file is ok. Not just its 'type'.
     * @see also FolderControlOperations::is_file_name_acceptable()
     * @param stdClass object $fileobject with properties ->name, size, type (at least)
     * @return boolean
     */
    protected function is_file_type_acceptable($fileobject)
    {
        //TODO $tmp = \CMSMS\sanitizeVal($file->name, CMSSAN_FILE) cleanup return false if invalid
        if (!Lone::get('Config')['developer_mode']) {
            // reject browser-executable files
            $helper = new FileTypeHelper();
            if ($helper->is_executable($fileobject->name)) {
                return false;
            }
            // reject bodgy image files
            if ($helper->is_image($fileobject->name)) {
            // TODO get its content, check that & return false
            // OR just in case: log_info('', 'FileManager', 'uploaded '.$fileobject->name);
            }
            // reject access-control files
            $nm = basename($fileobject->name);
            if ($nm == '.htaccess' || strcasecmp($nm, 'web.config') == 0) {
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

            $thumb = false;
            $helper = new FileTypeHelper();
            if ($helper->is_image($fileobject->name)) {
               //TODO check content of image files c.f. cms_move_uploaded_file()
                $mod = AppUtils::get_module('FileManager');
                if ($mod->GetPreference('create_thumbnails')) {
                    $thumb = Utils::create_thumbnail($file, null, true);
                }
            }
            $str = basename($file).' uploaded to '.$dir;
            if ($thumb) {
                $str .= ' and a thumbnail was generated';
            }
            log_info('', 'FileManager', $str);
        }
    }
}