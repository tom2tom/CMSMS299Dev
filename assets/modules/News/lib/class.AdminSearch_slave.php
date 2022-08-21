<?php
/*
Class: content searcher for News module
Copyright (C) 2016-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace News;

use AdminSearch\Base_slave;
use CMSMS\Lone;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use function check_permission;
use function get_userid;

final class AdminSearch_slave extends Base_slave
{
    public function get_name()
    {
        $mod = Utils::get_module('News');
        return $mod->Lang('lbl_adminsearch');
    }

    public function get_description()
    {
        $mod = Utils::get_module('News');
        return $mod->Lang('desc_adminsearch');
    }

//  public function use_slave(int $userid = 0) : bool {}

    protected function check_permission(int $userid = 0)
    {
        if ($userid == 0) { $userid = get_userid(); }
        return check_permission($userid,'Modify News');
    }

    //returns array, containing arrays or empty
    public function get_matches()
    {
        $mod = Utils::get_module('News');
        if( !is_object($mod) ) return [];

        $fz = $this->search_fuzzy();
        $output = [];
        $db = Lone::get('Db');
        // build the query
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news WHERE (';
        if( $fz ) {
            if( $this->search_casesensitive() ) {
                $wheres = [
                 'news_title REGEXP BINARY ?',
                 'news_data REGEXP BINARY ?',
                 'summary REGEXP BINARY ?'
                ];
            }
            else {
//TODO handle case-insensitive whole-chars, not bytes
                $wheres = [
                 'news_title REGEXP CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci',
                 'news_data REGEXP CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci',
                 'summary REGEXP CONVERT(? USING utf8mb4) COLLATE utf8mb4_general_ci'
                ];
            }
        } elseif( $this->search_casesensitive() ) {
            $wheres = [
             'news_title LIKE BINARY ?',
             'news_data LIKE BINARY ?',
             'summary LIKE BINARY ?'
            ];
        }
        else {
            $wheres = [
             'news_title LIKE ?',
             'news_data LIKE ?',
             'summary LIKE ?'
            ];
        }

        $needle = $this->get_text();
        if( $fz ) {
            $needle = $this->get_regex_pattern($needle, false);
            $wm = $db->escStr($needle);
        }
        else {
            $wm = '%' . $db->escStr($needle) . '%';
        }

        if( $this->search_descriptions() ) {
            $parms = [$wm, $wm, $wm];
        }
        else {
            unset($wheres[2]);
            $parms = [$wm, $wm];
        }
        $query .= implode(' OR ', $wheres).
        ') ORDER BY IF(modified_date,modified_date,create_date) DESC';

        $dbr = $db->getArray($query, $parms);
        if( $dbr ) {
            // got some results
            foreach( $dbr as $row ) {
                $html = '';
                foreach( $row as $key => $value ) {
                    // search for the keyword
                    $html2 = $this->get_matches_info($value);
                    if( $html2 ) {
                        $html .= '<br />'.$html2;
                    }
                }
                if( $html ) {
                    $html = substr($html,6); // strip leading newline
                }
                else {
                    continue;
                }

                $desc = $row['summary'];
                if( $this->check_permission() ) {
                    $url = $mod->create_action_url('','editarticle',['articleid'=>$row['news_id']]);
                }
                else {
                    $url = ''; // OR view-only URL?
                }
                $output[] = [
                 'title' => $row['news_title'],
                 'description' => ($desc) ? $this->summarize($desc) : '',
                 'edit_url' => $url,
                 'text' => $html
                ];
            }
        }
        return $output;
    }
} // class
