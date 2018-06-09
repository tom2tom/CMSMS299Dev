<?php

namespace ModuleManager;

use CmsFileSystemException;
use CmsInvalidDataException;
use CmsLogicException;
use CMSModule;
use CMSMS\FileTypeHelper;
use ModuleManager;
use RuntimeException;
use XMLWriter;
use const CMS_ASSETS_PATH;
use const CMS_ROOT_PATH;
use const CMS_VERSION;
use const TMP_CACHE_LOCATION;
use function audit;
use function cms_join_path;
use function cmsms;
use function file_put_contents;
use function get_recursive_file_list;
use function is_base64;
use function lang;
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
    private $xml_exclude_files = array('^\.svn' , '^CVS$' , '^\#.*\#$' , '~$', '\.bak$', '^\.git', '^\.tmp$');

    public function __construct( ModuleManager $mod )
    {
        $this->_mod = $mod;
    }

    /**
     * Unpackage a module from an xml string
     * does not touch the database
     *
     * @internal
     * @param string $xmlfile The filepath of xml file containing data for the package
     * @param bool $overwrite Should we overwrite files if they exist?
     * @param bool $brief If set to true, less checking is done and no errors are returned
     * @return array A hash of details about the installed module
     */
    public function expand_xml_package( $xmlfile, $overwrite = false, $brief = false )
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($xmlfile, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            $e = $this->_mod->Lang('err_xml_open');
            foreach (libxml_get_errors() as $error) {
                $e .= "\n".'Line '.$error->line.': '.$error->message;
            }
            libxml_clear_errors();
            throw new CmsInvalidDataException($e);
        }

        $val = $xml->dtdversion;
        if ($val != self::MODULE_DTD_VERSION ) {
			//TODO self::MODULE_DTD_MINVERSION check
			throw new CmsInvalidDataException($this->_mod->Lang('err_xml_dtdmismatch'));
		}

        $val = $xml->core;
        // make sure that we can actually write to the module directory
        $dir = (val) ? cms_join_path(CMS_ROOT_PATH,'lib','modules') : CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'modules';
        if( !is_writable( $dir ) ) throw new CmsFileSystemException(lang('errordirectorynotwritable'));

        $moduledetails = [];
        $modops = \ModuleOperations::get_instance();

        foreach ($xml->children() as $node) {
            $key = $node->getName();
            $lkey = strtolower($key);
            switch ($lkey) {
                case 'name':
                    $val = $xml->$key;
                    // check if this module is already installed
                    $loaded = $modops->GetLoadedModules();
                    if( isset( $loaded[$val] ) && !$overwrite && !$brief ) {
                        throw new CmsLogicException($this->_mod->Lang('err_xml_moduleinstalled'));
                    }
                    $moduledetails[$lkey] = $val;
                    break;
                case 'version':
                    $val = $xml->$key;
                    $tmpinst = $modops->get_module_instance($moduledetails['name']);
                    if( !$brief && $tmpinst ) {
                        $version = $tmpinst->GetVersion();
                        if( version_compare($val,$version) < 0 ) {
                            throw new RuntimeException($this->_mod->Lang('err_xml_oldermodule'));
                        }
                        elseif ( version_compare($val,$version) == 0 && !$overwrite ) {
                            throw new RuntimeException($this->_mod->Lang('err_xml_sameversion'));
                        }
                    }
                    $moduledetails[$lkey] = $val;
                    break;
                case 'minversion':
                    $val = $xml->$key;
                    if( !$brief && version_compare(CMS_VERSION,$val) < 0 ) {
                         throw new CmsLogicException($this->_mod->Lang('err_xml_moduleincompatible'));
                    }
                    $moduledetails[$lkey] = $val;
                    break;
                case 'requires':
                    $reqs = [];
                    foreach ($node->children() as $one) {
                        $reqs['name'][] = $one->requiredname;
                        $reqs['version'][] = $one->requiredversion;
                    }
                    $moduledetails[$lkey] = $reqs;
                    break;
                case 'help':
                case 'about':
                case 'description':
                    $val = (string)$xml->$key; //strip the CDATA[[ ]] surrounds
                    $moduledetails[$lkey] = (is_base64($val)) ?
                         base64_decode($val) : htmlspecialchars_decode($val);
                    break;
                case 'file':
                    $basepath = $dir . DIRECTORY_SEPARATOR . $moduledetails['name'];
                    if( !( is_dir( $basepath ) || @mkdir( $basepath, 0771, true ) ) ) {
                        throw new CmsFileSystemException(lang('errorcantcreatefile').': '.$basepath);
                    }
                    foreach ($node->children() as $one) {
                        $name = $one->filename;
                        $path = $basepath . DIRECTORY_SEPARATOR . $name;
                        $val = $one->isdir;
                        if ($val) {
                            if( !( is_dir( $path ) || @mkdir( $path, 0771, true ) ) ) {
                                throw new CmsFileSystemException(lang('errorcantcreatefile').': '.$path);
                            }
                        }
                        else {
                            $val = $one->istext;
                            if( $val ) {
                                file_put_contents($path, htmlspecialchars_decode((string)$one->data));
                            }
                            else {
                                file_put_contents($path, base64_decode((string)$one->data));
                            }
                        }
                    }
                    break;
            }
        }

        $moduledetails['size'] = filesize($xmlfile);

/*  // ~~~~~~~~~~~ START OLD ~~~~~~~~~~~~~~~~~

        $reader = new \XMLReader();
        $ret = $reader->open($xmlfile);

        $requires = [];

        while( $reader->read() ) {
            switch($reader->nodeType) {
            case \XMLReader::ELEMENT:
                switch( strtoupper($reader->localName) ) {
                case 'NAME':
                    $reader->read();
                    $moduledetails['name'] = $reader->value;
                    $loaded = $modops->GetLoadedModules();
                    // check if this module is already installed
                    if( isset( $loaded[$moduledetails['name']] ) && $overwrite == 0 && $brief == 0 ) {
                        throw new \CmsLogicException($this->_mod->Lang('err_xml_moduleinstalled'));
                    }
                    break;

                case 'DTDVERSION':
                    $reader->read();
                    if( $reader->value != self::MODULE_DTD_VERSION ) throw new \CmsInvalidDataException($this->_mod->Lang('err_xml_dtdmismatch'));
                    $havedtdversion = true;
                    break;

                case 'VERSION':
                    $reader->read();
                    $moduledetails['version'] = $reader->value;
                    $tmpinst = $modops->get_module_instance($moduledetails['name']);
                    if( $tmpinst && $brief == 0 ) {
                        $version = $tmpinst->GetVersion();
                        if( version_compare($moduledetails['version'],$version) < 0 ) {
                            throw new \RuntimeException($this->_mod->Lang('err_xml_oldermodule'));
                        }
                        else if (version_compare($moduledetails['version'],$version) == 0 && $overwrite == 0 ) {
                            throw new \RuntimeException($this->_mod->Lang('err_xml_sameversion'));
                        }
                    }
                    break;

                case 'MINCMSVERSION':
                    $name = $reader->localName;
                    $reader->read();
                    if( $brief == 0 && version_compare(CMS_VERSION,$reader->value) < 0 ) {
                        throw new \CmsLogicException($this->_mod->Lang('err_xml_moduleincompatible'));
                    }
                    $moduledetails[$name] = $reader->value;
                    break;

                case 'MAXCMSVERSION':
                case 'DESCRIPTION':
                case 'FILENAME':
                case 'ISDIR':
                    $name = $reader->localName;
                    $reader->read();
                    $moduledetails[$name] = $reader->value;
                    break;

                case 'HELP':
                case 'ABOUT':
                    $name = $reader->localName;
                    $reader->read();
                    $moduledetails[$name] = base64_decode($reader->value);
                    break;

                case 'REQUIREDNAME':
                    $reader->read();
                    $requires['name'] = $reader->value;
                    break;

                case 'REQUIREDVERSION':
                    $reader->read();
                    $requires['version'] = $reader->value;
                    break;

                case 'DATA':
                    $reader->read();
                    $moduledetails['filedata'] = $reader->value;
                    break;
                }
                break;

            case \XMLReader::END_ELEMENT:
                switch( strtoupper($reader->localName) ) {
                case 'REQUIRES':
                    if( count($requires) != 2 ) continue;
                    if( !isset( $moduledetails['requires'] ) ) $moduledetails['requires'] = array();
                    $moduledetails['requires'][] = $requires;
                    $requires = array();
                    break;

                case 'FILE':
                    if( $brief != 0 ) continue;

                    // finished a first file
                    if( !isset( $moduledetails['name'] ) || !isset( $moduledetails['version'] ) ||
                        !isset( $moduledetails['filename'] ) || !isset( $moduledetails['isdir'] ) ) {
                        throw new \CmsInvalidDataException($this->Lang('err_xml_invalid'));
                        return false;
                    }

                    // ready to go
                    $moduledir=$dir.DIRECTORY_SEPARATOR.$moduledetails['name'];
                    $filename=$moduledir.$moduledetails['filename'];
                    if( !file_exists( $moduledir ) ) {
                        if( !@mkdir( $moduledir ) && !is_dir( $moduledir ) ) {
                            throw new \CmsFileSystemException(lang('errorcantcreatefile').': '.$moduledir);
                            break;
                        }
                    }
                    else if( $moduledetails['isdir'] ) {
                        if( !@mkdir( $filename ) && !is_dir( $filename ) ) {
                            throw new \CmsFileSystemException(lang('errorcantcreatefile').': '.$filename);
                            break;
                        }
                    }
                    else {
                        $data = $moduledetails['filedata'];
                        if( strlen( $data ) ) $data = base64_decode( $data );
                        $fp = @fopen( $filename, "w" );
                        if( !$fp ) throw new \CmsFileSystemException(lang('errorcantcreatefile').' '.$filename);
                        if( strlen( $data ) ) @fwrite( $fp, $data );
                        @fclose( $fp );
                    }
                    unset( $moduledetails['filedata'] );
                    unset( $moduledetails['filename'] );
                    unset( $moduledetails['isdir'] );
                    break;
                }
                break;
            }
        } // while

        $reader->close();

        // we've created the module's directory
        unset( $moduledetails['filedata'] );
        unset( $moduledetails['filename'] );
        unset( $moduledetails['isdir'] );

// ~~~~~~~~~~~ END OLD ~~~~~~~~~~~~~ */

        if( !$brief ) audit('','Module', 'Expanded module: '.$moduledetails['name'].' version '.$moduledetails['version']);

        return $moduledetails;
    }

    /**
     * generate xml representing all the content of the specified module
     * @param CMSModule $modinstance
     * @param string $message for returning
     * @param int $filecount for returning
     * @return string filepath
     * @throws CmsFileSystemException
     */
    public function create_xml_package( CMSModule $modinstance, &$message, &$filecount )
    {
        $dir = $modinstance->GetModulePath();
        if( !is_writable( $dir ) ) throw new CmsFileSystemException(lang('errordirectorynotwritable'));

        // generate the moduleinfo.ini file
        \ModuleOperations::get_instance()->generate_moduleinfo($modinstance);

        $xw = new XMLWriter();
        $outfile = cms_join_path(TMP_CACHE_LOCATION,'module'.md5($dir).'.xml');
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
            $xw-> writeCdata(htmlspecialchars($desc, ENT_XML1, '', false));
            $xw->endElement();
        }
		if( startswith($dir, CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib') ) {
	        $xw->writeElement('core', 1);
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
        // get a file list
        $files = get_recursive_file_list( $dir, $this->xml_exclude_files );
        foreach( $files as $file ) {
            // strip off the beginning (keep leading separator)
            $rel = substr($file,$len);
            if( $rel === false || $rel === '' ) continue;

            $xw->startElement('file');
            $xw->writeElement('filename', $rel);
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
                    $xw-> writeCdata(htmlspecialchars(file_get_contents($file), ENT_XML1));
                }
                else {
                    $xw-> writeCdata(base64_encode(file_get_contents($file)));
                }
                $xw->endElement(); //data
            }
            $xw->endElement(); //file

            ++$filecount;
        }
        $xw->endElement(); //module
        $xw->endDocument();
        $xw->flush();

        //TODO $this->_mod->Lang('  ', strlen($xmltxt), $filecount);
        $message = 'XML package of '.strlen($xmltxt).' bytes created for '.
            $modinstance->GetName().' including '.$filecount.' files';

//        return $xw->flush();
        return $outfile;
    }
} // class
