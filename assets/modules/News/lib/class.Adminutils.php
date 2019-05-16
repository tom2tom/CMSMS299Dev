<?php
#Class: article utilities
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace News;

use cms_route_manager;
use cms_utils;
use CMSMS\Events;
use CmsRoute;
use const CMS_DB_PREFIX;
use function audit;
use function cms_join_path;
//use function cms_move_uploaded_file;
use function cmsms;
use function get_userid;
use function recursive_delete;

final class AdminOperations
{
    protected function __construct() {}
    protected function __clone() {}

    /**
     *
	 * @since 2.90
     * @param int $articleid Or numeric string
     * @return boolean
     */
    public static function copy_article($articleid)
    {
        if (!$articleid) return false;

        $db = cmsms()->GetDb();
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news WHERE news_id = ?';
        $row = $db->GetRow($query, [$articleid]);
        if ($row) {
            $query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news (
news_id,
news_category_id,
news_title,
news_data,
summary,
start_time,
end_time,
status,
create_date,
modified_date,
author_id,
news_extra,
news_url,
searchable) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $row['news_id'] = $db->GenID(CMS_DB_PREFIX.'module_news_seq');
            $row['news_title'] .= ' : Copy';
            $row['start_time'] = 0;
            $row['end_time'] = 0;
            $row['status'] = 'draft';
            $row['create_date'] = time();
            $row['modified_date'] = 0;
            $row['author_id'] = get_userid(false);
            if ($db->Execute($query, [
                $row['news_id'],
                $row['news_category_id'],
                $row['news_title'],
                $row['news_data'],
                $row['summary'],
                $row['start_time'],
                $row['end_time'],
                $row['status'],
                $row['create_date'],
                $row['modified_date'],
                $row['author_id'],
                $row['news_extra'],
                $row['news_url'],
                $row['searchable'] ])) {
                //TODO related stuff c.f. addarticle action
                Events::SendEvent('News', 'NewsArticleAdded', $row);
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param int $articleid Or numeric string
     * @return boolean
     */
    public static function delete_article($articleid)
    {
        if (!$articleid) return false;

        $db = cmsms()->GetDb();
        // remove the article
        $query = 'DELETE FROM '.CMS_DB_PREFIX.'module_news WHERE news_id = ?';
        $db->Execute($query, [$articleid]);

        self::delete_static_route($articleid);

        //Update search index
        $mod = cms_utils::get_module('News');
        $module = cms_utils::get_search_module();
        if ($module != false) $module->DeleteWords($mod->GetName(), $articleid, 'article');

        Events::SendEvent( 'News', 'NewsArticleDeleted', ['news_id'=>$articleid ] );

        // put mention into the admin log
        audit($articleid, 'News: '.$articleid, 'Article deleted');
        return true;
    }

    /* *
     *
     * @param type $itemid
     * @param type $fieldname
     * @param string-reference $error
     * @return mixed string|false
     */
/*
    public static function handle_upload($itemid,$fieldname,&$error)
    {
        $config = cmsms()->GetConfig();

        $mod = cms_utils::get_module('News');
        $p = cms_join_path($config['uploads_path'],'news');
        if (!is_dir($p)) {
            if( @mkdir($p) === false ) {
                $error = $mod->Lang('error_mkdir',$p);
                return false;
            }
        }

        $p = cms_join_path($config['uploads_path'],'news','id'.$itemid);
        if (!is_dir($p)) {
            if( @mkdir($p) === false ) {
                $error = $mod->Lang('error_mkdir',$p);
                return false;
            }
        }

        if( $_FILES[$fieldname]['size'] > $config['max_upload_size'] ) {
            $error = $mod->Lang('error_filesize');
            return false;
        }

        $filename = basename($_FILES[$fieldname]['name']);
        $dest = cms_join_path($config['uploads_path'],'news','id'.$itemid,$filename);

        // Get the files extension
        $ext = substr(strrchr($filename, '.'), 1);

        // compare it against the 'allowed extentions'
        $exts = explode(',',$mod->GetPreference('allowed_upload_types',''));
        if( !in_array( $ext, $exts ) )  {
            $error = $mod->Lang('error_invalidfiletype');
            return false;
        }

        if( @cms_move_uploaded_file($_FILES[$fieldname]['tmp_name'], $dest) === false ) {
            $error = $mod->Lang('error_movefile',$dest);
            return false;
        }

        return $filename;
    }
*/
   /**
    *
    */
    public static function UpdateHierarchyPositions()
    {
        $db = cmsms()->GetDb();
        $query = 'SELECT news_category_id, item_order, news_category_name FROM '.CMS_DB_PREFIX.'module_news_categories';
        $dbresult = $db->Execute($query);
        while ($dbresult && $row = $dbresult->FetchRow()) {
            $current_hierarchy_position = '';
            $current_long_name = '';
            $content_id = $row['news_category_id'];
            $current_parent_id = $row['news_category_id'];
            $count = 0;

            while ($current_parent_id > -1) {
                $query = 'SELECT news_category_id, item_order, news_category_name, parent_id FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_id = ?';
                $row2 = $db->GetRow($query, [$current_parent_id]);
                if ($row2) {
                    $current_hierarchy_position = str_pad($row2['item_order'], 5, '0', STR_PAD_LEFT) . '.' . $current_hierarchy_position;
                    $current_long_name = $row2['news_category_name'] . ' | ' . $current_long_name;
                    $current_parent_id = $row2['parent_id'];
                    $count++;
                }
                else {
                    $current_parent_id = 0;
                }
            }

            if (strlen($current_hierarchy_position) > 0) {
                $current_hierarchy_position = substr($current_hierarchy_position, 0, strlen($current_hierarchy_position) - 1);
            }

            if (strlen($current_long_name) > 0) {
                $current_long_name = substr($current_long_name, 0, strlen($current_long_name) - 3);
            }

            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_categories SET hierarchy = ?, long_name = ? WHERE news_category_id = ?';
            $db->Execute($query, [$current_hierarchy_position, $current_long_name, $content_id]);
        }
    }

    /**
     *
     * @param type $news_article_id
     * @return type
     */
    public static function delete_static_route($news_article_id)
    {
        return cms_route_manager::del_static('','News',$news_article_id);
    }

    /**
     *
     * @param type $news_url
     * @param type $news_article_id
     * @param type $detailpage
     * @return type
     */
    public static function register_static_route($news_url,$news_article_id,$detailpage = '')
    {
        if( $detailpage <= 0 ) {
            $module = cms_utils::get_module('News');
            $detailpage = $module->GetPreference('detail_returnid',-1);
            if( $detailpage == -1 ) {
                $detailpage = cmsms()->GetContentOperations()->GetDefaultContent();
            }
        }
        $parms = ['action'=>'detail','returnid'=>$detailpage,'articleid'=>$news_article_id];

        $route = CmsRoute::new_builder($news_url,'News',$news_article_id,$parms,TRUE);
        return cms_route_manager::add_static($route);
    }

    /**
     *
     * @param string $txt
     * @return mixed array|null
     */
    public static function optionstext_to_array($txt)
    {
        $txt = trim($txt);
        if( !$txt ) return;

        $arr_options = [];
        $tmp1 = explode("\n",$txt);
        foreach( $tmp1 as $tmp2 ) {
            $tmp2 = trim($tmp2);
            if( $tmp2 == '' ) continue;
            $tmp2_k = $tmp2_v = $tmp2;
            if( strpos($tmp2,'=') !== false ) {
                list($tmp2_k,$tmp2_v) = explode('=',$tmp2,2);
            }
            if( $tmp2_k == '' || $tmp2_v == '' ) continue;
            $arr_options[$tmp2_k] = $tmp2_v;
        }
        if( $arr_options ) return $arr_options;
    }

    /**
     *
     * @param array $arr
     * @return string
     */
    public static function array_to_optionstext($arr)
    {
        $txt = '';
        foreach( $arr as $key => $val ) {
            $txt .= "$key=$val\n";
        }
        return trim($txt);
    }
} // class
