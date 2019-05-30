<?php
# ModuleManager class: module import/export operations
# Copyright (C) 2011-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace ModuleManager;

use cms_config;
use CmsFileSystemException;
use CmsInvalidDataException;
use CmsLogicException;
use CMSModule;
use CMSMS\FileTypeHelper;
use CMSMS\internal\global_cache;
use CMSMS\ModuleOperations;
use ModuleManager;
use RuntimeException;
use XMLWriter;
use const CMS_VERSION;
use const TMP_CACHE_LOCATION;
use function audit;
use function cms_join_path;
use function cms_module_places;
use function cmsms;
use function file_put_contents;
use function get_recursive_file_list;
use function lang;
use function recursive_delete;
use function startswith;

class operations
{
    /**
     * @ignore
     */
    const MODULE_DTD_VERSION = '1.4';
    const MODULE_DTD_MINVERSION = '1.3';
    const MODULE_DTD = '
 <!ELEMENT module (dtdversion,name,version,mincmsversion,core?,help?,about?,description?,requires*,file+)>
 <!ELEMENT dtdversion (#PCDATA)>
 <!ELEMENT name (#PCDATA)>
 <!ELEMENT version (#PCDATA)>
 <!ELEMENT mincmsversion (#PCDATA)>
 <!ELEMENT help (#PCDATA)>
 <!ELEMENT about (#PCDATA)>
 <!ELEMENT description (#PCDATA)>
 <!ELEMENT core (#PCDATA)>
 <!ELEMENT requires (requiredname,requiredversion)>
 <!ELEMENT requiredname (#PCDATA)>
 <!ELEMENT requiredversion (#PCDATA)>
 <!ELEMENT file (filename,isdir?,istext?,data)>
 <!ELEMENT filename (#PCDATA)>
 <!ELEMENT isdir (#PCDATA)>
 <!ELEMENT istext (#PCDATA)>
 <!ELEMENT data (#PCDATA)>
';

    /**
     * @ignore
     */
    private $_mod;

    /**
     * @ignore
     */
    private $xml_exclude_files = ['^\.svn' , '^CVS$' , '^\#.*\#$' , '~$', '\.bak$', '^\.git', '^\.tmp$'];

    public function __construct( ModuleManager $mod )
    {
        $this->_mod = $mod;
    }

    /**
     * Unpackage a module from an xml string
     * Does not touch the database
     *
     * @internal
     * @param string $xmlfile The filepath of uploaded xml file containing data for the package
     * @param bool $overwrite Should we overwrite files if they exist?
     * @param bool $brief If set to true, less checking is done and no errors are returned
     *
     * @return array A hash of details about the installed module (if it returns at all)
     * @throws CmsInvalidDataException
     * @throws CmsFileSystemException
     * @throws CmsLogicException
     * @throws RuntimeException
     */
    public function expand_xml_package( $xmlfile, $overwrite = false, $brief = false )
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($xmlfile, 'SimpleXMLElement', LIBXML_NOCDATA);
        if( $xml === false ) {
            $val = $this->_mod->Lang('err_xml_open');
            foreach( libxml_get_errors() as $error ) {
                $val .= "\n".'Line '.$error->line.': '.$error->message;
            }
            libxml_clear_errors();
            throw new CmsInvalidDataException($val);
        }

        $val = trim((string)$xml->dtdversion);
        if( !$val || version_compare($val,self::MODULE_DTD_MINVERSION) < 0 ) {
            throw new CmsInvalidDataException($this->_mod->Lang('err_xml_dtdmismatch'));
        }
        $dtdversion = $val;
        $current = (version_compare($val,self::MODULE_DTD_VERSION) == 0);
        $coremodule = (string)$xml->core; //'1', '0' or ''
        $modops = ModuleOperations::get_instance();
        $moduledetails = [];
        $filedone = false;

        foreach( $xml->children() as $node ) {
            $key = $node->getName();
            $lkey = strtolower($key);
            switch( $lkey ) {
                case 'name':
                    $val = (string)$node;
                    // check if this module is already installed
                    $loaded = $modops->GetLoadedModules();
                    if( isset($loaded[$val]) && !$overwrite && !$brief ) { //TODO check logic
                        throw new CmsLogicException($this->_mod->Lang('err_xml_moduleinstalled'));
                    }
                    $moduledetails[$lkey] = $val;
                    break;
                case 'version':
                    $val = (string)$node;
                    $tmpinst = $modops->get_module_instance($moduledetails['name']);
                    if( !$brief && $tmpinst ) {
                        $version = $tmpinst->GetVersion();
                        if( version_compare($val,$version) < 0 ) {
                            throw new RuntimeException($this->_mod->Lang('err_xml_oldermodule'));
                        }
                        elseif( version_compare($val,$version) == 0 && !$overwrite ) {
                            throw new RuntimeException($this->_mod->Lang('err_xml_sameversion'));
                        }
                    }
                    $moduledetails[$lkey] = $val;
                    break;
                case 'mincmsversion':
                    $val = (string)$node;
                    if( !$brief && version_compare(CMS_VERSION,$val) < 0 ) {
                         throw new CmsLogicException($this->_mod->Lang('err_xml_moduleincompatible'));
                    }
                    $moduledetails[$lkey] = $val;
                    break;
                case 'requires':
                    $reqs = [];
                    foreach( $node->children() as $one ) {
                        $reqs['name'][] = (string)$one->requiredname;
                        $reqs['version'][] = (string)$one->requiredversion;
                    }
                    $moduledetails[$lkey] = $reqs; //upstream validation?
                    break;
                case 'help':
                case 'about':
                case 'description':
                    $moduledetails[$lkey] = ( $current ) ?
                      htmlspecialchars_decode((string)$node) : base64_decode((string)$node);
                    break;
                case 'file':
                    if( !$filedone ) {
                        $arr = cms_module_places($moduledetails['name']);
                        if( empty($arr) ) {
                            // confirm we can write to the module directory
                            $arr = cms_module_places(); //at least 2 folders
                            if( $coremodule ) {
                                $dir = $arr[0];
                            }
                            elseif( $coremodule === '0' || ($coremodule === '' && $dtdversion == '1.3' || !isset($arr[2])) ) {
                                if( strpos((string)$node->filename, 'assets') === false ) {
                                    $dir = $arr[0]; //core place
                                } else {
                                    $dir = $arr[1]; //non-core place
                                }
                            }
                            else {
                                $dir = $arr[2]; //deprecated place
                            }
                            if( !is_writable( $dir ) ) {
                                throw new CmsFileSystemException(lang('errordirectorynotwritable'));
                            }
                            $basepath = $dir . DIRECTORY_SEPARATOR . $moduledetails['name'];
                            if( !( is_dir( $basepath ) || @mkdir( $basepath, 0771, true ) ) ) {
                                throw new CmsFileSystemException(lang('errorcantcreatefile').': '.$basepath);
                            }
                        }
                        else {
                            //already installed somewhere(s) - use same place
                            if( count($arr) == 1 ) {
                                $basepath = dirname($arr[0]);
                            } else {
                                $t0 = filemtime($arr[0]);
                                $t1 = filemtime($arr[1]);
                                if( $t0 >= $t1 ) {
                                    $basepath = dirname($arr[0]);
                                    recursive_delete(dirname($arr[1]));
                                }
                                else {
                                    $basepath = dirname($arr[1]);
                                    recursive_delete(dirname($arr[0]));
                                }
                            }
                            if( !is_writable( $basepath ) ) {
                                throw new CmsFileSystemException(lang('errordirectorynotwritable'));
                            }
                        }
                        $from = ['\\','/'];
                        $to = [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR];
                        $aname = cms_config::get_instance()['assets_dir'];
                        if( $aname != 'assets' ) {
                            $from[] = 'assets';
                            $to[] = $aname;
                        }
                        $filedone = true;
                    }
                    //'filename' value is a relative path (DTD_VERSION 1.4+) or absolute (DTD_VERSION 1.3)
                    $val = (string)$node->filename;
                    if( $dtdversion == '1.3') {
                        if( $val[0] == '/' || $val[0] == '\\' ) {
                            $val = substr($val, 1); //relativize old-format
                            if( !$val) {
                                break; //no need to handle module-root-dir here
                            }
                        }
                    }
                    $name = str_replace($from, $to, $val);
                    $path = $basepath . DIRECTORY_SEPARATOR . $name;
                    if( (string)$node->isdir ) {
                        if( !( is_dir( $path ) || @mkdir( $path, 0771, true ) ) ) {
                            throw new CmsFileSystemException(lang('errorcantcreatefile').': '.$path);
                        }
                    }
                    elseif( (string)$node->istext ) {
                        if( @file_put_contents($path, htmlspecialchars_decode((string)$node->data), LOCK_EX) === false ) {
                            throw new CmsFileSystemException(lang('errorcantcreatefile').': '.$path);
                        }
                    }
                    elseif( @file_put_contents($path, base64_decode((string)$node->data), LOCK_EX) === false) {
                        throw new CmsFileSystemException(lang('errorcantcreatefile').': '.$path);
                  }
                  break;
            }
        }

        $moduledetails['size'] = filesize($xmlfile);

        if( !$brief ) audit('','Module', 'Expanded module: '.$moduledetails['name'].' version '.$moduledetails['version']);

        return $moduledetails;
    }

    /**
     * generate xml representing all the content of the specified module
     * @internal
     * @param CMSModule $modinstance
     * @param string $message for returning
     * @param int $filecount for returning
     * @return string output filepath
     * @throws CmsFileSystemException
     */
    public function create_xml_package( CMSModule $modinstance, &$message, &$filecount )
    {
        $dir = $modinstance->GetModulePath();
        if( !is_writable( $dir ) ) throw new CmsFileSystemException(lang('errordirectorynotwritable'));
/*
        // generate a moduleinfo.ini file, if N/A now
		$fn = $dir.'/moduleinfo.ini';
		if( !is_file($fn) ) {
	        ModuleOperations::get_instance()->generate_moduleinfo($modinstance);
//			global_cache::release('modules');
//			global_cache::release('module_deps');
//			global_cache::release('module_meta');
//			global_cache::release('module_plugins');
		}
*/
        $xw = new XMLWriter();
        $outfile = cms_join_path(TMP_CACHE_LOCATION,'module'.cms_utils::hash_string($dir).'.xml');
        @unlink($outfile);
        $xw->openUri('file://'.$outfile);
//        $xw->openMemory();
        $xw->setIndent(true);
        $xw->setIndentString("\t");
        $xw->startDocument('1.0', 'UTF-8');

        $xw->writeDtd('module', null, null, self::MODULE_DTD);
        $xw->startElement('module');

        $xw->writeElement('dtdversion', self::MODULE_DTD_VERSION);
        $xw->writeElement('name', $modinstance->GetName());
        $xw->writeElement('version', $modinstance->GetVersion());
        $xw->writeElement('mincmsversion', $modinstance->MinimumCMSVersion());
        $text = $modinstance->GetHelpPage();
        if( $text != '' ) {
            $xw->startElement('help');
            $xw-> writeCdata(htmlspecialchars($text, ENT_XML1, '', false));
            $xw->endElement();
        }
        $text = $modinstance->GetAbout();
        if( $text != '' ) {
            $xw->startElement('about');
            $xw-> writeCdata(htmlspecialchars($text, ENT_XML1, '', false));
            $xw->endElement();
        }
        $text = $modinstance->GetAdminDescription();
        if( $text != '' ) {
            $xw->startElement('description');
            $xw-> writeCdata(htmlspecialchars($text, ENT_XML1, '', false));
            $xw->endElement();
        }
        $arr = cms_module_places();
        if( startswith($dir, $arr[0]) ) {
            $xw->writeElement('core', 1);
        }
        elseif( startswith($dir, $arr[1]) ) {
            $xw->writeElement('core', 0);
        }

        $depends = $modinstance->GetDependencies();
        foreach( $depends as $key=>$val ) {
            $xw->startElement('requires');
            $xw->writeElement('requiredname', $key);
            $xw->writeElement('requiredversion', $val);
            $xw->endElement();
        }

        $len = strlen($dir) + 1; //preserve relative path only
        $config = cmsms()->GetConfig();
        $helper = new FileTypeHelper($config);
        $filecount = 0;
        $from = [DIRECTORY_SEPARATOR];
        $to = ['/'];
        $aname = $config['assets_dir'];
        if( $aname != 'assets' ) {
            $from[] = $aname;
            $to[] = 'assets';
        }

        // get a file list
        $items = get_recursive_file_list( $dir, $this->xml_exclude_files );
        foreach( $items as $file ) {
            // strip off the beginning
            $rel = substr($file,$len);
            if( $rel === false || $rel === '' ) continue;

            $xw->startElement('file');
            $xw->writeElement('filename', str_replace($from, $to, $rel));
            if( @is_dir( $file ) ) {
                 $xw->writeElement('isdir', 1);
            }
            else {
                $text = $helper->is_text($file);
                if( $text ) {
                    $xw->writeElement('istext', 1);
                }
                $xw->startElement('data');
                if( $text ) {
                    $xw->writeCdata(htmlspecialchars(file_get_contents($file), ENT_XML1));
                }
                else {
                    $xw->writeCdata(base64_encode(file_get_contents($file)));
                }
                $xw->endElement(); //data
            }
            $xw->endElement(); //file

            ++$filecount;
        }
        $xw->endElement(); //module
        $xw->endDocument();
        $xw->flush();

        $message = $this->_mod->Lang('xmlstatus', $modinstance->GetName(), $filecount);
        return $outfile;
    }
} // class
