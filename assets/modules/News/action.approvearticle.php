<?php
/*
Approve for publication action for CMSMS News module.
Copyright (C) 2005-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\Events;
use CMSMS\Utils;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Approve News') ) exit;

if( !isset($params['approve']) || !isset($params['articleid']) ) {
    throw new Exception('Missing action-parameter(s)');
}

switch( $params['approve'] ) {
  case 0:
    $status = 'draft';
    break;
  case 1:
    $status = 'published';
    break;
  default:
    throw new Exception('Unknown value for action-parameter');
}

$now = time();
$articleid = (int)$params['articleid'];
$search = Utils::get_search_module();
// Get the record
if( is_object($search) ) {
    if( $status == 'draft' ) {
        $search->DeleteWords($this->GetName(),$articleid,'article');
    }
    elseif( $status == 'published' ) {
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news WHERE news_id = ?';
        $article = $db->getRow($query,[$articleid]);
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

        if( $t_end > $now || $this->GetPreference('expired_searchable',1) == 1 ) {
            $text = $article['news_data'] . ' ' . $article['summary'] . ' ' . $article['news_title'] . ' ' . $article['news_title'];
            $search->AddWords($this->GetName(), $articleid, 'article', $text,
		     ($useexp && $this->GetPreference('expired_searchable',0) == 0) ? $t_end : NULL);
        }
    }
}

$uquery = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status = ?,modified_date = ? WHERE news_id = ?';
$longnow = $db->DbTimeStamp($now,false);
$db->execute($uquery,[$status,$longnow,$articleid]);
Events::SendEvent('News', 'NewsArticleEdited', ['news_id'=>$articleid, 'status'=>$status]);
$this->SetMessage($this->Lang('msg_success'));
$this->Redirect($id, 'defaultadmin', $returnid);
