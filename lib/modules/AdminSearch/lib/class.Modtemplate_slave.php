<?php
/*
Class which supports searching in module-related templates.
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file AdminSearch.module.php
*/
namespace AdminSearch;

use CMSMS\AppSingle;
use CMSMS\TemplateOperations;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use function check_permission;
use function get_userid;

final class Modtemplate_slave extends Base_slave
{
    public function get_name()
    {
        $mod = Utils::get_module('AdminSearch');
        return $mod->Lang('lbl_modtemplate_search');
    }

    public function get_description()
    {
        $mod = Utils::get_module('AdminSearch');
        return $mod->Lang('desc_modtemplate_search');
    }

    public function get_section_description()
    {
        $mod = Utils::get_module('AdminSearch');
        return $mod->Lang('sectiondesc_modtemplates');
    }

    public function check_permission()
    {
        return check_permission(get_userid(), 'Modify Templates'); //tho' no redirect to edit templates from returned match-data
    }

    //returns array of arrays
    public function get_matches()
    {
        $db = AppSingle::Db();
        $query = 'SELECT originator,name,description,content FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE originator IS NOT NULL AND originator != \'\' AND originator != \'__CORE__\' '; //other originators are for module-templates
        if ($this->search_casesensitive()) {
            $where = [
             'name LIKE CONVERT(? USING utf8mb4) COLLATE utf8mb4_bin',
             'description LIKE CONVERT(? USING utf8mb4) COLLATE utf8mb4_bin',
             'content LIKE CONVERT(? USING utf8mb4) COLLATE utf8mb4_bin'
            ];
        } else {
            $where = ['name LIKE ?', 'description LIKE ?', 'content LIKE ?'];
        }
        if (!$this->search_descriptions()) {
            unset($where[1]);
        }
        $query .= 'AND '.implode(' OR ', $where);

        $output = [];
        $needle = $this->get_text();
        $wm = '%'.$db->escStr($needle).'%';
        $dbr = $db->GetArray($query, [$wm,$wm,$wm]);
        if ($dbr) {
            foreach ($dbr as $row) {
                $html = '';
                $html2 = $this->get_matches_info($row['name']);
                if ($html2) {
                    $html .= '<br />'.$html2;
                }
                $desc = row['description'];
                if ($desc && $this->search_descriptions()) {
                    $html2 = $this->get_matches_info($desc);
                    if ($html2) {
                        $html .= '<br />'.$html2;
                    }
                }
                $html2 = $this->get_matches_info($row['content']);
                if ($html2) {
                    $html .= '<br />'.$html2;
                }
                if (!$html) {
                    continue;
                }
                $html = substr($html, 6); // strip leading newline

                //unlike other slaves, no 'edit_url' specified
                $output[] = [
                 'title' => $row['originator'].' + '.$row['name'],
                 'description' => ($desc) ? $this->summarize($desc) : '',
                 'url' => '',
                 'text' => $html
                ];
            }
        }
        return $output;
    }
} // class
