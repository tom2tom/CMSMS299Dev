<?php
#Class: article and category utility methods
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

use cms_config;
use cms_utils;
use CmsApp;
use DateTime;
use DateTimeZone;
use const CMS_DB_PREFIX;

final class Utils
{
  protected function __construct() {}

  private static $_categories_loaded = FALSE;
  private static $_cached_categories = [];
//  private static $_cached_fielddefs = NULL;
//  private static $_cached_fieldvals= NULL;

  public static function get_categories($id,$params,$returnid=-1)
  {
    $tmp = self::get_all_categories();
    if( !$tmp ) return;

    if( !isset($params['category']) || $params['category'] == '' ) {
      $catinfo = $tmp;
    }
    else {
      $catinfo = [];
      $categories = explode(',', $params['category']);
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
    if( !$catinfo ) return;

    $cat_ids = [];
    for( $i = 0, $n = count($catinfo); $i < $n; $i++ ) {
      $cat_ids[] = $catinfo[$i]['news_category_id'];
    }
    sort($cat_ids);
    $cat_ids = array_unique($cat_ids);

    // get counts.
    $depth = 1;
    $db = CmsApp::get_instance()->GetDb();
    $counts = [];
    $now = time();

    $q2 = 'SELECT news_category_id,COUNT(news_id) AS cnt FROM '.CMS_DB_PREFIX.'module_news WHERE news_category_id IN (';
    $q2 .= implode(',',$cat_ids).')';
    if( !empty($params['showarchive']) ) {
        $q2 .= ' AND (end_time < '.$now.') ';
    }
    else {
        $q2 .= ' AND ('.$db->IfNull('start_time',1)." < $now) AND (end_time IS NULL OR end_time > $now)";
    }
    $q2 .= ' AND status = \'published\' GROUP BY news_category_id';
    $tmp = $db->GetArray($q2);
    if( $tmp ) {
        for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
            $counts[$tmp[$i]['news_category_id']] = $tmp[$i]['cnt'];
        }
    }

    $rowcounter=0;
    $items = [];
    $depth = 1;
    for( $i = 0, $n = count($catinfo); $i < $n; $i++ ) {
      $row =& $catinfo[$i];
      $row['index'] = $rowcounter++;
      $row['count'] = $counts[$row['news_category_id']] ?? 0;
      $row['prevdepth'] = $depth;
      $depth = count(explode('.', $row['hierarchy']));
      $row['depth']=$depth;

      // changes so that parameters supplied to the tag
      // gets carried down through the links
      // screw pretty urls
      $parms = $params;
      unset($parms['browsecat']);
      unset($parms['category']);
      $parms['category_id'] = $row['news_category_id'];

      $pageid = (isset($params['detailpage']) && $params['detailpage']!='')?$params['detailpage']:$returnid;
      $mod = cms_utils::get_module('News');
      $row['url'] = $mod->CreateLink($id,'default',$pageid,$row['news_category_name'],$parms,'',true);
      $items[] = $row;
    }
    return $items;
  }

  public static function get_all_categories()
  {
    if( !self::$_categories_loaded ) {
      $db = CmsApp::get_instance()->GetDb();
      $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories ORDER BY hierarchy';
      $dbresult = $db->GetArray($query);
      if( $dbresult ) self::$_cached_categories = $dbresult;
      self::$_categories_loaded = TRUE;
    }
    return self::$_cached_categories;
  }

  public static function get_category_list()
  {
    self::get_all_categories();
    $categorylist = [];
    for( $i = 0, $n = count(self::$_cached_categories); $i < $n; $i++ ) {
      $row = self::$_cached_categories[$i];
      $categorylist[$row['long_name']] = $row['news_category_id'];
    }
    return $categorylist;
  }

  public static function get_category_names_by_id()
  {
    self::get_all_categories();
    $list = [];
    for( $i = 0, $n = count(self::$_cached_categories); $i < $n; $i++ ) {
      $list[self::$_cached_categories[$i]['news_category_id']] = self::$_cached_categories[$i]['news_category_name'];
    }
    return $list;
  }

  public static function get_category_name_from_id($id)
  {
    self::get_all_categories();
    for( $i = 0, $n = count(self::$_cached_categories); $i < $n; $i++ ) {
      if( $id == self::$_cached_categories[$i]['news_category_id'] ) {
        return self::$_cached_categories[$i]['news_category_name'];
      }
    }
  }

/*
  public static function get_fielddefs($publiconly = TRUE)
  {
    if( !is_array(self::$_cached_fielddefs) ) {
      $db = CmsApp::get_instance()->GetDb();
      if( $publiconly ) {
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE public = 1 ORDER BY item_order';
      } else {
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_fielddefs ORDER BY item_order';
      }
      $tmp = $db->GetArray($query);

      self::$_cached_fielddefs = [];
      if( $tmp ) {
        for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
          self::$_cached_fielddefs[$tmp[$i]['id']] = $tmp[$i];
        }
      }
    }
    return self::$_cached_fielddefs;
  }


  public static function &get_field_from_row($row)
  {
    $res = null;
    if( !isset($row['id']) ) return $res;

    $res = new Field();
    foreach( $row as $key => $value ) {
      switch( $key ) {
      case 'id':
      case 'name':
      case 'type':
      case 'max_length':
      case 'item_order':
      case 'public':
      case 'value':
        $res->$key = $value;
        break;
      }
    }
    return $res;
  }
*/

  public static function fill_article_from_formparams(Article &$news,$params,$handle_uploads = FALSE,$handle_deletes = FALSE)
  {
    $cz = cms_config::get_instance()['timezone'];
    $tz = new DateTimeZone($cz);
    $dt = new DateTime(null, $tz);
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
      case 'useexp':
      case 'extra':
        $news->$key = $value;
        break;

      case 'category':
        $list = self::get_category_names_by_id();
        for( $i = 0, $n = count(self::$_cached_categories); $i < $n; $i++ ) {
          if( $value == self::$_cached_categories[$i]['news_category_name'] )
            $news->category_id = self::$_cached_categories[$i]['news_category_id'];
        }
        break;

      case 'fromdate':
        $st = strtotime($value);
        if ($st !== false) {
            if (!empty($params['fromtime'])) {
                $stt = strtotime($params['fromtime'], 0);
                if ($stt !== false) {
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
        if ($st !== false) {
            if (!empty($params['totime'])) {
                $stt = strtotime($params['totime'], 0);
                if ($stt !== false) {
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
/*
    if( isset($params['customfield']) && is_array($params['customfield']) ) {
      $fielddefs = self::get_fielddefs();
      foreach( $params['customfield'] as $key => $value ) {
        if( !isset($fielddefs[$key]) ) continue;

        $field = self::get_field_from_row($fielddefs[$key]);
        $field->value = $value;
        $news->set_field($field);
      }
    }
*/
    return $news;
  }

  private static function &get_article_from_row($row) //,$get_fields = 'PUBLIC')
  {
    if( !is_array($row) ) return;
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
        $article->startdate = $value;
        break;

      case 'end_time':
        $article->enddate = $value;
        break;

      case 'status':
        $article->status = $value;
        break;

      case 'create_date':
        $article->create_date = $value;
        break;

      case 'modified_date':
        $article->modified_date = $value;
        break;

      case 'author_id':
        $article->author_id = $value;
        break;

      case 'news_extra':
        $article->extra = $value;
        break;

      case 'news_url':
        $article->news_url = $value;
        break;
      }
    }
/*
    if( $get_fields && $get_fields != 'NONE' && $article->id ) {
      self::preloadFieldData($article->id);
      $fields = self::get_fields($article->id);
      if( $fields ) {
        foreach( $fields as $field ) {
          $article->set_field($field);
        }
      }
    }
*/
    return $article;
  }

  public static function &get_latest_article($for_display = TRUE)
  {
    $db = CmsApp::get_instance()->GetDb();
    $now = time();
    $query = 'SELECT mn.*, mnc.news_category_name FROM '.CMS_DB_PREFIX.'module_news mn LEFT OUTER JOIN '.CMS_DB_PREFIX."module_news_categories mnc ON mnc.news_category_id = mn.news_category_id WHERE status = 'published' AND ";
    $query .= '('.$db->IfNull('start_time',1)." < $now) AND end_time IS NULL OR end_time > $now) ";
    $query .= 'ORDER BY start_time DESC LIMIT 1';
    $row = $db->GetRow($query);

    return self::get_article_from_row($row,($for_display)?'PUBLIC':'ALL');
  }

  public static function &get_article_by_id($article_id,$for_display = TRUE,$allow_expired = FALSE)
  {
    $db = CmsApp::Get_instance()->GetDb();
    $now = time();
    $query = 'SELECT mn.*, mnc.news_category_name FROM '.CMS_DB_PREFIX.'module_news mn
LEFT OUTER JOIN '.CMS_DB_PREFIX.'module_news_categories mnc ON mnc.news_category_id = mn.news_category_id
WHERE status = \'published\' AND news_id = ? AND ('.$db->ifNull('start_time',1).' < '.$now.')';
    if( !$allow_expired ) {
      $query .= 'AND (end_time IS NULL OR end_time > '.$now.')';
    }
    $row = $db->GetRow($query, [$article_id]);

    $res = null;
    if( !$row ) return $res;

    return self::get_article_from_row($row,($for_display)?'PUBLIC':'ALL');
  }

/*
  public static function preloadFieldData($ids)
  {
    if( !is_array($ids) && is_numeric($ids) ) $ids = [$ids];

    $tmp = [];
    for( $i = 0, $nn = count($ids); $i < $nn; $i++ ) {
      $n = (int)$ids[$i];
      if( $n < 0 ) continue;
      if( is_array(self::$_cached_fieldvals) && isset(self::$_cached_fieldvals[$n]) ) continue;
      $tmp[] = $n;
    }
    if( !$tmp ) return;
    sort($tmp);
    $idlist = array_unique($tmp);

    $fielddefs = self::get_fielddefs();
    if( !$fielddefs ) return;

    $db = CmsApp::get_instance()->GetDb();
    $query = 'SELECT A.news_id,A.fielddef_id,A.value FROM '.CMS_DB_PREFIX.'module_news_fieldvals A
INNER JOIN '.CMS_DB_PREFIX.'module_news_fielddefs B ON A.fielddef_id = B.id
WHERE news_id IN ('.implode(',',$idlist).') ORDER BY A.news_id,B.item_order';
    $dbr = $db->GetArray($query);
    if( !$dbr ) return;

    // initialization.
    if( !is_array(self::$_cached_fieldvals) ) self::$_cached_fieldvals = [];
    foreach( $idlist as $news_id ) {
      if( isset(self::$_cached_fieldvals[$news_id]) ) continue;

      self::$_cached_fieldvals[$news_id] = [];
      foreach( $fielddefs as $field ) {
        $obj = new Field();
        foreach( $field as $k => $v ) {
          $obj->$k = $v;
        }
        $obj->value = null;
        self::$_cached_fieldvals[$news_id][$field['id']] = $obj;
      }
    }

    // fill with values.
    foreach( $dbr as $row ) {
      $news_id = $row['news_id'];
      $flddef_id = $row['fielddef_id'];
      $value = $row['value'];

      if( !isset(self::$_cached_fieldvals[$news_id][$flddef_id]) ) continue;
      self::$_cached_fieldvals[$news_id][$flddef_id]->value = $value;
    }
  }


  public static function get_fields($news_id,$public_only = true,$filled_only = FALSE)
  {
    if( $news_id <= 0 ) return;
    $fd = self::get_fielddefs();
    if( !$fd ) return;

    $results = [];
    foreach( $fd as $field ) {
      $obj = null;
      if( isset(self::$_cached_fieldvals[$news_id][$field['id']]) ) {
        $obj = self::$_cached_fieldvals[$news_id][$field['id']];
      }
      else {
        // data for this field must not have been preloaded.
        // means there is no value, so just build one
        $obj = new Field();
        foreach( $field as $k => $v ) {
          $obj->$k = $v;
        }
        $obj->value = null;
      }
      $results[$field['name']] = $obj;
    }

    foreach( self::$_cached_fieldvals[$news_id] as $fid => $data ) {
      if( !$public_only || $data->public ) {
        if( !$filled_only || (isset($data->value) && $data->value != '') ) {
          $results[$data->name] = $data;
        }
      }
    }
    return $results;
  }
*/
} // class
