<?php
namespace FilePicker;

class CustomUploader extends \FileManager\UploadHandler
{
    private $_mod; //FilePicker object
    private $_profile; //\CMSMS\FilePickerProfile object

    public function __construct($options = null, $initialize = true, $error_messages = null)
    {
        $this->_mod = \cms_utils::get_module('FilePicker');
        if( !$options ) {
            $options = [];
            $this->_profile = null; //TODO
            $path = \cms_config::get_instance()['uploads_path'];
        } else {
            if (isset($options['profile'])) {
                $this->_profile = $options['profile'];
                unset($options['profile']);
            } else {
                $this->_profile = null; //TODO
            }
            if (isset($options['upload_dir'])) {
                $path = $options['upload_dir'];
            } else {
                $path = \cms_config::get_instance()['uploads_path'];
            }
        }
        if( !endswith( $path, DIRECTORY_SEPARATOR ) ) $path .= DIRECTORY_SEPARATOR;
        $options['upload_dir'] = $path;
        parent::__construct($options, $initialize, $error_messages);
    }

    protected function is_valid_file_object( $file_name )
    {
        $complete_path = $this->get_upload_path($file_name);
        return $this->_mod->is_acceptable_filename( $this->_profile, $complete_path );
    }

    protected function get_error_message ( $error )
    {
        $realm = $this->_mod->GetName();
          $key = 'error_upload_'.$error;

        if (\CmsLangOperations::lang_key_exists($realm, $key)) {
            return $this->_mod->Lang($key);
        }
        return parent::get_error_message( $error );
    }

    protected function handle_form_data( $fileobject, $index)
    {
        if( !$this->_profile->show_thumbs ) return;

        $complete_path = $this->get_upload_path($fileobject->name);
//      $complete_path = $this->_path.$fileobject->name;

        if( !is_file($complete_path) ) return;
        if( !$this->_mod->is_image( $complete_path ) ) return;
        $info = getimagesize($complete_path);
        if( !$info || !isset($info['mime']) ) return;

        // gotta create a thumbnail
        $width = (int) \cms_siteprefs::get('thumbnail_width',96);
        $height = (int) \cms_siteprefs::get('thumbnail_height',96);
        if( $width < 1 || $height < 1 ) return;

        $complete_thumb = $this->get_upload_path('thumb_'.$fileobject->name);
//      $complete_thumb = $this->_path.'thumb_'.$fileobject->name;

        $i_src = imagecreatefromstring(file_get_contents($complete_path));
        $i_dest = imagecreatetruecolor($width,$height);
        imagealphablending($i_dest,FALSE);
        $color = imageColorAllocateAlpha($i_src, 255, 255, 255, 127);
        imagecolortransparent($i_dest,$color);
        imagefill($i_dest,0,0,$color);
        imagesavealpha($i_dest,TRUE);
        imagecopyresampled($i_dest,$i_src,0,0,0,0,$width,$height,imagesx($i_src),imagesy($i_src));

        $res = null;
        switch( $info['mime'] ) {
        case 'image/gif':
            $res = imagegif($i_dest,$complete_thumb);
            break;
        case 'image/png':
            $res = imagepng($i_dest,$complete_thumb,9);
            break;
        case 'image/jpeg':
            $res = imagejpeg($i_dest,$complete_thumb,100);
            break;
        }
    }
}
