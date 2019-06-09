<?php

namespace AdminSearch;

use cms_cache_handler;
use cms_utils;
use function get_userid;

final class tools
{
//  private function __construct() {}

  public static function get_slave_classes()
  {
    $key = 'slaves'.get_userid(FALSE);
    $cache = cms_cache_handler::get_instance();
    $results = $cache->get($key,self::class);
    if( !$results ) {
      // cache needs populating
      //TODO force upon module installation
      $results = [];

      // get module search-slaves
      $mod = cms_utils::get_module('AdminSearch');
      $modulelist = $mod->GetModulesWithCapability('AdminSearch');
      if( $modulelist ) {
        foreach( $modulelist as $module_name ) {
          $mod = cms_utils::get_module($module_name);
          if( !is_object($mod) ) {
              continue;
          }
          if( !method_exists($mod,'get_adminsearch_slaves') ) {
              continue;
          }
          $classlist = $mod->get_adminsearch_slaves();
          if( $classlist ) {
            foreach( $classlist as $class_name ) {
/* don't assume all slave-classes can be namespaced (if not supplied as such)
              if( strpos($class_name,'\\') === FALSE ) {
                  $class_name = $module_name.'\\'.$class_name;
              }
*/
              $obj = new $class_name();
              if( !is_object($obj) ) {
                  continue;
              }
              if( !is_subclass_of($class_name,'AdminSearch\\slave') ) {
                  continue;
              }
              $name = $obj->get_name();
              if( !$name ) {
                  continue;
              }
              if( isset($results[$name]) ) {
                  continue;
              }
              $tmp = [
               'module'=>$module_name,
               'class'=>$class_name,
               'name'=>$name,
              ];
              $tmp['description'] = $obj->get_description();
              $tmp['section_description'] = $obj->get_section_description();
              $results[$name] = $tmp;
            }
          }
        }
        if( $results ) {
          //TODO proper UTF-8 sort
          ksort($results, SORT_LOCALE_STRING | SORT_FLAG_CASE);
        }
      }

      // cache the results
      $cache->set($key,$results,self::class);
    }

    return $results;
  }

  public static function summarize($text,$len = 255)
  {
    $text = strip_tags($text);
    return substr($text,0,$len);
  }
}
