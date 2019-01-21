<?php

namespace __installer\wizard;

use __installer\install_filehandler;
use __installer\manifest_reader;
use __installer\utils;
use Exception;
use PharData;
use RecursiveIteratorIterator;
use function __installer\CMSMS\endswith;
use function __installer\CMSMS\lang;
use function __installer\CMSMS\smarty;
use function __installer\get_app;

class wizard_step7 extends wizard_step
{
    protected function process()
    {
        // nothing here
    }

    private function _createIndexHTML($filename)
    {
        touch($filename);
    }

    private function detect_languages()
    {
        $this->message(lang('install_detectlanguages'));
        $destdir = get_app()->get_destdir();

        $nlsdir = "$destdir/lib/nls";
        $pattern = "$nlsdir/*nls.php";
        $files = glob($pattern);
        if( !is_array($files) || count($files) == 0 ) throw new Exception(lang('error_internal',750));

        foreach( $files as &$one ) {
            $fn = basename($one);
            $one = substr($fn,0,strlen($fn)-strlen('.nls.php'));
        }
        return $files;
    }

    private function do_index_html()
    {
        $this->message(lang('install_dummyindexhtml'));

        $destdir = get_app()->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',751));
        $d2 = $destdir . '/'; //phar tarball uses / for filepath separator
        $archive = get_app()->get_archive();
        $phardata = new PharData($archive); // TODO ?? support fallback to e.g. TarArchive class
        $aname = basename($archive);
        $len = strlen($aname);
        foreach( new RecursiveIteratorIterator($phardata) as $file => $it ) {
            if( ($p = strpos($file,$aname)) === FALSE ) continue;
            $fn = substr($file,$p + $len);
            $dn = $destdir.dirname($fn);
            if( $dn == $destdir || $dn == $d2 ) continue; //has index.php
            if( $dn == "$destdir/admin" ) continue;
            if( is_dir($dn) ) {
                $idxfile = $dn.'/index.html';
                if( !is_file($idxfile) )  $this->_createIndexHTML($idxfile);
            }
        }
    }

    private function do_files($langlist = null)
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',601));

        $languages = ['en_US'];
        $siteinfo = $this->get_wizard()->get_data('siteinfo');
		if( $siteinfo !== NULL ) {
			//we're installing
            if( $siteinfo['languages'] ) $languages = array_merge($languages,$siteinfo['languages']);
            if( $langlist ) $languages = array_merge($languages,$langlist);
            $languages = array_unique($languages);
		}

        $filehandler = new install_filehandler();
        $filehandler->set_destdir($destdir);
        $filehandler->set_languages($languages);
        $filehandler->set_output_fn('__installer\wizard\wizard_step6::verbose');

        $from = $to = [];
        $app_config = $app->get_config();
        if( isset($app_config['admindir']) && ($aname = $app_config['admindir']) != 'admin' ) {
            $from[] = '/admin/';//hardcoded '/' filepath-separators in phar tarball
            $to[] = '/'.$aname.'/'; //these separators may be migrated, downstream
        }
        if( isset($app_config['assetsdir']) && ($aname = $app_config['assetsdir']) != 'assets' ) {
            $from[] = '/assets/';
            $to[] = '/'.$aname.'/';
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

        $archive = $app->get_archive();
        $phardata = new PharData($archive); //TODO ?? support fallback to e.g. TarArchive class
        $aname = basename($archive);
        $len = strlen($aname);

        foreach( new RecursiveIteratorIterator($phardata) as $pharfile=>$info ) {
           if( ($p = strpos($pharfile,$aname)) === FALSE ) continue;
			if( strpos($pharfile,'modules',$p) !== FALSE ) {
				$ufile = strtr($pharfile,'\\','/');
				if( ($up = strpos($ufile,'/lib/modules/',$p)) !== FALSE ) {
					if( endswith($pharfile,'.module.php') ) {
						$parts = explode('/',substr($ufile,$up + 13));
						$allmodules[] = $parts[0];
					}
				}
				elseif( ($up = strpos($ufile,'/assets/modules/',$p)) !== FALSE ) {
					if( !($xmodules === NULL || $xmodules) ) continue; //no non-core modules used
					$parts = explode('/',substr($ufile,$up + 16));
					if( !$parts[0] || ($xmodules !== NULL && !in_array($parts[0],$xmodules)) ) continue; //this one not used
					if( endswith($pharfile,'.module.php') ) {
						$allmodules[] = $parts[0];
					}
				}
			}
            $spec = substr($pharfile,$p + $len); //retains leading separator
            if( $from ) {
                $spec = str_replace($from,$to,$spec);
            }
            $filehandler->handle_file($spec,$pharfile);
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
                        if( !file_exists($fn) ) {
                            $this->verbose("File $fn does not exist... but we planned to delete it anyway");
                            $nmissing++;
                        }
                        else if( !is_writable($fn) ) {
                            $this->error("$file $fn is not writable, could not delete it");
                            $nfailed++;
                        }
                        else {
                            if( is_dir($fn) ) {
                                if( is_file($fn.'/index.html') ) @unlink($fn.'/index.html');
                                $res = utils::rrmdir($fn);
                                if( !$res ) {
                                    $this->error('Problem removing directory: '.$fn);
                                    $nfailed++;
                                } else {
                                    $this->verbose('Removed directory: '.$fn);
                                    $ndeleted++;
                                }
                            }
                            else {
                                $res = @unlink($fn);
                                if( !$res ) {
                                    $this->error("Problem deleting: $fn");
                                    $nfailed++;
                                }
                                else {
                                    $this->verbose('Removed file: '.$fn);
                                    $ndeleted++;
                                }
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

        // create index.html files in directories.
        try {
            include_once dirname(__DIR__,2).'/msg_functions.php';
            $tmp = $this->get_wizard()->get_data('version_info');
            if( $action == 'upgrade' && $tmp ) {
                $languages = $this->detect_languages();
                $this->preprocess_files();
                $this->do_manifests();
                $this->do_files($languages);
            }
            else if( $action == 'freshen' ) {
                $inst_languages = $this->detect_languages();
                $this->do_files($inst_languages);
            }
            else if( $action == 'install' ) {
                $this->do_files();
            }
            else {
                throw new Exception(lang('error_internal',705));
            }

            $this->do_index_html();
        }
        catch( Exception $e ) {
            $this->error($e->GetMessage());
        }

        $this->finish();
    }
} // class
