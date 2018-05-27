<?php
namespace FilePicker;

use cms_config;
use cms_siteprefs;
use cms_utils;
use const CMS_ROOT_PATH;
use function endswith;
use function get_userid;

class UploadHandler extends jquery_upload_handler
{
    private $_mod; //FilePicker-module object
    private $_profile; //CMSMS\FilePickerProfile or derivative
    private $_path; //absolute filesystem path for upload

    /**
     * @param array $opts @since 2.3 Optional assoc. array of parameters for the upload
	 * Of special interest: 'module', 'upload_dir', 'profile' (all optional)
     */
    public function __construct($opts = [])
    {
        if( empty($opts['module']) ) {
            $this->_mod = cms_utils::get_module('FilePicker');
        } else {
            $this->_mod = $opts['module'];
            unset($opts['module']);
        }

        if( isset($opts['upload_dir']) ) {
			$path = trim($opts['upload_dir']);
		} else {
			$path = '';
		}
		if ($path === '' || !preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $path)) {
			// $path not provided or is relative
            $config = cms_config::get_instance();
            $devmode = $this->_mod->CheckPermission('Modify Site Code') || !empty($config['developer_mode']);
            $base  = ( $devmode ) ? CMS_ROOT_PATH : $config['uploads_path'];
			$path = ($path === '') ? $base : cms_join_path($base,$path);
        }
		// TODO $path existence, validity checks ... per CMSMS\FileTypeHelper-object ??
		$this->_path =  ( endswith( $path, DIRECTORY_SEPARATOR ) ) ? $path : $path . DIRECTORY_SEPARATOR;
        $opts['upload_dir'] = $this->_path; //expects trailing separator

        if( empty($opts['profile']) ) {
            $this->_profile = $this->_mod->get_default_profile($path, get_userid(false));
        } else {
            $this->_profile = $opts['profile'];
            unset($opts['profile']);
        }
        parent::__construct( $opts );
    }

    public function is_file_type_acceptable( $fileobject )
    {
        $complete_path = $this->_path.$fileobject->name;
        return $this->_mod->is_acceptable_filename( $this->_profile, $complete_path );
    }

    public function process_error( $fileobject, $error )
    {
        $fileobject = parent::process_error( $fileobject, $error );
        if( $fileobject->error ) {
            $fileobject->errormsg = $this->_mod->Lang('error_upload_'.$fileobject->error);
        }
        return $fileobject;
    }

    public function after_uploaded_file( $fileobject )
    {
        if( !$this->_profile->show_thumbs ) return;

        $complete_path = $this->_path.$fileobject->name;
        if( !is_file($complete_path) ) return;
        if( !$this->_mod->is_image( $complete_path ) ) return;
        $info = getimagesize($complete_path);
        if( !$info || !isset($info['mime']) ) return;

        // gotta create a thumbnail
        $width = (int) cms_siteprefs::get('thumbnail_width',96);
        $height = (int) cms_siteprefs::get('thumbnail_height',96);
        if( $width < 1 || $height < 1 ) return;

        $complete_thumb = $this->_path.'thumb_'.$fileobject->name;
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
