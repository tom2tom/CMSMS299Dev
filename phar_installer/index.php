<?php

use cms_installer\cli_install;
use cms_installer\gui_install;

function _detect_bad_ioncube()
{
    if( extension_loaded('ionCube Loader') ) {
        if( function_exists('ioncube_loader_version') ) {
            $ver = ioncube_loader_version();
            if( version_compare($ver,'4.1') < 0 ) throw new Exception('An old version of ioncube loader was detected.  Older versions are known to have problems with PHAR files. Sorry, but we cannot continue.');
        }
    }
}

//
// initialization
//
try {
    // some basic system wide pre-requisites
    if( version_compare(phpversion(),'7.1') < 0 ) throw new Exception('Sorry, this installer requires PHP 7.1 or higher');
    _detect_bad_ioncube();

    // disable some stuff.
    @ini_set('opcache.enable',0); // disable zend opcode caching.
    @ini_set('apc.enabled',0); // disable apc opcode caching (for later versions of APC)
    @ini_set('xcache.cacher',0); // disable xcache opcode caching

    if( php_sapi_name() == 'cli' ) {
        require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.cli_install.php';
        $app = new cli_install();
    }
    else {
        require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.gui_install.php';
        $app = new gui_install(); //no custom config-file specified
    }
    $app->run();
}
catch( Exception $e ) {
    // this handles fatal, serious errors.
    // cannot use stylesheets, scripts, or images here, as the problem may be a phar based problem
    $msg = $e->GetMessage();
    echo <<<EOT
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>CMS Made Simple Installer : Fatal Error</title>
  </head>
  <body>
    <div style="border-radius: 3px; max-width: 85%; margin: 10% auto; font-family: Arial, Helvetica Neue, Helvetica, sans-serif; background-color: #f2dede; border: 1px solid #ebccd1; color: #a94442; padding: 15px;">
      <h1>Fatal Error</h1>
      <p>$msg</p>
    </div>
  </body>
</html>
EOT;
}
