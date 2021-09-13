<?php
/*
Class which supports searching in stylesheets.
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file AdminSearch.module.php
*/
namespace AdminSearch;

use CMSMS\Stylesheet;
use CMSMS\StylesheetOperations;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use const CMS_ROOT_PATH;
use function check_permission;
use function cms_relative_path;
use function get_secure_param;
use function get_userid;

final class Css_slave extends Base_slave
{
    public function get_name()
    {
        $mod = $this->get_mod();
        return $mod->Lang('lbl_css_search');
    }

    public function get_description()
    {
        $mod = $this->get_mod();
        return $mod->Lang('desc_css_search');
    }

    private function get_mod()
    {
        // static properties here >> SingleItem property|ies ?
        static $_mod;
        if (!$_mod) {
            $_mod = Utils::get_module('AdminSearch');
        }
        return $_mod;
    }

    public function check_permission()
    {
        $userid = get_userid();
        return check_permission($userid, 'Manage Stylesheets');
    }

    /**
     * @return array of arrays
     */
    public function get_matches()
    {
        // get all stylesheets' ids
        $db = SingleItem::Db();
        $sql = 'SELECT id FROM '.CMS_DB_PREFIX. StylesheetOperations::TABLENAME.' ORDER BY name';
        $all_ids = $db->getCol($sql);
        $output = [];

        if ($all_ids) {
            // get all stylesheets' props chunkwise
            $chunks = array_chunk($all_ids, 15);
            foreach ($chunks as $chunk) {
                $css_list = StylesheetOperations::get_bulk_stylesheets($chunk);
                foreach ($css_list as $css) {
                    $res = $this->get_css_match_info($css);
                    if ($res) {
                        $output[] = $res;
                    }
                }
            }
        }
        return $output;
    }

    private function get_css_match_info(Stylesheet $css)
    {
        $html = '';
        $name = $css->get_name();
        $html2 = $this->get_matches_info($name);
        if ($html2) {
            $html .= '<br />'.$html2;
        }
        $desc = $css->get_description();
        if ($desc && $this->search_descriptions()) {
            $html2 = $this->get_matches_info($desc);
            if ($html2) {
                $html .= '<br />'.$html2;
            }
        }
        $content = $css->get_content();
        $html2 = $this->get_matches_info($content);
        if ($html2) {
            $html .= '<br />'.$html2;
        }
        if (!$html) {
            return [];
        }
        $html = substr($html, 6); //strip leading newline

        if ($css->get_content_file()) {
            $file = $css->get_content_filename();
            $title = $name.' ('.cms_relative_path($file, CMS_ROOT_PATH).')';
        } else {
            $title = $name;
        }
        if ($this->check_permission()) {
            $urlext = get_secure_param();
            $one = $css->get_id();
            $url = 'editstylesheet.php'.$urlext.'&css='.$one;
        } else {
            $url = ''; // OR view-content URL?
        }
        $tmp = [
         'title' => $title, //TODO sanitize for presentation
         'description' => ($desc) ? $this->summarize($desc) : '', //TODO sanitize for presentation
         'edit_url' => $url,
         'text' => $html
        ];
        return $tmp;
    }
} // class
