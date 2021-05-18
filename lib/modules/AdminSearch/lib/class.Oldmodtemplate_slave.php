<?php

namespace AdminSearch;

use CMSMS\TemplateOperations;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use function check_permission;
use function cmsms;
use function get_userid;

final class Oldmodtemplate_slave extends Slave
{
  public function get_name()
  {
    $mod = Utils::get_module('AdminSearch');
    return $mod->Lang('lbl_oldmodtemplate_search');
  }

  public function get_description()
  {
    $mod = Utils::get_module('AdminSearch');
    return $mod->Lang('desc_oldmodtemplate_search');
  }

  public function check_permission()
  {
    return check_permission(get_userid(),'Modify Templates'); //tho' no redirect to edit templates from returned match-data
  }

  //returns array or null
  public function get_matches()
  {
    $needle = $this->get_text();
    $db = cmsms()->GetDb();
    $query = 'SELECT originator,name,content FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE originator IS NOT NULL AND originator != \'\' AND originator != \'__CORE__\' AND content LIKE ?'; //other originators are for module-templates
    if( $this->search_casesensitive() ) {
      //$query .= ` WHERE `column` LIKE CONVERT('value' USING utf8mb4) COLLATE utf8mb4_bin;
	  //$parms = [$txt, $txt];
      $fname = 'stripos'; // TODO handle mb_* matches
    }
    else {
      //$query .= ' WHERE P.content LIKE ? OR C.metadata LIKE ? GROUP BY C.content_id'
	  //$wm = '%' . Utils::escape_wildsql($needle) . '%';
	  //$parms = [$wm, $wm];
      $fname = 'strpos'; // TODO handle mb_* matches
	}

	$wm = '%' . Utils::escape_wildsql($needle) . '%';
    $dbr = $db->GetArray($query, [$wm]);
    if( $dbr ) {
      $output = [];

      foreach( $dbr as $row ) {
        // here we could actually have a smarty template to build the description.
        $pos = $fname($row['content'],$needle); // TODO loop while any match
        if( $pos !== false ) {
          $html = Tools::contextize($needle, $row['content'], $pos);
          //unlike other slaves, no 'description' or 'edit_url' reported
          $output[] = [
           'title'=>$row['originator'].' + '.$row['name'],
           'text'=>$html
          ];
        }
      }

      return $output;
    }

  }

  public function get_section_description()
  {
    $mod = Utils::get_module('AdminSearch');
    return $mod->Lang('sectiondesc_oldmodtemplates');
  }
} // class
