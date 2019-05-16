<?php
# Class: content searcher for News module
# Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace News;

use AdminSearch\slave;
use AdminSearch\tools;
use cms_utils;
use const CMS_DB_PREFIX;
use function check_permission;
use function cms_htmlentities;
use function cmsms;
use function get_userid;

final class AdminSearch_slave extends slave
{
  public function get_name()
  {
    $mod = cms_utils::get_module('News');
    return $mod->Lang('lbl_adminsearch');
  }

  public function get_description()
  {
    $mod = cms_utils::get_module('News');
    return $mod->Lang('desc_adminsearch');
  }

  public function check_permission()
  {
    $userid = get_userid();
    return check_permission($userid,'Modify News');
  }

  public function get_matches()
  {
    $mod = cms_utils::get_module('News');
    if( !is_object($mod) ) return;
    $db = cmsms()->GetDb();
    // need to get the fielddefs of type textbox or textarea
//    $query = 'SELECT id FROM '.CMS_DB_PREFIX.'module_news_fielddefs WHERE type IN (?,?)';
//    $fdlist = $db->GetCol($query,['textbox','textarea']);

    $fields = ['N.*'];
    $joins = [];
    $where = ['news_title LIKE ?','news_data LIKE ?','summary LIKE ?'];
    $str = '%'.$this->get_text().'%';
    $parms = [$str,$str,$str];
/*
    // add in fields
    for( $i = 0, $n = count($fdlist); $i < $n; $i++ ) {
      $text = 'FV'.$i;
      $fdid = $fdlist[$i];
      $fields[] = "$text.value";
      $joins[] = 'LEFT JOIN '.CMS_DB_PREFIX."module_news_fieldvals $text ON N.news_id = $text.news_id AND $text.fielddef_id = $fdid";
      $where[] = "$text.value LIKE ?";
      $parms[] = $str;
    }
*/
    // build the query.
    $query = 'SELECT '.implode(',',$fields).' FROM '.CMS_DB_PREFIX.'module_news N';
    if( $joins ) $query .= ' ' . implode(' ',$joins);
    if( $where ) $query .= ' WHERE '.implode(' OR ',$where);
    $query .= ' ORDER BY N.modified_date DESC';

    $dbr = $db->GetArray($query,[$parms]);
    if( $dbr ) {
      // got some results.
      $output = [];
      foreach( $dbr as $row ) {
        $text = null;
        foreach( $row as $key => $value ) {
          // search for the keyword
          $pos = strpos($value,$this->get_text());
          if( $pos !== FALSE ) {
            // build the text
            $start = max(0,$pos - 50);
            $end = min(strlen($value),$pos+50);
            $text = substr($value,$start,$end-$start);
            $text = cms_htmlentities($text);
            $text = str_replace($this->get_text(),'<span class="search_oneresult">'.$this->get_text().'</span>',$text);
            $text = str_replace(["\r\n","\r","\n"],[' ',' ',' '],$text);
            break;
          }
        }
        $url = $mod->create_url('m1_','editarticle','',['articleid'=>$row['news_id']]);
        $tmp = [
         'title'=>$row['news_title'],
         'description'=>tools::summarize($row['summary']),
         'edit_url'=>$url,
         'text'=>$text
        ];
		$output[] = $tmp;
      }
      return $output;
    }
  }
} // class
