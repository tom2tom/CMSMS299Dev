<?php
/*
Class which supports searching in module-related templates.
Copyright (C) 2012-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file AdminSearch.module.php
*/
namespace AdminSearch;

use CMSMS\Lone;
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

//  public function use_slave(int $userid = 0) : bool {}

    protected function check_permission(int $userid = 0)
    {
        if ($userid == 0) { $userid = get_userid();  }
        return check_permission($userid, 'Modify Templates'); //tho' no redirect to edit templates from returned match-data
    }

    //returns array, containing arrays or empty
    public function get_matches()
    {
        $ds = $this->search_descriptions();
        $fz = $this->search_fuzzy();
        $output = [];
        $db = Lone::get('Db');
        $query = 'SELECT originator,name,description,content FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE (originator IS NOT NULL AND originator != \'\' AND originator != \'__CORE__\' '; //other originators are for module-templates or themes
        if ($fz) {
            if ($this->search_casesensitive()) {
                $wheres = [
                 'name REGEXP BINARY ?',
                 'description REGEXP BINARY ?',
                 'content REGEXP BINARY ?'
                ];
            } else {
//TODO handle case-insensitive whole-chars, not bytes
                $wheres = [
                 'name REGEXP CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci',
                 'description REGEXP CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci',
                 'content REGEXP CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci'
                ];
            }
        } elseif ($this->search_casesensitive()) {
            $wheres = [
             'name LIKE BINARY ?',
             'description LIKE BINARY ?',
             'content LIKE BINARY ?'
            ];
        } else {
            $wheres = [
             'name LIKE ?',
             'description LIKE ?',
             'content LIKE ?'
            ];
        }

        $needle = $this->get_text();
        if ($fz) {
            $needle = $this->get_regex_pattern($needle, false);
            $wm = $db->escStr($needle);
        } else {
            $wm = '%'.$db->escStr($needle).'%';
        }

        if ($ds) {
            $parms = [$wm, $wm, $wm];
        } else {
            unset($wheres[1]);
            $parms = [$wm, $wm];
        }
        $query .= 'AND ('.implode(' OR ', $wheres).'))';
        $dbr = $db->getArray($query, $parms);
        if ($dbr) {
            foreach ($dbr as $row) {
                $html = '';
                $html2 = $this->get_matches_info($row['name']);
                if ($html2) {
                    $html .= '<br>'.$html2;
                }
                $desc = $row['description'];
                if ($desc && $ds) {
                    $html2 = $this->get_matches_info($desc);
                    if ($html2) {
                        $html .= '<br>'.$html2;
                    }
                }
                $html2 = $this->get_matches_info($row['content']);
                if ($html2) {
                    $html .= '<br>'.$html2;
                }
                if (!$html) {
                    continue;
                }
                $html = substr($html, 6); // strip leading newline

                $output[] = [
                 'title' => $row['originator'].' + '.$row['name'],
                 'description' => ($desc) ? $this->summarize($desc) : '',
                 'url' => '', //unlike other slaves, no 'edit_url' specified
                 'text' => $html
                ];
            }
        }
        return $output;
    }
} // class
