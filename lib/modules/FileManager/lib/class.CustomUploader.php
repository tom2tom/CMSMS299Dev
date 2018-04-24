<?php

namespace FileManager;
use FileManager\filemanager_utils;

class CustomUploader extends UploadHandler
{
    public function __construct($options = null, $initialize = true, $error_messages = null)
    {
        if( !$options ) $options = [];

        // remove image handling, we're gonna handle this another way
        $options['orient_image'] = false;   // turn off auto image rotation
        $options['image_versions'] = [];

        $options['upload_dir'] = filemanager_utils::get_full_cwd().DIRECTORY_SEPARATOR;
        $options['upload_url'] = filemanager_utils::get_cwd_url().'/';

        // set everything up
        parent::__construct($options, $initialize, $error_messages);
    }

    protected function is_valid_file_object($file_name)
    {
        if( !\cms_config::get_instance()['developer_mode'] ) {
            $ext = strtolower(substr(strrchr($file_name, '.'), 1));
            if( startswith($ext,'php') || endswith($ext,'php') ) return false;
            return parent::is_valid_file_object($file_name);
        }
        return true;
    }

    protected function handle_form_data($fileobject, $index)
    {
        // here we may do image handling and other cruft
        if( is_object($fileobject) && $fileobject->name != '' ) {

            $parms = [];
            $parms['file'] = filemanager_utils::join_path(filemanager_utils::get_full_cwd(),$fileobject->name);

            $mod = \cms_utils::get_module('FileManager');
            if( $mod->GetPreference('create_thumbnails') ) {
                $thumb = filemanager_utils::create_thumbnail($parms['file']);
                if( $thumb ) $params['thumb'] = $thumb;
            }

            $str = $fileobject->name.' uploaded to '.filemanager_utils::get_full_cwd();
            if( isset($params['thumb']) ) $str .= ' and a thumbnail was generated';
            audit('',$mod->GetName(),$str);

            \CMSMS\HookManager::do_hook( 'FileManager::OnFileUploaded', $parms );
        }
    }
}
