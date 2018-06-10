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
use function cms_module_places;
use function cmsms;
use function get_recursive_file_list;
use function recursive_delete;
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
     * @param string $xmlfile The filepath of uploaded xml file containing data for the package
     * @param bool $overwrite Should we overwrite files if they exist?
     * @param bool $brief If set to true, less checking is done and no errors are returned
     * @return array A hash of details about the installed module
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

        $val = (string)$xml->dtdversion;
        if( !$val || version_compare($val,self::MODULE_DTD_MINVERSION) < 0 ) {
            throw new CmsInvalidDataException($this->_mod->Lang('err_xml_dtdmismatch'));
        }
        $current = (version_compare($val,self::MODULE_DTD_VERSION) == 0);

        $val = (string)$xml->core;
        // make sure that we can actually write to the module directory
        $dir = ( $val ) ? cms_join_path(CMS_ROOT_PATH,'lib','modules') : CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'modules';
        if( !is_writable( $dir ) ) throw new CmsFileSystemException(lang('errordirectorynotwritable'));

        $modops = \ModuleOperations::get_instance();
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
                        elseif ( version_compare($val,$version) == 0 && !$overwrite ) {
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
                    foreach ($node->children() as $one) {
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
	                    $basepath = $dir . DIRECTORY_SEPARATOR . $moduledetails['name'];
						$arr = cms_module_places($moduledetails['name']);
						if( !empty($arr) ) {
							//already installed
//							TODO always cleanup current files (& database?)
							if( $arr[0] != $basepath ) {
								recursive_delete($arr[0]);
							}
						}
						if( !( is_dir( $basepath ) || @mkdir( $basepath, 0771, true ) ) ) {
							throw new CmsFileSystemException(lang('errorcantcreatefile').': '.$basepath);
						}
						$filedone = true;
					}
                    //'filename' value is actually a relative path
					$name = strtr((string)$node->filename, [ '/' => DIRECTORY_SEPARATOR, '\\' => DIRECTORY_SEPARATOR]);
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
            $xw-> writeCdata(htmlspecialchars($text, ENT_XML1, '', false));
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
        $items = get_recursive_file_list( $dir, $this->xml_exclude_files );
        foreach( $items as $file ) {
            // strip off the beginning
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
