<?php
/*
Class: article and category utility methods
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

use CMSMS\Lone;
use CMSMS\Utils as AppUtils;
use DateTime;
use DateTimeZone;
use News\Article;
use const CMS_DB_PREFIX;

final class Utils
{
    //NOTE static properties here >> Lone property|ies ?
    private static $_categories_loaded = FALSE;
    private static $_cached_categories = [];

//  private function __construct() {}
//  #[\ReturnTypeWillChange]
//  private function __clone() {}// : void {}

    /**
     * Munge supplied string to an URL-slug
     * @see also munge_string_to_url()
     *
     * @param mixed $str String to convert, or null
     * @param bool  $tolower Optional flag whether output string should be converted to lower case. Default false
     * @param bool  $withslash Optional flag whether slashes should be retained in the output. Default false UNUSED
     * @param int   $maxlen Optional maximum length of returned string. Default 15 bytes. Ignored if <= 10.
     *  Automatically increased somewhat if non-ASCII content needs to be converted.
     * @return string
     */
    public static function condense(string $str,bool $tolower = FALSE,bool $withslash = FALSE,int $maxlen = 15) : string
    {
        // $str = 'Example: 點看 with this after'; for testing
        $val = trim($str);
        if (!($val || is_numeric($val))) {
            return $val;
        }
        $val = preg_replace(['~\b[a-z]{1,2}\b~i','~\bthe\b~i','~[\s[:punct:]aeiouAEIOU]~'],['','',''],$val);
        $val = preg_replace_callback('~[\x80-\xff]+~',function($matches) use(&$maxlen) {
            $maxlen += (int)(strlen($matches[0]) / 2);
            $t = bin2hex($matches[0]);
            return '-'.base_convert($t,16,36).'-';
        },$val);
        if ($tolower) {
            $val = strtolower($val);
        }
        if ($maxlen > 10) {
            $val = substr($val,0,$maxlen);
        }
        $val = trim($val,'-');
        return $val;
    }

    /**
     *
     * @param type $id
     * @param array $params
     * @param int $returnid Default -1
     * @return array, maybe empty
     */
    public static function get_categories($id,array $params,$returnid = -1)
    {
        $tmp = self::get_all_categories();
        if( !$tmp ) return [];

        if( empty($params['category']) ) {
            $catinfo = $tmp;
        }
        else {
            $catinfo = [];
            $categories = explode(',',$params['category']);
            foreach( $categories as $onecat ) {
                if( strpos($onecat,'*') !== FALSE ) {
                    foreach( $tmp as $rec ) {
                        if( fnmatch($onecat,$rec['long_name']) ) {
                            $catinfo[] = $rec;
                        }
                    }
                }
                else {
                    foreach( $tmp as $rec ) {
                        if( $rec['long_name'] == $onecat ) {
                            $catinfo[] = $rec;
                        }
                    }
                }
            }
        }
        unset($tmp);
        if( !$catinfo ) return [];

        $cat_ids = [];
        for( $i = 0,$n = count($catinfo); $i < $n; $i++ ) {
            $cat_ids[] = $catinfo[$i]['news_category_id'];
        }
        sort($cat_ids);
        $cat_ids = array_unique($cat_ids);

        // get counts.
        $depth = 1;
        $db = Lone::get('Db');
        $counts = [];
        $longnow = $db->DbTimeStamp(time());

        $q2 = 'SELECT news_category_id,COUNT(news_id) AS cnt FROM '.CMS_DB_PREFIX.'module_news WHERE news_category_id IN (';
        $q2 .= implode(',',$cat_ids).') AND status = \'published\' AND ';
        if( !empty($params['showarchive']) ) {
            $q2 .= 'end_time < '.$longnow;
        }
        else {
            $q2 .= $db->ifNull('start_time','2000-1-1')." <= $longnow AND (end_time IS NULL OR end_time > $longnow)";
        }
        $q2 .= ' GROUP BY news_category_id';
        $tmp = $db->getArray($q2);
        if( $tmp ) {
            for( $i = 0,$n = count($tmp); $i < $n; $i++ ) {
                $counts[$tmp[$i]['news_category_id']] = $tmp[$i]['cnt'];
            }
        }

        $rowcounter = 0;
        $items = [];
        $depth = 1;
        for( $i = 0,$n = count($catinfo); $i < $n; $i++ ) {
            $row =& $catinfo[$i];
            $row['index'] = $rowcounter++;
            $row['count'] = $counts[$row['news_category_id']] ?? 0;
            $row['prevdepth'] = $depth;
            $depth = count(explode('.',$row['hierarchy']));
            $row['depth'] = $depth;

            // changes so that parameters supplied to the tag
            // get carried down through the links
            // screw pretty urls
            $parms = $params;
            unset($parms['browsecat']);
            unset($parms['category']);
            $parms['category_id'] = $row['news_category_id'];

            $pageid = ( isset($params['detailpage']) && $params['detailpage'] != '' ) ? $params['detailpage'] : $returnid;
            $mod = AppUtils::get_module('News');
            $row['url'] = $mod->CreateLink($id,'default',$pageid,$row['news_category_name'],$parms,'',true);
            $items[] = $row;
        }
        return $items;
    }

    /**
     *
     * @return array
     */
    public static function get_all_categories() : array
    {
        if( !self::$_categories_loaded ) {
            $db = Lone::get('Db');
            $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
            $dbresult = $db->getArray($query);
            if( $dbresult ) { self::$_cached_categories = $dbresult; }
            self::$_categories_loaded = TRUE;
        }
        return self::$_cached_categories;
    }

    /**
     *
     * @return array
     */
    public static function get_category_list() : array
    {
        self::get_all_categories();
        $categorylist = [];
        for( $i = 0,$n = count(self::$_cached_categories); $i < $n; $i++ ) {
            $row = self::$_cached_categories[$i];
            $categorylist[$row['long_name']] = $row['news_category_id'];
        }
        return $categorylist;
    }

    /**
     *
     * @return array
     */
    public static function get_category_names_by_id() : array
    {
        self::get_all_categories();
        $list = [];
        for( $i = 0,$n = count(self::$_cached_categories); $i < $n; $i++ ) {
            $list[self::$_cached_categories[$i]['news_category_id']] = self::$_cached_categories[$i]['news_category_name'];
        }
        return $list;
    }

    /**
     *
     * @param int $id
     * @return mixed string | null
     */
    public static function get_category_name_from_id(int $id)
    {
        self::get_all_categories();
        for( $i = 0,$n = count(self::$_cached_categories); $i < $n; $i++ ) {
            if( $id == self::$_cached_categories[$i]['news_category_id'] ) {
                return self::$_cached_categories[$i]['news_category_name'];
            }
        }
    }

    /**
     *
     * @param Article $news
     * @param array $params
     * @param bool $handle_uploads Default false UNUSED
     * @param bool $handle_deletes Default false UNUSED
     * @return Article
     */
    public static function fill_article_from_formparams(Article &$news,array $params,bool $handle_uploads = FALSE,$handle_deletes = FALSE) : Article
    {
        $cz = Lone::get('Config')['timezone'];
        $tz = new DateTimeZone($cz);
        $dt = new DateTime(null,$tz);
        $toffs = $tz->getOffset($dt);

        foreach( $params as $key => $value ) {
            switch( $key ) {
            case 'articleid':
                $news->id = $value;
                break;

            case 'author_id':
            case 'title':
            case 'content':
            case 'summary':
            case 'status':
            case 'news_url':
            case 'image_url':
            case 'extra':
                $news->$key = $value;
                break;

            case 'category':
                $list = self::get_category_names_by_id();
                for( $i = 0,$n = count(self::$_cached_categories); $i < $n; $i++ ) {
                    if( $value == self::$_cached_categories[$i]['news_category_name'] ) {
                        $news->category_id = self::$_cached_categories[$i]['news_category_id'];
                    }
                }
                break;

            case 'fromdate':
                $st = strtotime($value);
                if( $st !== false ) {
                    if( !empty($params['fromtime']) ) {
                        $stt = strtotime($params['fromtime'],0);
                        if( $stt !== false ) {
                            $st += $stt + $toffs;
                        }
                    }
                } else {
                    $st = 0;
                }
                $news->startdate = $st;
                break;

            case 'todate':
                $st = strtotime($value);
                if( $st !== false ) {
                    if( !empty($params['totime']) ) {
                        $stt = strtotime($params['totime'],0);
                        if( $stt !== false ) {
                            $st += $stt + $toffs;
                        }
                    }
                } else {
                    $st = 0;
                }
                $news->enddate = $st;
                break;
            }
        }
        return $news;
    }

    /**
     * @private
     * @ignore
     * @param array $row a row from a database-selection
     * @return Article
     */
    private static function get_article_from_row($row)
    {
        if( !is_array($row) ) return;

        $mod = AppUtils::get_module('News');
//      $fmt = $mod->GetDateFormat(); //c.f. 'Y-m-d H:i'
        $article = new Article();

        foreach( $row as $key => $value ) {
            switch( $key ) {
            case 'news_id':
                $article->id = $value;
                break;

            case 'news_category_id':
                $article->category_id = $value;
                break;

            case 'news_title':
                $article->title = $value;
                break;

            case 'news_data':
                $article->content = $value;
                break;

            case 'summary':
                $article->summary = $value;
                break;

            case 'start_time':
                $article->startdate = $mod->FormatforDisplay($value);
                break;

            case 'end_time':
                $article->enddate = $mod->FormatforDisplay($value);
                break;

            case 'status':
                $article->status = $value;
                break;

            case 'create_date':
                $article->created = $mod->FormatforDisplay($value);
                break;

            case 'modified_date':
                if( $value ) {
                    $article->modified = $mod->FormatforDisplay($value);
                } else {
                    $article->modified = '';
                }
                break;

            case 'author_id':
                $article->author_id = $value;
                break;

            case 'news_extra':
                $article->extra = $value;
                break;

            case 'image_url':
            case 'icon':
                $article->image_url = $value;
                break;

            case 'news_url':
            case 'alias':
                $article->news_url = $value;
                break;
            }
        }
        return $article;
    }

    /**
     *
     * @param bool $for_display Default true UNUSED
     * @return mixed Article | null
     */
    public static function get_latest_article(bool $for_display = TRUE)
    {
        $db = Lone::get('Db');
        $nonull = $db->ifNull('start_time','2000-1-1');
        $longnow = $db->DbTimeStamp(time());
        $pref = CMS_DB_PREFIX;
        $query = <<<EOS
SELECT N.*,G.news_category_name
FROM {$pref}module_news N
LEFT JOIN {$pref}module_news_categories G
ON N.news_category_id = G.news_category_id
WHERE status = 'published' AND $nonull <= $longnow AND (end_time IS NULL OR end_time > $longnow)
ORDER BY start_time DESC LIMIT 1
EOS;
        $row = $db->getRow($query);
        if( $row ) {
            return self::get_article_from_row($row); //,($for_display) ? 'PUBLIC' : 'ALL');
        }
    }

    /**
     *
     * @param $article_id  int | numeric string
     * @param bool $for_display Default true UNUSED
     * @param bool $allow_expired Default false
     * @return mixed Article | null
     */
    public static function get_article_by_id($article_id,bool $for_display = TRUE,bool $allow_expired = FALSE)
    {
        $db = Lone::get('Db');
        $nonull = $db->ifNull('start_time','2000-1-1');
        $longnow = $db->DbTimeStamp(time());
        $pref = CMS_DB_PREFIX;
        $query = <<<EOS
SELECT N.*,G.news_category_name
FROM {$pref}module_news N
LEFT JOIN {$pref}module_news_categories G
ON N.news_category_id = G.news_category_id
WHERE news_id = ? AND status = 'published' AND $nonull <= $longnow
EOS;
        if( !$allow_expired ) {
            $query .= ' AND (end_time IS NULL OR end_time > '.$longnow.')';
        }
        $row = $db->getRow($query,[$article_id]);
        if( $row ) {
            return self::get_article_from_row($row); //,(($for_display) ? 'PUBLIC' : 'ALL'));
        }
    }
} // class
