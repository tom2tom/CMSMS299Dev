<?php
/*
Class which supports searching in content-pages.
Copyright (C) 2012-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file AdminSearch.module.php
*/
namespace AdminSearch;

use CMSMS\SingleItem;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use function check_permission;
use function get_userid;

final class Content_slave extends Base_slave
{
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

    public function check_permission()
    {
        return true; // we will use page-specific check, later
    }

    // returns array of arrays
    public function get_matches()
    {
        $db = SingleItem::Db();
        $query = 'SELECT
C.content_id,
C.content_name,
C.menu_text,
C.content_alias,
C.metadata,
C.titleattribute,
C.page_url, P.content FROM '.CMS_DB_PREFIX.'content C LEFT JOIN '.
            CMS_DB_PREFIX.'content_props P ON C.content_id = P.content_id WHERE ';
        if ($this->search_casesensitive()) {
            $where = [
             'P.content LIKE CONVERT(? USING utf8mb4) COLLATE utf8mb4_bin',
             'CONCAT_WS(\'\',
C.content_name,
C.menu_text,
C.content_alias,
C.metadata,
C.titleattribute,
C.page_url) LIKE CONVERT(? USING utf8mb4) COLLATE utf8mb4_bin'
            ];
        } else {
            $where = ['P.content LIKE ?', 'CONCAT_WS(\'\',
C.content_name,
C.menu_text,
C.content_alias,
C.metadata,
C.titleattribute,
C.page_url) LIKE ?'];
        }
        $query .= implode(' OR ', $where) . ' GROUP BY C.content_id ORDER BY C.content_name'; // TODO if needed, work around ONLY_FULL_GROUP_BY effect on reported fields other than content_id
        $needle = $this->get_text();
        $wm = '%'.$db->secStr($needle).'%';
        $dbr = $db->getArray($query, [$wm, $wm]);
        if ($dbr) {
            $content_manager = Utils::get_module('ContentManager');
            $content_ops = SingleItem::ContentOperations(); // OR CMSMS\ContentOperations->get_instance()
            $userid = get_userid();
            $pmod1 = check_permission($userid, 'Manage All Content') || check_permission($userid, 'Modify Any Page');
            $output = [];
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
                $html2 = $this->get_matches_info(implode('<br />', $checkfields));
                if ($html2) {
                    $html .= '<br />'.$html2;
                }
                $html2 = $this->get_matches_info($row['content']);
                if ($html2) {
                    $html .= '<br />'.$html2;
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

                $tmp = [
                 'title' => $row['content_name'],
                 'description' => $desc,
                 'edit_url' => $url,
                 'text' => $html,
                ];
                $output[] = $tmp;
            }

            return $output;
        }
    }
} // class
