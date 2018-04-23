<?php

namespace FileManager;
use FileManager\filemanager_utils;

class UploadHandler extends jquery_upload_handler
{
    public function __construct($options=null)
    {
        if( !is_array($options) ) $options = [];

        // remove image handling, we're gonna handle this another way
        $options['orient_image'] = false;   // turn off auto image rotation
        $options['image_versions'] = [];

        $options['upload_dir'] = filemanager_utils::get_full_cwd().DIRECTORY_SEPARATOR;
        $options['upload_url'] = filemanager_utils::get_cwd_url().'/';

        // set everything up.
        parent::__construct($options);
    }

    protected function is_file_acceptable( $file )
    {
        $config = \cms_config::get_instance();
        if( !$config['developer_mode'] ) {
            $ext = strtolower(substr(strrchr($file, '.'), 1));
            if( startswith($ext,'php') || endswith($ext,'php') ) return false;
        }
        return true;
    }

    protected function after_uploaded_file($fileobject)
    {
        // here we may do image handling, and other cruft.
        if( is_object($fileobject) && $fileobject->name != '' ) {

            $mod = \cms_utils::get_module('FileManager');
            $parms = [];
            $parms['file'] = filemanager_utils::join_path(filemanager_utils::get_full_cwd(),$fileobject->name);

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
