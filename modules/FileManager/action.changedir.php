<?php
use FileManager\Utils;
use function CMSMS\log_error;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) exit;

if( $_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET[CMS_JOB_KEY]) && $_GET[CMS_JOB_KEY] > 0 ) {
  echo Utils::get_cwd();
  exit;
}

if( !isset($params['newdir']) && !isset($params['setdir']) ) $this->RedirectToAdminTab();

$path = null;
if( isset($params['newdir']) ) {
    // set a relative directory.
    $newdir = trim($params['newdir']);
    $path = cms_join_path(Utils::get_cwd(),$newdir);
}
elseif( isset($params['setdir']) ) {
    // set an explicit directory
    $path = trim($params['setdir']);
    if( $path == '::top::' ) $path = Utils::get_default_cwd();
}

try {
    Utils::set_cwd($path);
    if( !isset($params['ajax']) ) {
        Utils::set_cwd($path);
        $this->RedirectToAdminTab();
    }
}
catch (Throwable $t) {
    log_error('Attempt to set invalid working directory',$path);
    if( isset($params['ajax']) ) exit('ERROR');
    $this->SetError($this->Lang('invalidchdir',$path));
    $this->RedirectToAdminTab();
}

if( isset($params['ajax']) ) echo 'OK'; exit;
$this->RedirectToAdminTab();
