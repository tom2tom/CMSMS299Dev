<?php
/*
ModuleManager class: module import/export operations
Copyright (C) 2011-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace ModuleManager; // the module-class

use CMSModule;
use CMSMS\AppSingle;
use CMSMS\Crypto;
use CMSMS\DataException;
use CMSMS\FileSystemException;
use CMSMS\FileTypeHelper;
use CMSMS\XMLErrorException;
use LogicException;
use ModuleManager;
use RuntimeException;
use UnexpectedValueException;
use XMLWriter;
use const CMS_VERSION;
use const TMP_CACHE_LOCATION;
use function audit;
use function cms_join_path;
use function cms_module_places;
use function file_put_contents;
use function get_recursive_file_list;
use function get_server_permissions;
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
     * @throws DataException
     * @throws FileSystemException
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
            throw new XMLErrorException($val);
        }

        $val = trim((string)$xml->dtdversion);
        if( !$val || version_compare($val,self::MODULE_DTD_MINVERSION) < 0 ) {
            throw new UnexpectedValueException($this->_mod->Lang('err_xml_dtdmismatch'));
        }
        $dtdversion = $val;
        $current = (version_compare($val,self::MODULE_DTD_VERSION) == 0);
        $coremodule = (string)$xml->core; //'1', '0' or ''
        $modops = AppSingle::ModuleOperations();
        $moduledetails = [];
        $filedone = false;
        $modes = get_server_permissions(); // might fail!
        $filemode = $modes[1]; // read + write
        $dirmode = $modes[3]; // read + write

        foreach( $xml->children() as $node ) {
            $key = $node->getName();
            $lkey = strtolower($key);
            switch( $lkey ) {
                case 'name':
                    $val = (string)$node;
                    // check if this module is already installed
                    $loaded = $modops->GetLoadedModules();
                    if( isset($loaded[$val]) && !$overwrite && !$brief ) { //TODO check logic
                        throw new LogicException($this->_mod->Lang('err_xml_moduleinstalled'));
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
                         throw new UnexpectedValueException($this->_mod->Lang('err_xml_moduleincompatible'));
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
                      htmlspecialchars_decode((string)$node, ENT_XML1/* | ENT_NOQUOTES*/ | ENT_SUBSTITUTE) : // NOT worth CMSMS\de_specialize
                      base64_decode((string)$node);
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
                                throw new FileSystemException(lang('errordirectorynotwritable'));
                            }
                            $basepath = $dir . DIRECTORY_SEPARATOR . $moduledetails['name'];
                            if( !(is_dir($basepath) || @mkdir($basepath, $dirmode, true)) ) {
                                throw new FileSystemException(lang('errorcantcreatefile').': '.$basepath);
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
                                throw new FileSystemException(lang('errordirectorynotwritable'));
                            }
                        }
                        $from = ['\\','/'];
                        $to = [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR];
                        $aname = AppSingle::Config()['assets_path'];
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
                        if( (is_dir($path) || @mkdir($path, $dirmode, true)) ) {
                            chmod($path, $dirmode); // in case refresh is needed
                        }
                        else {
                            throw new FileSystemException(lang('errorcantcreatefile').': '.$path);
                        }
                    }
                    elseif( (string)$node->istext ) {
                        $text = htmlspecialchars_decode((string)$node->data, ENT_XML1/* | ENT_NOQUOTES*/ | ENT_SUBSTITUTE, null, false);
                        if( @file_put_contents($path, $text, LOCK_EX) !== false ) {
                            chmod($path, $filemode);
                        }
                        else {
                            throw new FileSystemException(lang('errorcantcreatefile').': '.$path);
                        }
                    }
                    elseif( @file_put_contents($path, base64_decode((string)$node->data), LOCK_EX) !== false) {
                        chmod($path, $filemode);
                    }
                    else {
                        throw new FileSystemException(lang('errorcantcreatefile').': '.$path);
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
     * @param CMSModule  | IResource $modinst
     * @param string $message for returning
     * @param int $filecount for returning
     * @return string output filepath
     * @throws FileSystemException
     */
    public function create_xml_package($modinst, &$message, &$filecount )
    {
        $dir = $modinst->GetModulePath();
        if( !is_writable( $dir ) ) throw new FileSystemException(lang('errordirectorynotwritable'));
/*
        // generate a moduleinfo.ini file, if N/A now
        $fn = $dir.'/moduleinfo.ini';
        if( !is_file($fn) ) {
            AppSingle::ModuleOperations()->generate_moduleinfo($modinst);
//          $cache = AppSingle::SysDataCache();
//          $cache->release('modules');
//          $cache->release('module_deps');
//          $cache->release('module_plugins');
//          $cache->clear_cache();
        }
*/
        $xw = new XMLWriter();
        $outfile = cms_join_path(TMP_CACHE_LOCATION,'module'.Crypto::hash_string($dir).'.xml');
        @unlink($outfile);
        $xw->openUri('file://'.$outfile);
//        $xw->openMemory();
        $xw->setIndent(true);
        $xw->setIndentString("\t");
        $xw->startDocument('1.0', 'UTF-8');

        $xw->writeDtd('module', null, null, self::MODULE_DTD);
        $xw->startElement('module');

        $xw->writeElement('dtdversion', self::MODULE_DTD_VERSION);
        $xw->writeElement('name', $modinst->GetName());
        $xw->writeElement('version', $modinst->GetVersion());
        $xw->writeElement('mincmsversion', $modinst->MinimumCMSVersion());
        $text = $modinst->GetHelpPage();
        if( $text != '' ) {
            $xw->startElement('help');
            $xw-> writeCdata(htmlspecialchars($text, ENT_XML1/* | ENT_NOQUOTES*/ | ENT_SUBSTITUTE, null, false)); // NOT worth CMSMS\specialize
            $xw->endElement();
        }
        $text = $modinst->GetAbout();
        if( $text != '' ) {
            $xw->startElement('about');
            $xw-> writeCdata(htmlspecialchars($text, ENT_XML1/* | ENT_NOQUOTES*/ | ENT_SUBSTITUTE, null, false));
            $xw->endElement();
        }
        $text = $modinst->GetAdminDescription();
        if( $text != '' ) {
            $xw->startElement('description');
            $xw-> writeCdata(htmlspecialchars($text, ENT_XML1/* | ENT_NOQUOTES*/ | ENT_SUBSTITUTE, null, false));
            $xw->endElement();
        }
        $arr = cms_module_places();
        if( startswith($dir, $arr[0]) ) {
            $xw->writeElement('core', 1);
        }
        elseif( startswith($dir, $arr[1]) ) {
            $xw->writeElement('core', 0);
        }

        $depends = $modinst->GetDependencies();
        foreach( $depends as $key=>$val ) {
            $xw->startElement('requires');
            $xw->writeElement('requiredname', $key);
            $xw->writeElement('requiredversion', $val);
            $xw->endElement();
        }

        $len = strlen($dir) + 1; //preserve relative path only
        $helper = new FileTypeHelper();
        $filecount = 0;
        $from = [DIRECTORY_SEPARATOR];
        $to = ['/'];
        $aname = AppSingle::Config()['assets_path'];
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
                    $xw->writeCdata(htmlspecialchars(file_get_contents($file), ENT_XML1/* | ENT_NOQUOTES*/ | ENT_SUBSTITUTE, null, false));
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

        $message = $this->_mod->Lang('xmlstatus', $modinst->GetName(), $filecount);
        return $outfile;
    }
} // class
