<?php
/*
Class which supports searching in templates.
Copyright (C) 2012-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file AdminSearch.module.php
*/
namespace AdminSearch;

use CMSMS\Lone;
use CMSMS\Template;
use CMSMS\TemplateOperations;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use const CMS_ROOT_PATH;
use function check_permission;
use function cms_relative_path;
use function get_secure_param;
use function get_userid;

final class Template_slave extends Base_slave
{
    public function get_name()
    {
        $mod = Utils::get_module('AdminSearch');
        return $mod->Lang('lbl_template_search');
    }

    public function get_description()
    {
        $mod = Utils::get_module('AdminSearch');
        return $mod->Lang('desc_template_search');
    }

    public function check_permission()
    {
        $userid = get_userid();
        return check_permission($userid, 'Modify Templates');
    }

    /**
     *
     * @return array of arrays
     */
    public function get_matches()
    {
        if (!$this->check_permission()) {
            return [];
        }
        $db = Lone::get('Db');
        // get all template ids
        $sql = 'SELECT id FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' ORDER BY name';
        $all_ids = $db->getCol($sql);
        $output = [];
        if ($all_ids) {
            $chunks = array_chunk($all_ids, 15);
            foreach ($chunks as $chunk) {
                $tpl_list = TemplateOperations::get_bulk_templates($chunk);
                foreach ($tpl_list as $tpl) {
                    $res = $this->get_tpl_match_info($tpl);
                    if ($res) {
                        $output[] = $res;
                    }
                }
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
     * @param Template $tpl
     * @return array
     */
    private function get_tpl_match_info(Template $tpl)
    {
        $html = '';
        $name = $tpl->get_name();
        $html2 = $this->get_matches_info($name);
        if ($html2) {
            $html .= '<br />'.$html2;
        }
        $desc = $tpl->get_description();
        if ($desc && $this->search_descriptions()) {
            $html2 = $this->get_matches_info($desc);
            if ($html2) {
                $html .= '<br />'.$html2;
            }
        }
        $content = $tpl->get_content();
        $html2 = $this->get_matches_info($content);
        if ($html2) {
            $html .= '<br />'.$html2;
        }
        if (!$html) {
            return [];
        }
        $html = substr($html, 6); //strip leading newline

        if ($this->check_permission()) {
            $one = $tpl->get_id();
            $urlext = get_secure_param();
            $url = 'edittemplate.php'.$urlext.'&tpl='.$one;
        } else {
            $url = ''; // OR view-only URL?
        }

        if ($tpl->get_content_file()) {
            $file = $tpl->get_content_filename();
            $title = $name.' ('.cms_relative_path($file, CMS_ROOT_PATH).')';
        } else {
            $title = $name;
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
