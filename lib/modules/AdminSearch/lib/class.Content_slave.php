<?php

namespace AdminSearch;

use CMSMS\ContentOperations;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use function check_permission;
use function cmsms;
use function get_userid;

final class Content_slave extends Slave
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
    return true;
  }

  public function get_matches()
  {
    $content_manager = Utils::get_module('CMSContentManager');
    $db = cmsms()->GetDb();
    $query = 'SELECT C.content_id, P.content FROM '.CMS_DB_PREFIX.'content C LEFT JOIN '.CMS_DB_PREFIX.'content_props P ON C.content_id = P.content_id WHERE P.content LIKE ? OR C.metadata LIKE ? GROUP BY C.content_id';
//TODO handle parent::_params which includes 'cased' for a case-sensitive search
    if( $this->search_casesensitive() ) {
      //$query .= ` WHERE `column` LIKE CONVERT('value' USING utf8mb4) COLLATE utf8mb4_bin;
	  //$parms = [$txt, $txt];
      $fname = 'stripos'; // TODO handle mb_* matches
    }
    else {
      //$query .= ' WHERE P.content LIKE "%?%" OR C.metadata LIKE "%?%" GROUP BY C.content_id'
	  //$wm = Utils::escape_wildsql($needle);
	  //$parms = [$wm, $wm];
      $fname = 'strpos'; // TODO handle mb_* matches
	}
    //$query = 'SELECT DISTINCT C.content_id, P.content FROM '.CMS_DB_PREFIX.'content C LEFT JOIN '.CMS_DB_PREFIX.'content_props P ON C.content_id = P.content_id WHERE P.content LIKE ? OR C.metadata LIKE ?';
    //$query = 'SELECT DISTINCT content_id,prop_name,content FROM '.CMS_DB_PREFIX.'content_props WHERE content LIKE ?';
  	$needle = $this->get_text();
    $txt = '%'.$needle.'%';
    $dbr = $db->GetArray($query, [ $txt, $txt ] );
    if( $dbr ) {
      $userid = get_userid();
//      $urlext = get_secure_param();
      $output = [];

      foreach( $dbr as $row ) {
        $content_id = $row['content_id'];
        if( !check_permission($userid,'Manage All Content') && !check_permission($userid,'Modify Any Page') &&
          !ContentOperations::get_instance()->CheckPageAuthorship($userid,$content_id) ) {
          // no access to this content page.
          continue;
        }

        $content_obj = ContentOperations::get_instance()->LoadContentFromId($content_id); //both types of Content object support HasSearchableContent() and Name();
        if( !is_object($content_obj) ) continue;
        if( !$content_obj->HasSearchableContent() ) continue;

        // here we could actually have a smarty template to build the description.
        $pos = $fname($row['content'],$needle); //TODO caseless|cased multi-byte match
        $html = '';
        if( $pos !== false ) { //TODO loop while
          $html = Tools::contextize($needle, $row['content'], $pos);
        }

        $tmp = [
         'title'=>$content_obj->Name(),
         'description'=>$content_obj->Name(),
         'edit_url'=>$content_manager->create_url('m1_','admin_editcontent','',['content_id'=>$content_id]),
         'text'=>$html,
        ];
        $output[] = $tmp;
      }

      return $output;
    }
  }
} // class
