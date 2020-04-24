<?php

namespace cms_installer\wizard;

use cms_installer\install_filehandler;
use cms_installer\manifest_reader;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use function cms_installer\endswith;
use function cms_installer\get_app;
use function cms_installer\get_upgrade_versions;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\rrmdir;
use function cms_installer\smarty;

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
        if( !$files ) throw new Exception(lang('error_internal',700));

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
        if( !$destdir ) throw new Exception(lang('error_internal',701));
/*
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
*/
        $path = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'sources';
        $len = strlen($path); //each file's prefix-length
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS |
                FilesystemIterator::UNIX_PATHS
            ),
            RecursiveIteratorIterator::SELF_FIRST);
        foreach( $iter as $fp ) {
            $dn = $destdir.substr($fp, $len);
            if( is_dir($fp) ) {
                $ip = $dn.DIRECTORY_SEPARATOR;
            } else {
                $ip = dirname($dn).DIRECTORY_SEPARATOR;
            }
            if( !@is_file($ip.'index.php') ) {
                @touch($ip.'index.html'); //ignore failure
            }
        }
    }

    private function do_files(bool $checklangs = false)
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',702));

        $languages = ['en_US'];
        if( $checklangs ) { // upgrade or refresh
            try {
                $languages = array_merge($languages, $this->detect_languages());
            }
            catch (Throwable $t) {/* nothing here */}
        }
        $choices = $this->get_wizard()->get_data('sessionchoices');
        if( $choices && isset($choices['languages']) ) {
            $languages = array_merge($languages, $choices['languages']);
        }
        $languages = array_unique($languages);

        $filehandler = new install_filehandler();
        $filehandler->set_destdir($destdir);
        $filehandler->set_languages($languages);
        $filehandler->set_output_fn([$this,'verbose']);

        $from = $to = $lens = [];
        $app_config = $app->get_config();
        //we rename filepaths, not the actual folders followed by rename-back
        if( !empty($app_config['admin_path']) && ($aname = $app_config['admin_path']) != 'admin' ) {
            $s = '/admin'; //hardcoded '/' filepath-separators in phar tarball
            $from[] = $s;
            $to[] = '/'.$aname; //the separator may be migrated, downstream
            $lens[] = strlen($s);
        }
        if( !empty($app_config['assets_path']) && ($aname = $app_config['assets_path']) != 'assets' ) {
            $s = '/assets';
            $from[] = $s;
            $to[] = '/'.$aname;
            $lens[] = strlen($s);
        }
        if( !empty($app_config['simpletags_path']) ) {
            $aname = $app_config['simpletags_path'];
            $aname = strtr(trim($aname, ' \\/'), '\\', '/');
            if( !($aname == 'simple_plugins' || $aname == 'assets/simple_plugins') ) {
                $s = '/assets/simple_plugins';
                $from[] = $s;
                $to[] = '/'.$aname;
                $lens[] = strlen($s);
            }
        }

        $coremodules = $app_config['coremodules'];
        if( $choices && !empty($choices['wantedextras']) ) {
            $xmodules = $choices['wantedextras']; //non-core modules to be processed
            if( !is_array($xmodules) ) $xmodules = [$xmodules];
        }
        else {
            $xmodules = NULL;
        }

        $action = $this->get_wizard()->get_data('action');
        if( $action != 'install' ) {
            //add any installed non-core modules, which might need to be freshened ?
            // TODO anything not in $coremodules or $xmodules
            // in any module location
            $cfgfile = $destdir.DIRECTORY_SEPARATOR.'config.php';
            include_once $cfgfile;
            $s = ( !empty($config['assets_path']) ) ? $config['assets_path'] : 'assets';
            $fp = joinpath($destdir,$s,'modules','*','*.module.php');
            $paths = glob($fp);
            if( $paths ) {
                if( !$xmodules ) $xmodules = [];
                foreach($paths as $fp) {
                    $xmodules[] = basename($fp,'.module.php');
                }
                $xmodules = array_unique($xmodules);
            }
        }
        $havemodules = [];

        $this->message(lang('install_extractfiles'));

        list($iter,$topdir) = $app->setup_sources_scan();
        $len = strlen($topdir); //suffix retains leading separator

        foreach ($iter as $fn=>$fp) {
            if( strpos($fp,'modules') !== FALSE && endswith($fn,'.module.php') ) {
                $tmp = substr($fn,0,-11);
                if( in_array($tmp,$coremodules) ) {
                    array_unshift($havemodules,$tmp);
                }
                elseif( $xmodules && in_array($tmp,$xmodules) ) {
                    $havemodules[] = $tmp;
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

        if( $havemodules ) {
            if( !$choices ) $choices = [];
            $choices['havemodules'] = array_unique($havemodules);
            $this->get_wizard()->merge_data('sessionchoices',$choices);
        }
    }

    private function preprocess_files()
    {
        $upgrade_dir = dirname(__DIR__, 2).'/upgrade';
        if( !is_dir($upgrade_dir) ) throw new Exception(lang('error_internal',710));
        $destdir = get_app()->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',711));

        $version_info = $this->get_wizard()->get_data('version_info');
        $versions = get_upgrade_versions();
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
        $upgrade_dir = dirname(__DIR__,2).'/upgrade';
        if( !is_dir($upgrade_dir) ) throw new Exception(lang('error_internal',720));
        $destdir = get_app()->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',721));

        $version_info = $this->get_wizard()->get_data('version_info');
        $versions = get_upgrade_versions();
        if( $versions ) {
            $this->message(lang('processing_file_manifests'));
            foreach( $versions as $one_version ) {
                if( version_compare($one_version, $version_info['version']) < 1 ) continue;

                // open the manifest
                // check the to version info
                $manifest = new manifest_reader("$upgrade_dir/$one_version");
                if( $one_version != $manifest->to_version() ) {
                    throw new Exception(lang('error_internal',722));
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
                            if( rrmdir($fn) ) {
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
                throw new Exception(lang('error_internal',730));
            }

            // create index.html files in directories.
            $this->do_index_html();
        }
        catch( Throwable $t ) {
            $this->error($t->GetMessage());
        }

        $this->finish();
    }
} // class
