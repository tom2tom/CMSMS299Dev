<?php
/*
FilePicker - a CMSMS module which provides file-related services for the website
Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
Copyright (C) 2016-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

//use CMSMS\FolderControls;
use CMSMS\CapabilityType;
use CMSMS\FileType;
use CMSMS\FileTypeHelper;
use CMSMS\FolderControlOperations;
use CMSMS\IFilePicker;
use CMSMS\Lone;
use FilePicker\Utils;
use function CMSMS\is_frontend_request;

final class FilePicker extends CMSModule implements IFilePicker
{
    public $_typehelper;

    public function __construct()
    {
        parent::__construct();
        $this->_typehelper = new FileTypeHelper();
    }

    private function _encodefilename($filename)
    {
        return str_replace('==', '', base64_encode($filename));
    }

    private function _decodefilename($encodedfilename)
    {
        return base64_decode($encodedfilename . '==');
    }

    private function _GetTemplateObject()
    {
        $ret = $this->GetActionTemplateObject();
        if( is_object($ret) ) return $ret;
        return Lone::get('Smarty');
    }
    /*
     * end of private methods
     */

    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetVersion() { return '2.0'; }
    public function HasAdmin() { return false; }
    public function MinimumCMSVersion() { return '2.999'; }
    public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

    public function HasCapability($capability, $params = [])
    {
        switch( $capability ) {
//abandoned        case CapabilityType::CORE_MODULE:
        case CapabilityType::CONTENT_BLOCKS:
        case 'filepicker':
        case 'upload':
            return true;
        default:
            return false;
        }
    }

    public function GetHelp() {
        //setup tag help TODO reconcile with InitializeFrontend()
        $this->CreateParameter('action', 'filepicker', $this->Lang('help_action'), false);
        $this->CreateParameter('content', 'false', $this->Lang('help_content'));
        $this->CreateParameter('name', '', $this->Lang('help_name'), false);
        $this->CreateParameter('profile', '', $this->Lang('help_profile'));
        $this->CreateParameter('type', 'IMAGE', $this->Lang('help_type'));
        $this->CreateParameter('value', '', $this->Lang('help_value'));
        return $this->Lang('help');
    }

    public function InitializeFrontend()
    {
        $this->SetParameterType([
        '_enc' => CLEAN_STRING,
        'cmd' => CLEAN_STRING,
        'content' => CLEAN_BOOL,
        'cwd' => CLEAN_STRING,
        'extensions' => CLEAN_STRING,
        'exts' => CLEAN_STRING, //not needed (bundled into '_enc')
        'inst' => CLEAN_STRING,
        'mime' => CLEAN_STRING,
        'name' => CLEAN_STRING,
        'nosub' => CLEAN_BOOL,
        'seldir' => CLEAN_STRING,
        'subdir' => CLEAN_STRING, //not needed (bundled into '_enc')
        'type' => CLEAN_STRING, //wanted FileType identifier e.g. ANY
        'val' => CLEAN_STRING, //URL parameter value
        'value' => CLEAN_STRING, //html-element initial value
        ]);
    }

    /**
     * Generate page-header js. For use by relevant module actions.
     * Include after jQuery and core js.
     * @since 2.0
     *
     * @return string
     */
    protected function HeaderJsContent() : string
    {
        $url1 = str_replace('&amp;', '&', $this->get_browser_url());
        $config = Lone::get('Config');
        $url2 = $config['uploads_url'];
        $max = $config['max_upload_size'];
        $choose = $this->Lang('choose');
        $errm = $this->Lang('error_upload_maxTotalSize');
        $choose2 = $this->Lang('select_file');
        $jsurl = cms_get_script('jquery.cmsms_filepicker.js');
        return <<<EOS
<script type="text/javascript">
//<![CDATA[
 if (!cms_data) { cms_data = {}; }
 $.extend(cms_data, {
  lang_choose = '$choose',
  lang_largeupload = '$errm',
  lang_select_file = '$choose2',
  filepicker_url = '$url1',
  uploads_url = '$url2',
  max_upload_size = $max
 };
//]]>
</script>
<script type="text/javascript" src="$jsurl"></script>

EOS;
    }

    /**
     * Get a list of files in the specified folder, or othewrwise in the
     *  top-level accessible folder for the current user
     * @param string $dirpath Optional filesystem path, absolute or relative
     * @return array, possibly empty
     */
    public function GetFileList($dirpath = '')
    {
        return Utils::get_file_list($this, null, $dirpath);
    }

    /**
     * Get the named profile, or otherwise a profile for the specified
     * folder and/or user.
     * @param mixed $profile_name string or falsy value
     * @param mixed $dirpath Optional filesystem path, absolute or relative
     * @param mixed $uid Optional user-identifier
     * @return FolderControls object
     */
    public function get_profile_or_default($profile_name, $dirpath = null, $uid = null)
    {
        return FolderControlOperations::get_profile($profile_name, $dirpath, (int)$uid);
    }

    /**
     * Get a profile for the specified folder and/or user.
     * @param mixed $dirpath Optional top-directory for the profile. Default null hence top-level
     * @param mixed $uid Optional user id Default null hence current user
     * @return FolderControls object
     */
    public function get_default_profile( $dirpath = null, $uid = null )
    {
        return FolderControlOperations::get_profile_for($dirpath, (int)$uid);
    }

    /**
     * Generate the URL which initiates this module's filepicker action
     * @return string (formatted for js use)
     */
    public function get_browser_url()
    {
        return $this->create_action_url('', 'filepicker', ['forjs'=>1, CMS_JOB_KEY=>1]);
    }

    /**
     * Generate page content for a profile-conformant input-text element
     * with file-pick ancillaries.
     * Backend for processing {content_module} tags.
     *
     * @param string $blockName Content block name, used here for
     *  name-attribute of created element
     * @param mixed  $value     Content block value, used for initial
     *  value of created element
     * @param array  $params    Associative array containing content
     *  block parameters, of which only 'profile', if any, is used here
     * @param bool $adding UNUSED whether the content editor is in create mode, otherwise edit mode
     * @param mixed $content_obj UNUSED The (possibly-unsaved) content object being edited.
     * @return string
     */
    public function GetContentBlockFieldInput($blockName, $value, $params, $adding, $content_obj)
    {
        if( !$blockName) return '';
//      $uid = get_userid(false);
//      if( !$adding && $content_obj->Id() < 1 ) { $adding = true; } // hack for the core. Have to ask why though (JM)

        $profile_name = $params['profile'] ?? '';
        $profile = $this->get_profile_or_default($profile_name);
        // TODO optionally allow further overriding the profile

        return $this->get_html($blockName, $value, $profile);
    }

    /**
     * Generate page content for an input-text element with ancillaries
     * which support file picking.
     * During admin requests, associated js is placed at page bottom
     *
     * @staticvar boolean $first_time
     * @param string $name the name-attribute of the element
     * @param string $value the initial value of the element
     * @param FolderControls $profile
     * @param bool $required Optional flag, whether some choice must be entered, default false
     * @return string
     */
    public function get_html($name, $value, $profile, $required = false)
    {
        static $first_time = true;

        if( $value === '-1' ) { $value = null; }

        // store the profile as a 'useonce' and add its signature to the params on the url
        $inst = FolderControlOperations::store_cached($profile);
/*
        $mime = $this->_typehelper->get_file_type_mime((int)$profile->type); //NOTE ->type should never be string-form
        $exts = $this->_typehelper->get_file_type_extensions((int)$profile->type);
*/
        switch( $profile->type ) {
        case FileType::IMAGE:
            $key = 'select_an_image';
            break;
        case FileType::AUDIO:
            $key = 'select_an_audio_file';
            break;
        case FileType::VIDEO:
            $key = 'select_a_video_file';
            break;
        case FileType::MEDIA:
            $key = 'select_a_media_file';
            break;
        case FileType::XML:
            $key = 'select_an_xml_file';
            break;
        case FileType::DOCUMENT:
            $key = 'select_a_document';
            break;
        case FileType::ARCHIVE:
            $key = 'select_an_archive_file';
            break;
//      case FileType::ANY:
        default:
            $key = 'select_a_file';
//            $mime = '';
//            $exts = '';
            break;
        }
        $title = $this->Lang($key);
         // CHECKME generated element also uses the html5 required-attribute
        $req = ( $required ) ? 'true':'false';
        $s1 = $this->Lang('clear');
/*
        if( $mime ) {
            $mime = rawurlencode($mime); OR CMSMS\urlencode()
        }
        if( $exts ) {
            $extparm = rawurlencode(implode(',', $exts)); OR CMSMS\urlencode()
        }
        else {
            $extparm = '';
        }
*/
        if( $first_time ) {
            $first_time = false;
            //mebbe merge the file in with all used for the current-request ?
            //$combiner = get_scripts_manager();
            //$combiner->queue_matchedfile('jquery.cmsms_filepicker', 2);
            $jsurl = cms_get_script('jquery.cmsms_filepicker.js');
            $js = '<script type="text/javascript" src="'.$jsurl.'"></script>'.PHP_EOL;
        }
        else {
            $js = '';
        }

        // where to go to generate the browse/select page content
        $url = $this->get_browser_url();
// parameters now in profile identified by param_inst:
//  param_mime: '$mime',
//  param_extensions: '$extparm',
//  param_type: '$type',
// TODO action.filepicker recognises type as a FileType-class value (int) or a corresponding name e.g. 'IMAGE'
// TODO if picker is used to populate a popup, document-ready N/A
        $js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 $('input[data-cmsfp-instance="$inst"]').filepicker({
  btn_label: '$title',
  remove_label: '$s1',
  remove_title: '$s1',
  required: $req,
  title: '$title',
  url: '$url'
 });
});
//]]>
</script>

EOS;
        $smarty = Lone::get('Smarty');
        $tpl = $smarty->createTemplate($this->GetTemplateResource('contentblock.tpl')); //, null, null, $smarty);
        $tpl->assign([
         'blockName' => $name,
         'value' => $value,
         'required' => $required,
         'instance' => $inst,
         ]);
        $out = $tpl->fetch();

        if( is_frontend_request() ) {
            return $out."\n".$js;
        }
        else {
            add_page_foottext($js);
            return $out;
        }
    }

    /**
     * Get data for a file-browse process
     * @since 2.0
     *
     * @param array $params assoc. array of values to be used
     * @param bool $framed optional flag whether to generate content
     *  for full-page(iframe), default true
     * @return 2-member array
     * [0] = page-header content (html) OR array of css or js filepaths to be loaded
     * [1] = page-bottom content (js) OR immediately-executable js
     */
    public function get_browsedata(array $params, bool $framed = true) : array
    {
        return Utils::get_browsedata($this, $params, $framed);
    }

    /**
     * Report whether the specified filename represents an image
     * @param string $filename
     * @return bool
     */
    public function is_image($filename)
    {
        $filename = trim($filename);
        if( $filename ) return $this->_typehelper->is_image($filename);
        return false;
    }

    /**
     * Report whether the specified filepath accords with the specified profile
     * @param FolderControls $profile
     * @param string $filepath
     * @param mixed $type optional limiter for the filetype(s) allowed by
     *  $profile, a FilteType enum (int) or corresponding identifier (string).
     *  Default 0 i.e. ignored.
     * @return boolean
     */
    public function is_acceptable_filename($profile, $filepath, $type = 0)
    {
        if( endswith($filepath, 'index.html') || endswith($filepath, 'index.php') ) {
            return false;
        }
        if( $type ) {
            if( is_numeric($type) ) {
                $itype = (int)$type;
                $stype = FileType::getName($itype);
                if( $stype === null ) { return false; } // unknown type
            }
            else {
                $itype = FileType::getValue($type);
                if( $itype === null ) { return false; } // unknown type
            }
            if( $itype != FILETYPE::ANY ) {
                $all = $this->_typehelper->get_file_type_extensions($itype);
                $ext = Utils::get_extension($filepath);
                if( !in_array($ext, $all) ) {
                    return false;
                }
            }
        }
        return FolderControlOperations::is_file_name_acceptable($profile, $filepath);
    }
} // class
