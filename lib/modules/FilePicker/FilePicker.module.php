<?php
/*
FilePicker - a CMSMS module which provides file-related services for the website
Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AppSingle;
use CMSMS\contenttypes\ContentBase;
use CMSMS\CoreCapabilities;
use CMSMS\FileType;
use CMSMS\FileTypeHelper;
use CMSMS\IFilePicker;
use FilePicker\Profile;
use FilePicker\ProfileDAO;
use FilePicker\TemporaryInstanceStorage;
use FilePicker\TemporaryProfileStorage;
use FilePicker\Utils;

final class FilePicker extends CMSModule implements IFilePicker
{
    public $_dao;
    public $_typehelper;

    public function __construct()
    {
        parent::__construct();
        $this->_dao = new ProfileDAO( $this );
        $this->_typehelper = new FileTypeHelper();
        //TODO process these as end-of-session (not end-of-request) cleanups
        $callable = TemporaryProfileStorage::get_cleaner();
        register_shutdown_function($callable);
        $callable = TemporaryInstanceStorage::get_cleaner();
        register_shutdown_function($callable);
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
        return AppSingle::Smarty();
    }
    /*
     * end of private methods
     */

    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'siteadmin'; } //only the profiles stuff is present in admin UI
    public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetVersion() { return '3.0'; }
    public function HasAdmin() { return true; }
    public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

    public function HasCapability( $capability, $params = [] )
    {
        switch( $capability ) {
        case CoreCapabilities::CORE_MODULE:
        case 'contentblocks':
        case 'filepicker':
        case 'upload':
            return true;
        default:
            return false;
        }
    }

    /**
     * Generate page-header js. For use by relevant module actions.
     * Include after jQuery and core js.
     * @since 2.99
     * @return string
     */
    protected function HeaderJsContent() : string
    {
        $url1 = str_replace('&amp;','&',$this->get_browser_url()).'&'.CMS_JOB_KEY.'=1';
        $config = AppSingle::Config();
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
     *
     * @param type $blockName
     * @param type $value
     * @param array $params
     * @param bool $adding
     * @param ContentBase $content_obj
     * @return string
     */
    public function GetContentBlockFieldInput($blockName, $value, $params, $adding, ContentBase $content_obj)
    {
        if( empty($blockName) ) return false;
//        $uid = get_userid(false);
//        $adding = (bool)( $adding || ($content_obj->Id() < 1) ); // hack for the core. Have to ask why though (JM)

        $profile_name = $params['profile'] ?? '';
        $profile = $this->get_profile_or_default($profile_name);

        // TODO optionally allow further overriding the profile
        $out = $this->get_html($blockName, $value, $profile);
        return $out;
    }
/*
    function ValidateContentBlockFieldValue($blockName,$value,$blockparams,ContentBase $content_obj)
    {
        echo('<br />:::::::::::::::::::::<br />');
        debug_display($blockName, '$blockName');
        debug_display($value, '$value');
        debug_display($blockparams, '$blockparams');
        //debug_display($adding, '$adding');
        echo('<br />' . __FILE__ . ' : (' . self::class . ' :: ' . __FUNCTION__ . ') : ' . __LINE__ . '<br />');
        //die('<br />RIP!<br />');
    }
*/
    /**
     * Get a list of files in the prescribed folder (or else in the
     *  top-level accessible folder for the current user)
     * @param string $dirpath Optional filesystem path, absolute or relative
     * @return array, possibly empty
     */
    public function GetFileList( $dirpath = '' )
    {
        return Utils::get_file_list(null, $dirpath);
    }

    /**
     * Get the named profile, or otherwise a profile for the specified
     * folder and/or user.
     * @param mixed $profile_name string or falsy value
     * @param mixed $dirpath Optional filesystem path, absolute or relative
     * @param mixed $uid Optional user-identifier
     * @return Profile
     */
    public function get_profile_or_default( $profile_name, $dirpath = null, $uid = null )
    {
        return Utils::get_profile($profile_name, $dirpath, (int)$uid);
    }

    /**
     * Get a profile for the specified folder and/or user.
     * @param mixed $dirpath Optional top-directory for the profile. Default null hence top-level
     * @param mixed $uid Optional user id Default null hence current user
     * @return Profile
     */
    public function get_default_profile( $dirpath = null, $uid = null )
    {
        return Utils::get_profile_for($dirpath, (int)$uid);
    }

    /**
     * Generate the URL which initiates this module's filepicker action
     * @return string
     */
    public function get_browser_url()
    {
        return $this->create_url('m1_','filepicker');
    }

    /**
     * Generate page content for an input-text element with ancillaries
     * which support file picking. Associated js is pushed into the page footer.
     * @staticvar boolean $first_time
     * @param string $name the name-attribute of the element
     * @param string $value the initial value of the element
     * @param Profile $profile
     * @param bool $required Optional flag, whether some choice must be entered, default false
     * @return string
     */
    public function get_html( $name, $value, $profile, $required = false )
    {
        static $first_time = true;

        if( $value === '-1' ) { $value = null; }

        // store the profile as a 'useonce' and add its signature to the params on the url
        $inst = TemporaryProfileStorage::set($profile);
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
            $mime = '';
            $exts = '';
            break;
        }
        $title = $this->Lang($key);
         // CHECKME generated element also uses the html5 required-attribute
        $req = ( $required ) ? 'true':'false';
        $s1 = $this->Lang('clear');
/*
        if ($mime) {
            $mime = rawurlencode($mime); OR CMSMS\urlencode()
        }
        if ($exts) {
            $extparm = rawurlencode(implode(',', $exts)); OR CMSMS\urlencode()
        } else {
            $extparm = '';
        }
*/
        if( $first_time ) {
            $first_time = false;
            //mebbe merge the file in with all used for the current-request ?
            //$combiner = CMSMS\AppSingle::App()->GetScriptsManager();
            //$combiner->queue_matchedfile('jquery.cmsms_filepicker', 2);
            $jsurl = cms_get_script('jquery.cmsms_filepicker.js');
            $js = '<script type="text/javascript" src="'.$jsurl.'"></script>'.PHP_EOL;
        }
        else {
            $js = '';
        }

        // where to go to generate the browse/select page content
        $url = str_replace('&amp;', '&', $this->get_browser_url()).'&'.CMS_JOB_KEY.'=1';
// parameters now in profile identified by param_inst:
//  param_mime: '$mime',
//  param_extensions: '$extparm',
// CHECKME param_inst: '$inst',
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
        add_page_foottext($js);

        $smarty = AppSingle::Smarty();
        $tpl = $smarty->createTemplate($this->GetTemplateResource('contentblock.tpl')); //, null, null, $smarty);
        $tpl->assign([
         'blockName' => $name,
         'value' => $value,
         'required' => $required,
         'instance' => $inst,
         ]);
        return $tpl->fetch();
    }

    /**
     * Report whether the specified filename represents an image
     * @param string $filename
     * @return bool
     */
    public function is_image( $filename )
    {
        $filename = trim($filename);
        if( $filename ) return $this->_typehelper->is_image( $filename );
        return false;
    }

    /**
     * Report whether the specified filepath accords with the specified profile
     * @param Profile $profile
     * @param string $filepath
     * @return boolean
     */
    public function is_acceptable_filename( $profile, $filepath )
    {
        if( endswith($filepath,'index.html') || endswith($filepath,'index.php') ) return false;
        return $profile->is_file_name_acceptable($filepath);
    }
} // class
