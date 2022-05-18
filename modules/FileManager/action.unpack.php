<?php
use FileManager\Utils;
use wapmorgan\UnifiedArchive\UnifiedArchive;
use function CMSMS\log_notice;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed() ) exit;
if( isset($params['cancel']) ) {
    $this->Redirect($id,'defaultadmin',$returnid,$params);
}
$sel = $params['sel'];
if( !is_array($sel) ) {
    $sel = json_decode(rawurldecode($sel), true);
}
if( !$sel ) {
    $params['fmerror'] = 'nofilesselected';
    $this->Redirect($id,'defaultadmin',$returnid,$params);
}
if( count($sel) > 1 ) {
    $params['fmerror'] = 'morethanonefiledirselected';
    $this->Redirect($id,'defaultadmin',$returnid,$params);
}

//$config = Lone::get('Config');
$filename = $this->decodefilename($sel[0]);
$src = cms_join_path(CMS_ROOT_PATH,Utils::get_cwd(),$filename);
if( !file_exists($src) ) {
    $params['fmerror'] = 'filenotfound';
    $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$res = false;
try {
    // archive-classes autoloading
    if( 1 ) { // TODO not already registered
        spl_autoload_register(['FileManager\Utils','ArchAutoloader']);
    }
    require_once cms_join_path(__DIR__,'lib','UnifiedArchive','UnifiedArchive.php');
    $archive = UnifiedArchive::open($src);
    if( $archive ) {
        $destdir = cms_join_path(CMS_ROOT_PATH,Utils::get_cwd());
        $fs = disk_free_space($destdir);
        if( $fs > $archive->getOriginalSize() ) {
            if( !endswith($destdir,DIRECTORY_SEPARATOR) ) {
                $destdir = rtrim($destdir,'/\\').DIRECTORY_SEPARATOR; //TODO needed ?
            }
            $archive->extractFiles($destdir);
            $res = true; // even if 0 files processed
            //ETC
        } else {
            //TODO report something
        }
    } else {
        //TODO report something
    }
} catch (Throwable $t) {
   //TODO report something
}

if ($res) {
    $params['fmmessage'] = 'unpacksuccess'; //strips the file data
    log_notice('File Manager','Unpacked file: '.$src);
} else {
//TODO
//    $params['fmerror'] = 'something';
//    log_error('File Manager',$subject);
}
$this->Redirect($id,'defaultadmin',$returnid,$params);
