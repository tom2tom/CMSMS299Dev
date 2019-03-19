<?php

namespace AdminSearch;

use cms_utils;
use CmsLayoutTemplate;
use const CMS_DB_PREFIX;
use function check_permission;
use function cms_htmlentities;
use function cmsms;
use function get_userid;

final class oldmodtemplate_slave extends slave
{
  public function get_name()
  {
    $mod = cms_utils::get_module('AdminSearch');
    return $mod->Lang('lbl_oldmodtemplate_search');
  }

  public function get_description()
  {
    $mod = cms_utils::get_module('AdminSearch');
    return $mod->Lang('desc_oldmodtemplate_search');
  }

  public function check_permission()
  {
    return check_permission(get_userid(),'Modify Templates'); //tho' no redirect to edit templates from returned match-data
  }

  //returns array or null
  public function get_matches()
  {
    $db = cmsms()->GetDb();
    $query = 'SELECT originator,name,content FROM '.CMS_DB_PREFIX.LayoutTemplateOperations::TABLENAME.' WHERE originator IS NOT NULL AND originator != \'\' AND content LIKE ?';
    $dbr = $db->GetArray($query,['%'.$this->get_text().'%']);
    if( $dbr ) {
      $output = [];

      foreach( $dbr as $row ) {
        // here we could actually have a smarty template to build the description.
        $pos = strpos($row['content'],$this->get_text());
        if( $pos !== FALSE ) {
          $start = max(0,$pos - 50);
          $end = min(strlen($row['content']),$pos+50);
          $text = substr($row['content'],$start,$end-$start);
          $text = cms_htmlentities($text);
          $text = str_replace($this->get_text(),'<span class="search_oneresult">'.$this->get_text().'</span>',$text);
          $text = str_replace(["\r\n","\r","\n"],[' ',' ',' '],$text);

          //unlike other slaves, no 'description' or 'edit_url' reported
          $output[] = [
		   'title'=>$row['originator'].' + '.$row['name'],
		   'text'=>$text
		  ];
        }
      }

      return $output;
    }

  }

  public function get_section_description()
  {
    $mod = cms_utils::get_module('AdminSearch');
    return $mod->Lang('sectiondesc_oldmodtemplates');
  }
} // class
