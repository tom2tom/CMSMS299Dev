<?php

namespace AdminSearch;

use CMSMS\Stylesheet;
use CMSMS\StylesheetOperations;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use const CMS_ROOT_PATH;
use function check_permission;
use function cms_relative_path;
use function cmsms;
use function get_userid;

final class css_slave extends slave
{
    public function get_name()
    {
        $mod = Utils::get_module('AdminSearch');
        return $mod->Lang('lbl_css_search');
    }

    public function get_description()
    {
        $mod = Utils::get_module('AdminSearch');
        return $mod->Lang('desc_css_search');
    }

    public function check_permission()
    {
        $userid = get_userid();
        return check_permission($userid,'Manage Stylesheets');
    }

    private function check_css_matches(Stylesheet $css)
    {
        if( strpos($css->get_name(),$this->get_text()) !== FALSE ) return TRUE;
        if( strpos($css->get_content(),$this->get_text()) !== FALSE ) return TRUE;
        if( $this->search_descriptions() && strpos($css->get_description(),$this->get_text()) !== FALSE ) return TRUE;
        return FALSE;
    }

    private function get_mod()
    {
        // static properties here >> StaticProperties class ?
        static $_mod;
        if( !$_mod ) $_mod = Utils::get_module('AdminSearch'); //TODO relevant module
        return $_mod;
    }

    private function get_css_match_info(Stylesheet $css)
    {
        $one = $css->get_id();
        $intext = $this->get_text();
        $text = '';
        $content = $css->get_content();
        $pos = strpos($content,$intext);
        if( $pos !== FALSE ) {
            $start = max(0,$pos - 50);
            $end = min(strlen($content),$pos+50);
            $text = substr($content,$start,$end-$start);
            $text = htmlentities($text);
            $text = str_replace($intext,'<span class="search_oneresult">'.$intext.'</span>',$text);
            $text = str_replace(["\r\n","\r","\n"],[' ',' ',' '],$text);
        }
        $url = $this->get_mod()->create_url( 'm1_','admin_edit_css','', [ 'css'=>$one ] );
        $url = str_replace('&amp;','&',$url);
        $title = $css->get_name();
        if( $css->get_content_file() ) {
            $file = $css->get_content_filename();
            $title = $css->get_name().' ('.cms_relative_path($file,CMS_ROOT_PATH).')';
        }
        $tmp = [
		 'title'=>$title,
         'description'=>tools::summarize($css->get_description()),
         'edit_url'=>$url,
         'text'=>$text
        ];
        return $tmp;
    }

    public function get_matches()
    {
        $db = cmsms()->GetDb();
//        $mod = $this->get_mod();
        // get all of the stylesheet ids
        $sql = 'SELECT id FROM '.CMS_DB_PREFIX. StylesheetOperations::TABLENAME.' ORDER BY name';
        $all_ids = $db->GetCol($sql);
        $output = [];
        if( $all_ids ) {
            $chunks = array_chunk($all_ids,15);
            foreach( $chunks as $chunk ) {
                $css_list = StylesheetOperations::get_bulk_stylesheets($chunk);
                foreach( $css_list as $css ) {
                    if( $this->check_css_matches($css) ) $output[] = $this->get_css_match_info($css);
                }
            }
        }
        return $output;
    }
} // class
