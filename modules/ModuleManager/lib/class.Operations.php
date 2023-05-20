<?php
/*
ModuleManager class: module import/export operations
Copyright (C) 2011-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
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
use CMSMS\Crypto;
use CMSMS\FileSystemException;
use CMSMS\FileTypeHelper;
use CMSMS\Lone;
use CMSMS\XMLException;
use LogicException;
use ModuleManager;
use RuntimeException;
use XMLWriter;
use const CMS_VERSION;
use const TMP_CACHE_LOCATION;
use function cms_join_path;
use function cms_module_places;
use function CMSMS\log_notice;
use function file_put_contents;
use function get_recursive_file_list;
use function get_server_permissions;
use function lang;
use function recursive_delete;

class Operations
{
    /**
     * @ignore
     */
    const MODULE_DTD_VERSION = '1.4';
    const MODULE_DTD_MINVERSION = '1.3';
    const MODULE_DTD = '
 <!ELEMENT module (dtdversion,name,version,mincmsversion,help?,about?,description?,requires*,file+)>
 <!ELEMENT dtdversion (#PCDATA)>
 <!ELEMENT name (#PCDATA)>
 <!ELEMENT version (#PCDATA)>
 <!ELEMENT mincmsversion (#PCDATA)>
 <!ELEMENT help (#PCDATA)>
 <!ELEMENT about (#PCDATA)>
 <!ELEMENT description (#PCDATA)>
 <!ELEMENT requires (requiredname,requiredversion)>
 <!ELEMENT requiredname (#PCDATA)>
 <!ELEMENT requiredversion (#PCDATA)>
 <!ELEMENT file (filename,isdir?,istext?,data)>
 <!ELEMENT filename (#PCDATA)>
 <!ELEMENT isdir (#PCDATA)>
 <!ELEMENT istext (#PCDATA)>
 <!ELEMENT data (#PCDATA)>
';
// core/non-core modules N/A ATM
// <!ELEMENT module (dtdversion,name,version,mincmsversion,core?,help?,about?,description?,requires*,file+)>
// <!ELEMENT core (#PCDATA)>

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
     * Un-package a module from an xml string
     * Does not touch the database
     *
     * @internal
     * @param string $xmlfile The filepath of uploaded xml file containing data for the package
     * @param bool $overwrite Optional flag, if true, overwrite existing files. Default false
     * @param bool $brief Optional flag, if true, less checking is done and no errors are returned. Default false
     * @param bool $meta Optional flag since 3.0, if true, do not process files in the package. Default false
     *
     * @return array A hash of details about the installed module (if it returns at all)
     * @throws XMLException or FileSystemException or LogicException or RuntimeException
     */
    public function expand_xml_package($xmlfile, $overwrite = false, $brief = false, $meta = false)
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($xmlfile, 'SimpleXMLElement', LIBXML_NOCDATA);
        if( $xml === false ) {
            $val = $this->_mod->Lang('err_xml_open');
            foreach( libxml_get_errors() as $error ) {
                $val .= "\n".'Line '.$error->line.': '.$error->message;
            }
            libxml_clear_errors();
            throw new XMLException($val);
        }

        $val = trim((string)$xml->dtdversion);
        if( !$val || version_compare($val,self::MODULE_DTD_MINVERSION) < 0 ) {
            throw new RuntimeException($this->_mod->Lang('err_xml_dtdmismatch'));
        }
        $dtdversion = $val;
        $current = (version_compare($val,self::MODULE_DTD_VERSION) == 0);
//      $coremodule = (string)$xml->core; //'1', '0' or ''
        $modops = Lone::get('ModuleOperations');
        $moduledetails = [];
        $filedone = false;
        $modes = get_server_permissions(); // might fail!
        $filemode = $modes[1]; // read + write
        $dirmode = $modes[3]; // read + write
        $alldirs = cms_module_places('', true);

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
                    if( !$brief ) {
                        $mod = $modops->get_module_instance($moduledetails['name']);
                        if( $mod ) {
                            $version = $mod->GetVersion();
                            if( version_compare($val,$version) < 0 ) {
                                throw new RuntimeException($this->_mod->Lang('err_xml_oldermodule'));
                            }
                            elseif( version_compare($val,$version) == 0 && !$overwrite ) {
                                throw new RuntimeException($this->_mod->Lang('err_xml_sameversion'));
                            }
                        }
                    }
                    $moduledetails[$lkey] = $val;
                    break;
                case 'mincmsversion':
                    $val = (string)$node;
                    if( !$brief && version_compare(CMS_VERSION,$val) < 0 ) {
                         throw new RuntimeException($this->_mod->Lang('err_xml_moduleincompatible'));
                    }
                    $moduledetails[$lkey] = $val;
                    break;
                case 'requires':
                    $reqs = [];
                    foreach( $node->children() as $one ) {
                        $reqs['name'][] = (string)$one->requiredname;
                        $reqs['version'][] = (string)$one->requiredversion;
                    }
                    $moduledetails[$lkey] = $reqs; //upstream validation? Forge/ModuleRepository processing?
                    break;
                case 'help':
                case 'about':
                case 'description':
                    $moduledetails[$lkey] = ( $current ) ?
                      htmlspecialchars_decode((string)$node, ENT_XML1/* | ENT_NOQUOTES*/ | ENT_SUBSTITUTE) : // NOT worth CMSMS\de_specialize
                      base64_decode((string)$node);
                    break;
                case 'file':
                    if( $meta ) { break; }
                    if( !$filedone ) {
                        $dirlist = cms_module_places($moduledetails['name']);
                        if( empty($dirlist) ) {
                            $basepath = $alldirs[0];
                        }
                        else {
                            $basepath = dirname($dirlist[0]);
                            recursive_delete($dirlist[0]);
                        }
                        $from = ['\\', '/'];
                        $to = [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR];
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

        if( !$brief ) log_notice('Module', 'Expanded module: '.$moduledetails['name'].' version '.$moduledetails['version']);

        return $moduledetails;
    }

    /**
     * Generate xml representing all the content of the specified module
     * ABANDONED including a metadata file (moduleinfo.ini) for Forge use
     * @internal
     * @param CMSModule  | IResource $mod
     * @param string $message for returning
     * @param int $filecount for returning
     * @return string output filepath
     * @throws FileSystemException
     */
    public function create_xml_package($mod, &$message, &$filecount )
    {
        $dir = $mod->GetModulePath();
        if( !is_writable( $dir ) ) throw new FileSystemException(lang('errordirectorynotwritable'));
/*
        // generate a moduleinfo.ini file, if N/A now
        $fn = $dir.'/moduleinfo.ini';
        if( !is_file($fn) ) {
            Lone::get('ModuleOperations')->generate_moduleinfo($mod);
//          $cache = Lone::get('LoadedData');
//          $cache->refresh('modules');
//          $cache->refresh('module_deps');
//          $cache->refresh('module_plugins');
//          Lone::get('LoadedMetadata')->refresh('*');
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
        $xw->writeElement('name', $mod->GetName());
        $xw->writeElement('version', $mod->GetVersion());
        $xw->writeElement('mincmsversion', $mod->MinimumCMSVersion());
        $text = $mod->GetHelpPage();
        if( $text != '' ) {
            $xw->startElement('help');
            $xw-> writeCdata(htmlspecialchars($text, ENT_XML1/* | ENT_NOQUOTES*/ | ENT_SUBSTITUTE, null, false)); // NOT worth CMSMS\specialize
            $xw->endElement();
        }
        $text = $mod->GetAbout();
        if( $text != '' ) {
            $xw->startElement('about');
            $xw-> writeCdata(htmlspecialchars($text, ENT_XML1/* | ENT_NOQUOTES*/ | ENT_SUBSTITUTE, null, false));
            $xw->endElement();
        }
        $text = $mod->GetAdminDescription();
        if( $text != '' ) {
            $xw->startElement('description');
            $xw-> writeCdata(htmlspecialchars($text, ENT_XML1/* | ENT_NOQUOTES*/ | ENT_SUBSTITUTE, null, false));
            $xw->endElement();
        }

/*      $dirlist = cms_module_places('', true);
        if( !$dirlist[1] || startswith($dir, $dirlist[1]) ) { // TODO
            $xw->writeElement('core', 1);
        }
        elseif( !$dirlist[2] || startswith($dir, $dirlist[2]) ) { // TODO
            $xw->writeElement('core', 2);
        }
*/
        $depends = $mod->GetDependencies();
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
        $aname = Lone::get('Config')['assets_path'];
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

        $message = $this->_mod->Lang('xmlstatus', $mod->GetName(), $filecount);
        return $outfile;
    }
} // class
