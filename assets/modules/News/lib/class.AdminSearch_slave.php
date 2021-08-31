<?php
/*
Class: content searcher for News module
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
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
use CMSMS\SingleItem;
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

    public function check_permission()
    {
        $userid = get_userid();
        return check_permission($userid,'Modify News');
    }

    //returns array of arrays
    public function get_matches()
    {
        $mod = Utils::get_module('News');
        if( !is_object($mod) ) return;

        $db = SingleItem::Db();
        // build the query
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news WHERE ';
        if( $this->search_casesensitive() ) {
            $where = [
             'news_title LIKE CONVERT(? USING utf8mb4) COLLATE utf8mb4_bin',
             'news_data LIKE CONVERT(? USING utf8mb4) COLLATE utf8mb4_bin',
             'summary LIKE CONVERT(? USING utf8mb4) COLLATE utf8mb4_bin'
            ];
        }
        else {
            $where = ['news_title LIKE ?','news_data LIKE ?','summary LIKE ?'];
        }

        $query .= ' '.implode(' OR ',$where);
        $query .= ' ORDER BY IF(modified_date,modified_date,create_date) DESC';

        $needle = $this->get_text();
        $wm = '%' . $db->escStr($needle) . '%';
        $parms = [$wm,$wm,$wm];

        $dbr = $db->getArray($query,[$parms]);
        if( $dbr ) {
            // got some results
            $output = [];
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

                if( $this->check_permission() ) {
                    $url = $mod->create_action_url('m1_','editarticle',['articleid'=>$row['news_id']]);
                }
                else {
                    $url = ''; // OR view-only URL?
                }
                $tmp = [
                 'title'=>$row['news_title'],
                 'description'=>$this->summarize($row['summary']),
                 'edit_url'=>$url,
                 'text'=>$html
                ];
                $output[] = $tmp;
            }
            return $output;
        }
    }
} // class
