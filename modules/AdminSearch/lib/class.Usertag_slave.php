<?php
/*
Class which supports searching in UDT's.
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
See license details at the top of file AdminSearch.module.php
*/
namespace AdminSearch;

use CMSMS\UserTagOperations;
use CMSMS\Utils;
use const CMS_ROOT_PATH;
use function check_permission;
use function cms_relative_path;
use function get_secure_param;
use function get_userid;

final class Usertag_slave extends Base_slave
{
    private $ops; //populated before use

    public function get_name()
    {
        $mod = $this->get_mod();
        return $mod->Lang('lbl_udt_search');
    }

    public function get_description()
    {
        $mod = $this->get_mod();
        return $mod->Lang('desc_udt_search');
    }

//  public function use_slave(int $userid = 0) : bool {}

    protected function check_permission(int $userid = 0)
    {
        if ($userid == 0) { $userid = get_userid(); }
        return check_permission($userid, 'Modify User Plugins');
    }

    // @return array, containing arrays or empty
    public function get_matches()
    {
        $this->ops = new UserTagOperations();
        $results = $this->ops->ListUserTags(); //array, each member like $id => tagname, where $id <= -100 for UDTfiles
        $output = [];
        foreach ($results as $id => $tagname) {
            $res = $this->get_match_info($id,$tagname);
            if ($res) {
                $output[] = $res;
            }
        }
        return $output;
    }

    private function get_mod()
    {
        // static properties here >> Lone property|ies ?
        static $_mod;
        if (!$_mod) {
            $_mod = Utils::get_module('AdminSearch');
        }
        return $_mod;
    }

    /**
     *
     * @param string $tagname
     * @return array
     */
    private function get_match_info($id, $tagname)
    {
        $html = '';
        $html2 = $this->get_matches_info($tagname);
        if ($html2) {
            $html .= '<br />'.$html2;
        }
        $props = $this->ops->GetUserTag($tagname, 'description,code');
        $desc = $props['description'] ?? '';
        if ($desc && $this->search_descriptions()) {
            $html2 = $this->get_matches_info($desc);
            if ($html2) {
                $html .= '<br />'.$html2;
            }
        }
        $content = $props['code'] ?? '';
        $html2 = $this->get_matches_info($content);
        if ($html2) {
            $html .= '<br />'.$html2;
        }
        if (!$html) {
            return [];
        }
        $html = substr($html, 6); // strip leading newline

        if ($this->check_permission()) {
            $urlext = get_secure_param();
            $url = 'openusertag.php'.$urlext.'&tagname='.$tagname;
        } else {
            $url = '';// OR view-only url?
        }

        if ($this->ops->IsFileID($id)) {
            $file = $this->ops->FilePath($tagname);
            $title = $tagname.' ('.cms_relative_path($file, CMS_ROOT_PATH).')';
        } else {
            $title = $tagname;
        }
        $tmp = [
         'title' => $title,
         'description' => ($desc) ? $this->summarize($desc) : '',
         'edit_url' => $url,
         'text' => $html
        ];
        return $tmp;
    }
} // class
