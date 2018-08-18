<?php
function smarty_function_file_url($params, $template)
{
    $file = trim(get_parameter_value($params,'file'));
    if( !$file ) {
        trigger_error('file_url plugin: invalid file parameter');
        return;
    }

    $config = cms_config::get_instance();
    $dir = $config['uploads_path'];
    $add_dir = trim(get_parameter_value($params,'dir'));

    if( $add_dir ) {
        if( startswith($add_dir,DIRECTORY_SEPARATOR) ) $add_dir = substr($add_dir,1);
        $dir .= DIRECTORY_SEPARATOR.$add_dir;
        if( !is_dir($dir) || !is_readable($dir) ) {
            trigger_error("file_url plugin: dir=$add_dir invalid directory name specified");
            return;
        }
    }

    $fullpath = $dir.DIRECTORY_SEPARATOR.$file;
    if( !is_file($fullpath) || !is_readable($fullpath) ) {
        // no error log here.
        return;
    }

    // convert it to a url
    $out = CMS_UPLOADS_URL.'/';
    if( $add_dir ) $out .= $add_dir.'/';
    $out .= $file;
    $out = strtr($out,'\\','/');

    if( isset($params['assign']) ) {
        $template->assign(trim($params['assign']),$out);
        return;
    }
    return $out;
}
