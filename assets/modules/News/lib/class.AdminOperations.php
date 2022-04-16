<?php
/*
Class: admin console utilities
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace News;

//use function cms_move_uploaded_file;
use CMSMS\Events;
use CMSMS\Route;
use CMSMS\RouteOperations;
use CMSMS\SingleItem;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use function CMSMS\log_info;
use function get_userid;

final class AdminOperations
{
//    private function __construct() {}
//    private function __clone() {}

    /**
     *
     * @since 2.90
     * @param mixed $articleid int | numeric string
     * @return boolean
     */
    public static function copy_article($articleid)
    {
        if (!$articleid) return false;

        $db = SingleItem::Db();
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news WHERE news_id = ?';
        $row = $db->getRow($query, [$articleid]);
        if ($row) {
            //image_url is skipped
            $query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news
(news_id,
news_category_id,
news_title,
news_data,
news_extra,
summary,
news_url,
start_time,
end_time,
status,
create_date,
modified_date,
author_id,
searchable) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $row['news_id'] = $db->genID(CMS_DB_PREFIX.'module_news_seq');
            $row['news_title'] .= ' : Copy';
            $row['news_url'] .= 'copy';
            $row['start_time'] = null;
            $row['end_time'] = null;
            $row['status'] = 'draft';
            $row['create_date'] = $db->DbTimeStamp(time(),false);
            $row['modified_date'] = null;
            $row['author_id'] = get_userid(false);
            if ($db->execute($query, [
                $row['news_id'],
                $row['news_category_id'],
                $row['news_title'],
                $row['news_data'],
                $row['news_extra'],
                $row['summary'],
                $row['news_url'],
                $row['start_time'],
                $row['end_time'],
                $row['status'],
                $row['create_date'],
                $row['modified_date'],
                $row['author_id'],
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
     * @since 3.1
     * @param mixed $articleid int | numeric string
     * @param mixed $categoryid int | numeric string
     * @return boolean
     */
    public static function move_article($articleid, $categoryid)
    {
        if (!$articleid) return false;
        $db = SingleItem::Db();
        $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET
news_category_id = ?,
modified_date = ?
WHERE news_id = ?';
        $longnow = $db->DbTimeStamp(time(),false);
        $db->execute($query, [$categoryid, $longnow, $articleid]);
        if ($db->errorNo() > 0) {
            // TODO handle error
            return false;
        }
        return true;
    }

    /**
     *
     * @param mixed $articleid int | numeric string
     * @return boolean
     */
    public static function delete_article($articleid)
    {
        if (!$articleid) return false;

        $db = SingleItem::Db();
        // remove the article
        $query = 'DELETE FROM '.CMS_DB_PREFIX.'module_news WHERE news_id = ?';
        $db->execute($query, [$articleid]);

        self::delete_static_route($articleid);

        //Update search index
        $mod = Utils::get_search_module();
        if ($mod ) {
            $mod2 = Utils::get_module('News');
            $mod->DeleteWords($mod2->GetName(), $articleid, 'article');
        }

        Events::SendEvent( 'News', 'NewsArticleDeleted', ['news_id'=>$articleid ] );

        log_info($articleid, 'News: '.$articleid, 'Article deleted');
        return true;
    }

    /* *
     *
     * @param type $itemid
     * @param type $fieldname
     * @param string-reference $error
     * @return mixed string | false
     */
/*
    public static function handle_upload($itemid,$fieldname,&$error)
    {
        $config = SingleItem::Config();

        $mod = Utils::get_module('News');
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
    * Supports up to 999 children of each node (tho. recorded order <= 255)
    */
    public static function UpdateHierarchyPositions()
    {
        $db = SingleItem::Db();
        $query = 'SELECT news_category_id, item_order, news_category_name FROM '.CMS_DB_PREFIX.'module_news_categories';
        $rst = $db->execute($query);
        if ($rst) {
          while ($row = $rst->FetchRow()) {
            $current_hierarchy_position = '';
            $current_long_name = '';
            $content_id = $row['news_category_id'];
            $current_parent_id = $row['news_category_id'];
            $count = 0;

            while ($current_parent_id > -1) {
                $query = 'SELECT news_category_id, item_order, news_category_name, parent_id FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_id = ?';
                $row2 = $db->getRow($query, [$current_parent_id]);
                if ($row2) {
                    $current_hierarchy_position = str_pad($row2['item_order'], 3, '0', STR_PAD_LEFT) . '.' . $current_hierarchy_position;
                    $current_long_name = $row2['news_category_name'] . ' | ' . $current_long_name;
                    $current_parent_id = $row2['parent_id'];
                    $count++;
                }
                else {
                    $current_parent_id = 0;
                }
            }

            if (strlen($current_hierarchy_position) > 0) {
                $current_hierarchy_position = substr($current_hierarchy_position, 0, -1);
            }

            if (strlen($current_long_name) > 0) {
                $current_long_name = substr($current_long_name, 0, -3); // omit trailing separator
            }

            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_categories SET hierarchy = ?, long_name = ? WHERE news_category_id = ?';
            $db->execute($query, [$current_hierarchy_position, $current_long_name, $content_id]);
          }
          $rst->Close();
        }
    }

    /**
     *
     * @param int $news_article_id
     * @return bool indicating success
     */
    public static function delete_static_route($news_article_id)
    {
        return RouteOperations::del_static('','News',$news_article_id);
    }

    /**
     * Create and register a 'detail' route
     *
     * @param string $news_url
     * @param int $news_article_id
     * @param mixed $detailpage int | numeric string | falsy
     * @return bool indicating success
     * @throws Exception if registration fails
     */
    public static function register_static_route($news_url,$news_article_id,$detailpage = '')
    {
        if( $detailpage <= 0 ) {
            $mod = Utils::get_module('News');
            $detailpage = $mod->GetPreference('detail_returnid',-1);
            if( $detailpage == -1 ) {
                $detailpage = SingleItem::ContentOperations()->GetDefaultContent();
            }
        }
        $dflts = ['action'=>'detail','returnid'=>$detailpage,'articleid'=>$news_article_id];
        $route = new Route($news_url,'News',$dflts,TRUE,$news_article_id);
        return RouteOperations::add_static($route);
    }

    /**
     *
     * @param string $txt
     * @return array, maybe empty
     */
    public static function optionstext_to_array($txt)
    {
        $txt = trim($txt);
        if( !$txt ) return [];

        $arr_options = [];
        $tmp1 = explode("\n",$txt);
        foreach( $tmp1 as $tmp2 ) {
            $tmp2 = trim($tmp2);
            if( $tmp2 === '' ) continue;
            if( strpos($tmp2,'=') === false ) {
                $tmp2_k = $tmp2_v = $tmp2;
            }
            else {
                list($tmp2_k,$tmp2_v) = explode('=',$tmp2,2);
                if( $tmp2_k === '' || $tmp2_v === '' ) continue;
            }
            $arr_options[$tmp2_k] = $tmp2_v;
        }
        return $arr_options;
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
