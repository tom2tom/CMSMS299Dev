<?php

namespace cms_installer\wizard;

use cms_installer\install_filehandler;
use cms_installer\manifest_reader;
use cms_installer\utils;
use Exception;
use FilesystemIterator;
use PharData;
use PHPArchive\Tar;
use RecursiveIteratorIterator;
use function cms_installer\endswith;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\smarty;
use function cms_installer\get_app;

class wizard_step7 extends wizard_step
{
    protected function process()
    {
        // nothing here
    }

    /**
     * Return sorted array of 'idenfifiers' of installed translation files, each like 'en_US'
     * @return array
     * @throws Exception if there is no such file
     */
    private function detect_languages() : array
    {
        $this->message(lang('install_detectlanguages'));
        $destdir = get_app()->get_destdir();
        $pattern = joinpath($destdir, 'lib', 'nls', '*nls.php');

        $files = glob($pattern);
        if( !$files ) throw new Exception(lang('error_internal',750));

        foreach( $files as &$one ) {
            $one = basename($one, '.nls.php');
        }
        unset($one);

        return $files;
    }

    private function do_index_html()
    {
        $this->message(lang('install_dummyindexhtml'));

        $destdir = get_app()->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',751));

        $archive = get_app()->get_archive();
        if( class_exists('PharData') ) {
            $len = strlen('phar://'.$archive); //each file's prefix-length
            $d2 = $destdir . '/';
            $iter = new RecursiveIteratorIterator(
                new PharData($archive,
                  FilesystemIterator::KEY_AS_FILENAME |
                  FilesystemIterator::CURRENT_AS_PATHNAME |
                  FilesystemIterator::SKIP_DOTS |
                  FilesystemIterator::UNIX_PATHS),
                RecursiveIteratorIterator::SELF_FIRST);
            foreach( $iter as $fn=>$file ) {
                $fp = substr($file,$len);
                $dn = $destdir.$fp;
                if( is_dir($dn) ) {
                    $idxfile = $dn.DIRECTORY_SEPARATOR.'index.html';
                } else {
                    $idxfile = dirname($dn).DIRECTORY_SEPARATOR.'index.html';
                }
                @touch($idxfile); //ignore failure
            }
            unlink($destdir.DIRECTORY_SEPARATOR.'index.html');
            @unlink($destdir.DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'index.html'); //ok if dir is renamed
        }
        else {
            $destdir .= DIRECTORY_SEPARATOR;
            $adata = new Tar();
            $adata->open($archive);
            $files = $adata->folder_contents();
            foreach ($files as $fp) {
                if( $fp == '.' || $fp == 'admin' ) {
                    continue; //places having index.php
                }
                $idxfile = $destdir.$fp.DIRECTORY_SEPARATOR.'index.html';
                @touch($idxfile);
            }
        }
    }

    private function do_files(bool $checklangs = false)
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',601));

        $languages = ['en_US'];
        if( $checklangs ) { // upgrade or refresh
            try {
                $languages = array_merge($languages, $this->detect_languages());
            }
            catch (Exception $e) {/* nothing here */}
        }
        $siteinfo = $this->get_wizard()->get_data('siteinfo');
        if( $siteinfo && isset($siteinfo['languages']) ) {
            $languages = array_merge($languages, $siteinfo['languages']);
        }
        $languages = array_unique($languages);

        $filehandler = new install_filehandler();
        $filehandler->set_destdir($destdir);
        $filehandler->set_languages($languages);
        $filehandler->set_output_fn('cms_installer\wizard\wizard_step6::verbose');

        $from = $to = $lens = [];
        $app_config = $app->get_config();
        //we rename filepaths, not the actual folders followed by rename-back
        if( isset($app_config['admindir']) && ($aname = $app_config['admindir']) != 'admin' ) {
            $s = '/admin'; //hardcoded '/' filepath-separators in phar tarball
            $from[] = $s;
            $to[] = '/'.$aname; //the separator may be migrated, downstream
            $lens[] = strlen($s);
        }
        if( isset($app_config['assetsdir']) && ($aname = $app_config['assetsdir']) != 'assets' ) {
            $s = '/assets';
            $from[] = $s;
            $to[] = '/'.$aname;
            $lens[] = strlen($s);
        }

        if( $siteinfo !== NULL ) {
            $xmodules = $siteinfo['xmodules'] ?? []; //TODO relevant non-core modules are needed for upgrade as well as install
            if( !is_array($xmodules) ) $xmodules = [$xmodules];
        }
        else {
            $xmodules = NULL;
        }
        $allmodules = [];

        $this->message(lang('install_extractfiles'));

        list($iter,$archdir) = $app->unpack_archive();
        $len = strlen($archdir);

        foreach ($iter as $fn=>$fp) {
            if( strpos($fp,'modules') !== FALSE ) {
                if( strpos($fp,'/lib/modules/') !== FALSE ) {
                    if( endswith($fn,'.module.php') ) {
                        $allmodules[] = substr($fn,0,strlen($fn) - 11);
                    }
                }
                elseif( ($up = strpos($fp,'/assets/modules/')) !== FALSE ) {
                    if( !($xmodules === NULL || $xmodules) ) continue; //no non-core modules used
                    $parts = explode('/',substr($fp,$up + 16));
                    if( !$parts[0] || ($xmodules !== NULL && !in_array($parts[0],$xmodules)) ) continue; //this one not used
                    if( endswith($fn,'.module.php') ) {
                        $allmodules[] = $parts[0];
                    }
                }
            }

            $spec = substr($fp,$len); //retains leading separator
            if( $from ) {
                //replace prefix-only, where relevant
                foreach( $from as $i=>$s ) {
                    $l = $lens[$i];
                    if( strncmp($spec,$s,$l) == 0 ) {
                        $spec = $to[$i].substr($spec,$l);
                        break;
                    }
                }
            }
            $filehandler->handle_file($spec,$fp);
        }

        if( $allmodules ) {
            $siteinfo['havemodules'] = array_unique($allmodules);
            $this->get_wizard()->set_data('siteinfo',$siteinfo);
        }
    }

    private function preprocess_files()
    {
        $app = get_app();
        $upgrade_dir = $app->get_assetsdir().'/upgrade';
        if( !is_dir($upgrade_dir) ) throw new Exception(lang('error_internal',710));
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',711));

        $version_info = $this->get_wizard()->get_data('version_info');
        $versions = utils::get_upgrade_versions();
        if( $versions ) {
            $this->message(lang('preprocessing_files'));
            $smarty = smarty(); // in scope for inclusions
            foreach( $versions as $one_version ) {
                if( version_compare($one_version, $version_info['version']) < 1 ) continue;

                $pre_files = "$upgrade_dir/$one_version/preprocess_files.php";
                if( !is_file( $pre_files ) ) continue;

                $destdir = $destdir; // make sure it's in scope.
                include $pre_files;
            }
        }
    }

    private function do_manifests()
    {
        // get the list of all available versions that this upgrader knows about
        $app = get_app();
        $upgrade_dir =  $app->get_assetsdir().'/upgrade';
        if( !is_dir($upgrade_dir) ) throw new Exception(lang('error_internal',710));
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',711));

        $version_info = $this->get_wizard()->get_data('version_info');
        $versions = utils::get_upgrade_versions();
        if( $versions ) {
            $this->message(lang('processing_file_manifests'));
            foreach( $versions as $one_version ) {
                if( version_compare($one_version, $version_info['version']) < 1 ) continue;

                // open the manifest
                // check the to version info
                $manifest = new manifest_reader("$upgrade_dir/$one_version");
                if( $one_version != $manifest->to_version() ) {
                    throw new Exception(lang('error_internal',712));
                }

                // delete all 'deleted' files
                // if they are supposed to be in the installation, the copy from the archive
                // will restore them.
                $deleted = $manifest->get_deleted();
                $ndeleted = 0;
                $nfailed = 0;
                $nmissing = 0;
                if( $deleted ) {
                    foreach( $deleted as $rec ) {
                        $fn = "{$destdir}{$rec['filename']}";
                        if( !is_file($fn) ) {
                            $this->verbose("File $fn does not exist... but we planned to delete it anyway");
                            $nmissing++;
                        }
                        elseif( !is_writable($fn) ) {
                            $this->error("$file $fn is not writable, could not delete it");
                            $nfailed++;
                        }
                        elseif( is_dir($fn) ) {
                            if( utils::rrmdir($fn) ) {
                                $this->verbose('Removed directory: '.$fn);
                                $ndeleted++;
                            }
                            else {
                                $this->error('Problem removing directory: '.$fn);
                                $nfailed++;
                            }
                        }
                        else {
                            if( @unlink($fn) ) {
                                $this->verbose('Removed file: '.$fn);
                                $ndeleted++;
                            }
                            else {
                                $this->error('Problem deleting: '.$fn);
                                $nfailed++;
                            }
                        }
                    }
                }

                $this->message($ndeleted.' files/folders deleted for version '.$one_version.': '.$nmissing.' missing, '.$nfailed.' failed');
            }
        }
    }

    protected function display()
    {
        // here, we do either the upgrade, or the install stuff.
        parent::display();
        $action = $this->get_wizard()->get_data('action');
        $smarty = smarty();
        $smarty->assign('next_url',$this->get_wizard()->next_url());
        if( $action == 'freshen' ) {
            $smarty->assign('next_url',$this->get_wizard()->step_url(9));
        }
        $smarty->display('wizard_step7.tpl');
        flush();

        try {
            $tmp = $this->get_wizard()->get_data('version_info'); // extra validation
            if( $action == 'upgrade' && $tmp ) {
                $this->preprocess_files();
                $this->do_manifests();
                $this->do_files(true);
            }
            elseif( $action == 'freshen' ) {
                $this->do_files(true);
            }
            elseif( $action == 'install' ) {
                $this->do_files();
            }
            else {
                throw new Exception(lang('error_internal',705));
            }

            // create index.html files in directories.
            $this->do_index_html();
        }
        catch( Exception $e ) {
            $this->error($e->GetMessage());
        }

        $this->finish();
    }
} // class
