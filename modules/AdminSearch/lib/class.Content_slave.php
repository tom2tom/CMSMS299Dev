<?php
/*
Class which supports searching in content-pages.
Copyright (C) 2012-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file AdminSearch.module.php
*/
namespace AdminSearch;

use CMSMS\Lone;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use function check_permission;
use function get_userid;

final class Content_slave extends Base_slave
{
//TODO UI for processing inactive pages or not
    public function get_name()
    {
        $mod = Utils::get_module('AdminSearch');
        return $mod->Lang('lbl_content_search');
    }

    public function get_description()
    {
        $mod = Utils::get_module('AdminSearch');
        return $mod->Lang('desc_content_search');
    }

//  public function use_slave(int $userid = 0): bool {}

    protected function check_permission(int $userid = 0)
    {
        return true; // we will use page-specific checks, later
    }

    // returns array, containing arrays or empty
    public function get_matches()
    {
        $all = $this->include_inactive_items();
        $fz = $this->search_fuzzy();
        $output = [];
        $db = Lone::get('Db');
        $pref = CMS_DB_PREFIX;
        $query = <<<EOS
SELECT
C.content_id,
C.content_name,
C.menu_text,
C.content_alias,
C.metadata,
C.titleattribute,
C.page_url, P.content FROM {$pref}content C
LEFT JOIN {$pref}content_props P
ON C.content_id = P.content_id

EOS;
        $query .= ($all) ? 'WHERE (' : 'WHERE C.active=1 AND (';
        if ($fz) {
            if ($this->search_casesensitive()) {
                $wheres = [
'P.prop_name=\'content_en\' AND P.content REGEXP BINARY ?',
'CONCAT_WS(\'\',
C.content_name,
C.menu_text,
C.content_alias,
C.metadata,
C.titleattribute,
C.page_url) REGEXP BINARY ?'];
            } else {
//TODO handle case-insensitive whole-chars, not bytes
                $wheres = [
'P.prop_name=\'content_en\' AND P.content REGEXP CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci',
'CONCAT_WS(\'\',
C.content_name,
C.menu_text,
C.content_alias,
C.metadata,
C.titleattribute,
C.page_url) REGEXP CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci'];
            }
        } elseif ($this->search_casesensitive()) {
            $wheres = [
'P.prop_name=\'content_en\' AND P.content LIKE BINARY ?',
'CONCAT_WS(\'\',
C.content_name,
C.menu_text,
C.content_alias,
C.metadata,
C.titleattribute,
C.page_url) LIKE BINARY ?'];
        } else {
            $wheres = [
'P.prop_name=\'content_en\' AND P.content LIKE ?',
'CONCAT_WS(\'\',
C.content_name,
C.menu_text,
C.content_alias,
C.metadata,
C.titleattribute,
C.page_url) LIKE ?'];
        }
        $query .= implode(' OR ', $wheres) . ') GROUP BY C.content_id ORDER BY C.content_name'; // TODO if needed, work around ONLY_FULL_GROUP_BY effect on reported fields other than content_id
        $needle = $this->get_text();
        if ($fz) {
            $needle = $this->get_regex_pattern($needle, false);
            $wm = $db->escStr($needle);
        } else {
            $wm = '%'.$db->escStr($needle).'%';
        }
        $dbr = $db->getArray($query, [$wm, $wm]);
        if ($dbr) {
            $content_manager = Utils::get_module('ContentManager');
            $content_ops = Lone::get('ContentOperations'); // OR CMSMS\ContentOperations->get_instance()
            $userid = get_userid();
            $pmod1 = check_permission($userid, 'Manage All Content') || check_permission($userid, 'Modify Any Page');
            $mains = ['content_name'=>1,'menu_text'=>1,'content_alias'=>1,'metadata'=>1,'titleattribute'=>1,'page_url'=>1];

            foreach ($dbr as $row) {
                $html = '';
                $checkfields = array_intersect_key($row, $mains);
                foreach ($checkfields as $key => &$val) {
                    if ($val === '' || is_null($val)) {
                        unset($checkfields[$key]);
                    }
                }
                unset($val);
                $html2 = $this->get_matches_info(implode('<br>', $checkfields));
                if ($html2) {
                    $html .= '<br>'.$html2;
                }
                $html2 = $this->get_matches_info($row['content']);
                if ($html2) {
                    $html .= '<br>'.$html2;
                }

                if (!$html) {
                    continue;
                }
                $html = substr($html, 6); //strip leading newline

                $desc = ($row['content_name'] != $row['menu_text']) ? $row['menu_text'] : ''; // proxy for this context

                $content_id = $row['content_id'];
                if ($pmod1 || $content_ops->CheckPageAuthorship($userid, $content_id)) {
                    $url = $content_manager->create_action_url('', 'admin_editcontent', ['content_id' => $content_id]);
                } else {
                    $url = ''; // no edit-access to this page
                }

                $output[] = [
                 'title' => $row['content_name'],
                 'description' => $desc,
                 'edit_url' => $url,
                 'text' => $html,
                ];
            }
        }
        return $output;
    }
} // class
