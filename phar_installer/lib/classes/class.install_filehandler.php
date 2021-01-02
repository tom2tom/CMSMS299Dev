<?php

namespace cms_installer;

use cms_installer\filehandler;
use Exception;
use function cms_installer\get_server_permissions;
use function cms_installer\lang;

class install_filehandler extends filehandler
{
    /**
     * @param string $filespec site-root-relative file- or folder-path, but with leading separator
     * @param string $srcspec absolute identifier corresponding to $filespec (copyable, if that's a file)
     *  e.g. filepath or URI like file://... or phar://...
     * @throws Exception
     */
    public function handle_file(string $filespec, string $srcspec)
    {
        if( $this->is_excluded($filespec) ) return;
        if( is_dir($srcspec) ) {
            $destpath = $this->get_destdir().$filespec;
			$dirmode = get_server_permissions()[3]; // read+write
            @mkdir($destpath,$dirmode,true);
            return;
        }

        if( $this->is_langfile($filespec) ) {
            if( !$this->is_accepted_lang($filespec) ) return;
        }

        if( !$this->dir_exists($filespec) ) $this->create_directory($filespec);

        $destpath = $this->get_destdir().$filespec;
        if( is_file($destpath) && !is_writable($destpath) ) throw new Exception(lang('error_overwrite',$filespec));

        if( !@copy($srcspec,$destpath) ) throw new Exception(lang('error_extract',$filespec));
        $cksum = md5_file($srcspec,true);
        $cksum2 = md5_file($destpath,true);
        if( $cksum != $cksum2 ) throw new Exception(lang('error_checksum',$filespec));

        $this->output_string(lang('file_installed',$filespec));
    }
}
