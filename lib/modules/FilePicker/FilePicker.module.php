<?php
/*
FilePicker - a CMSMS module which provides file-related services for the website
Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\contenttypes\ContentBase;
use CMSMS\FilePicker as IFilePicker;
use CMSMS\FilePickerProfile;
use CMSMS\FileType;
use CMSMS\FileTypeHelper;
use FilePicker\ProfileDAO;
use FilePicker\TemporaryProfileStorage;
use FilePicker\Utils;

final class FilePicker extends CMSModule implements IFilePicker
{
    protected $_dao;
    protected $_typehelper;

    public function __construct()
    {
        parent::__construct();
        $this->_dao = new ProfileDAO( $this );
        $this->_typehelper = new FileTypeHelper( cms_config::get_instance() );
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
        return CmsApp::get_instance()->GetSmarty();
    }
    /*
     * end of private methods
     */

    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'extensions'; }
    public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetVersion() { return '2.0'; }
    public function HasAdmin() { return true; }
    public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

    public function HasCapability( $capability, $params = [] )
    {
        switch( $capability ) {
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
     * @since 2.3
     * @return string
     */
    protected function HeaderJsContent() : string
    {
        $url = str_replace('&amp;','&',$this->get_browser_url()).'&'.CMS_JOB_KEY.'=1';
        $prompt = $this->Lang('select_file');
        $local = cms_join_path($this->GetModulePath(),'lib','js');
        $jsurl = cms_get_script('jquery.cmsms_filepicker.js',true,[$local]);
        return <<<EOS
<script type="text/javascript">
//<![CDATA[
 cms_data.filepicker_url = '$url';
 cms_data.lang_select_file = '$prompt';
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

        $profile_name = get_parameter_value($params,'profile');
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
     *
     * @param string $path Optional
     * @return array
     */
    public function GetFileList($path = '')
    {
        return Utils::get_file_list($path);
    }

    /**
     *
     * @param string $profile_name or NULL?
     * @param mixed $dir Optional
     * @param mixed $uid Optional
     * @return FilePickerProfile
     */
    public function get_profile_or_default( $profile_name, $dir = null, $uid = null )
    {
        $profile_name = trim($profile_name);
        if( $profile_name ) $profile = $this->_dao->loadByName( $profile_name );
        else $profile = null;
        if( !$profile ) {
            $profile = $this->get_default_profile( $dir, $uid );
        }
        return $profile;
    }

    /**
     *
     * @param mixed $dir Optional UNUSED
     * @param mixed $uid Optional UNUSED
     * @return FilePickerProfile
     */
    public function get_default_profile( $dir = null, $uid = null )
    {
        /* $dir is absolute */
        $profile = $this->_dao->loadDefault();
        if( !$profile ) {
            $profile = new FilePickerProfile();
        }
        return $profile;
    }

    /**
     *
     * Generate URL which initiates this module's filepicker action
     * @return string
     */
    public function get_browser_url()
    {
        return $this->create_url('m1_','filepicker');
    }

    /**
     * Generate page content for an input-text element which with ancillaries
     * which support file picking. Associated js is pushed into the page footer.
     * @staticvar boolean $first_time
     * @param string $name the name-attribute of the element
     * @param string $value the initial value of the element
     * @param FilePickerProfile $profile
     * @param bool $required Optional flag, whether some choice must be entered, default false
     * @return string
     */
    public function get_html( $name, $value, $profile, $required = false )
    {
        static $first_time = true;

//        $inst = 'i'.uniqid();
        if( $value === '-1' ) $value = null;

        // store the profile as a 'useonce' and add its signature to the params on the url
//        $sig = TemporaryProfileStorage::set( $profile );
        $inst = 'i'.TemporaryProfileStorage::set($profile);

        $helper = new FileTypeHelper();
        $mime = $helper->get_file_type_mime((int)$profile->type); //NOTE ->type should never be string-form
        $exts = $helper->get_file_type_extensions((int)$profile->type);

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
        if ($mime) {
            $mime = rawurlencode($mime);
        }
        if ($exts) {
            $extparm = rawurlencode(implode(',', $exts));
        } else {
            $extparm = '';
        }
        // where to go to generate the browse/select page content
        $url = str_replace('&amp;', '&', $this->get_browser_url()).'&'.CMS_JOB_KEY.'=1';

        if( $first_time ) {
            $first_time = false;
            //DEBUG
            $js = '<script type="text/javascript" src="'.$this->GetModuleURLPath().'/lib/js/jquery.cmsms_filepicker.min.js"></script>'."\n";
            //otherwise mebbe merge the file in with all used for the current-request ?
            //$combiner = CmsApp::get_instance()->GetScriptManager();
            //$combiner->queue_file($thejsfile, 2);
        }
        else {
            $js = '';
        }

//  param_sig: '$sig',
        $js .= <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
 $('input[data-cmsfp-instance="$inst"]').filepicker({
  title: '$title',
  param_sig: '$inst',
  required: $req,
  btn_label: '$title',
  remove_label: '$s1',
  remove_title: '$s1',
  param_mime: '$mime',
  param_extensions: '$extparm',
  url: '$url'
 });
});
//]]>
</script>

EOS;
        add_page_foottext($js);

        $smarty = CmsApp::get_instance()->GetSmarty();
        $tpl = $smarty->createTemplate($this->GetTemplateResource('contentblock.tpl'), null, null, $smarty);
        $tpl->assign('blockName', $name)
         ->assign('value', $value)
         ->assign('required', $required)
         ->assign('instance', $inst);
        return $tpl->fetch();
    }

    /**
     * utility function
     * @param type $filespec
     * @return bool
     */
    public function is_image( $filespec )
    {
        $filespec = trim($filespec);
        if( $filespec ) return $this->_typehelper->is_image( $filespec );
        return false;
    }

    /**
     * utility function
     * @param FilePickerProfile $profile
     * @param string $filepath
     * @return boolean
     */
    public function is_acceptable_filename( $profile, $filepath )
    {
        $filepath = trim($filepath);
        $filename = basename($filepath);
        if( !$filename ) return false;
        if( !$profile->show_hidden && (startswith($filename,'.') || startswith($filename,'_') || $filename == 'index.html') ) { return false; }
        if( $profile->match_prefix ) {
            //TODO support wildcards, regex match
            if( !startswith( $filename, $profile->match_prefix) ) { return false; }
        }
        if( $profile->exclude_prefix ) {
            //TODO support wildcards, regex match
            if( startswith( $filename, $profile->exclude_prefix) ) { return false; }
        }
        switch( $profile->type ) {
        case FileType::IMAGE:
            return $this->_typehelper->is_image( $filepath );

        case FileType::AUDIO:
            return $this->_typehelper->is_audio( $filepath );

        case FileType::VIDEO:
            return $this->_typehelper->is_video( $filepath );

        case FileType::MEDIA:
            return $this->_typehelper->is_media( $filepath );

        case FileType::XML:
            return $this->_typehelper->is_xml( $filepath );

        case FileType::DOCUMENT:
            return $this->_typehelper->is_document( $filepath );

        case FileType::ARCHIVE:
            return $this->_typehelper->is_archive( $filepath );
        }

        // passed
        return true;
    }
} // class
