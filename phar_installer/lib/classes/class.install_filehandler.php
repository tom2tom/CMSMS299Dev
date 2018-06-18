<?php

namespace __installer;

use __installer\filehandler;
use Exception;
use PharFileInfo;
use function __installer\CMSMS\lang;

class install_filehandler extends filehandler
{
 /**
  * @param string $filespec
  * @param string $srcspec
  * @param PharFileInfo $fi
  * @throws Exception
  */
  public function handle_file(string $filespec, string $srcspec, PharFileInfo $fi)
  {
    if( $this->is_excluded($filespec) ) return;
    if( $this->is_langfile($filespec) ) {
      if( !$this->is_accepted_lang($filespec) ) return;
    }

    if( !$this->dir_exists($filespec) ) $this->create_directory($filespec);

    $destname = $this->get_destdir().$filespec;
    if( file_exists($destname) && !is_writable($destname) ) throw new Exception(lang('error_overwrite',$filespec));

    $cksum = md5_file($srcspec);
    @copy($srcspec,$destname);
    $cksum2 = md5_file($destname);
    if( $cksum != $cksum2 ) throw new Exception(lang('error_checksum',$filespec));

    $this->output_string(lang('file_installed',$filespec));
  }
}
