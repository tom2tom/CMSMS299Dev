<?php
/*
Approve for publication action for CMSMS News module.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Events;

if( !isset($gCms) ) exit();
if( !$this->CheckPermission('Approve News') ) exit;

if( !isset($params['approve']) || !isset($params['articleid']) ) {
  die('missing parameter, this should not happen');
}

$articleid = (int)$params['articleid'];
$search = cms_utils::get_search_module();
$status = '';
$now = time();
$uquery = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status = ?,modified_date = ? WHERE news_id = ?';
switch( $params['approve'] ) {
 case 0:
   $status = 'draft';
   break;
 case 1:
   $status = 'published';
   break;
 default:
   die('unknown value for approve parameter, I do not know what to do with this');
   break;
}

// Get the record
if( is_object($search) ) {
  if( $status == 'draft' ) {
    $search->DeleteWords($this->GetName(),$articleid,'article');
  }
  else if( $status == 'published' ) {
    $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news WHERE news_id = ?';
    $article = $db->GetRow($query,[$articleid]);
    if( !$article ) {
        $this->SetError($this->Lang('error_detailed', 'Record not found'));
        $this->Redirect($id, 'defaultadmin', $returnid);
    }

    if( $article['end_time'] != '' ) {
      $useexp = 1;
      $t_end = $article['end_time'];
    }
	else {
      $useexp = 0;
      $t_end = $now + 3600; // just for the math
	}

    if( $t_end > $now || $this->GetPreference('expired_searchble',1) == 1 ) {
      $text = $article['news_data'] . ' ' . $article['summary'] . ' ' . $article['news_title'] . ' ' . $article['news_title'];
      $search->AddWords($this->GetName(), $articleid, 'article', $text,
			($useexp == 1 && $this->GetPreference('expired_searchable',0) == 0) ? $t_end : NULL);
    }
  }
}

$db->Execute($uquery,[$status,$now,$articleid]);
Events::SendEvent( 'News', 'NewsArticleEdited', [ 'news_id'=>$articleid, 'status'=>$status ] );
$this->SetMessage($this->Lang('msg_success'));
$this->Redirect($id, 'defaultadmin', $returnid);
