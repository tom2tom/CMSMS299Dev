<?php

namespace AdminSearch;

use CMSMS\SystemCache;
use CMSMS\Utils;
use function cms_htmlentities;
use function get_userid;

final class Tools
{
//  private function __construct() {}

  public static function get_slave_classes()
  {
    $key = 'slaves'.get_userid(false);
    $cache = SystemCache::get_instance();
    $results = $cache->get($key,self::class);
    if( !$results ) {
      // cache needs populating
      //TODO force upon module installation
      $results = [];

      // get module search-slaves
      $mod = Utils::get_module('AdminSearch');
      $modulelist = $mod->GetModulesWithCapability('AdminSearch');
      if( $modulelist ) {
        foreach( $modulelist as $module_name ) {
          $mod = Utils::get_module($module_name);
          if( !is_object($mod) ) {
              continue;
          }
          if( !method_exists($mod,'get_adminsearch_slaves') ) {
              continue;
          }
          $classlist = $mod->get_adminsearch_slaves();
          if( $classlist ) {
            foreach( $classlist as $class_name ) {
/* TODO don't assume all slave-classes can be namespaced (if not supplied as such)
              if( strpos($class_name,'\\') === false ) {
                  $class_name = $module_name.'\\'.$class_name;
              }
*/
              $obj = new $class_name();
              if( !is_object($obj) ) {
                  continue;
              }
              if( !is_subclass_of($class_name,'AdminSearch\\Slave') ) {
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

  public static function summarize($text,$len = 255) : string
  {
    $text = strip_tags($text);
    return substr($text,0,$len);
  }

  /**
   * Format a match for on-page display
   * The returned text will be up to 50 bytes around the matching text, with
   * collapsed newlines, and include a 'search_oneresult'-classed span around
   * the matching text.
   * @since 1.2
   *
   * @param string $needle the specified search-text
   * @param string $haystack the text which includes $needle
   * @param int $haypos byte-offset of the start of $needle in $haystack
   * @return string sanitized html ready for display
   */
  public static function contextize(string $needle,string $haystack,int $haypos): string
  {
    $p = max(0,$haypos-25);
    $pre = substr($haystack,$p,$haypos-$p); //TODO whole-chars if mb
    if( $pre ) {
        //smart-collapse
        $pre = str_replace(
          ["\r\n","\r","\n"],
          [' ',' ',' '],
          $pre);
        $parts = explode(' ',$pre,2);
        if( isset($parts[1]) ) {
          $pre = ltrim($parts[1]);
        }
    }
    $len = strlen($needle);
    $p = min(strlen($haystack),$haypos+$len+25);
    $post = substr($haystack,$haypos+$len,$p-$haypos-$len); //TODO whole-chars if mb
    if( $post ) {
        //smart-collapse
        $post = str_replace(
          ["\r\n","\r","\n"],
          [' ',' ',' '],
          $post);
        $parts = explode(' ',$post);
        if( ($n = count($parts)) > 1 ) {
          unset($parts[$n-1]);
          $post = rtrim(join(' ',$parts));
        }
    }

    $match = str_replace(
      ["\r\n","\r","\n"],
      [' ',' ',' '],
      $needle);
    if( $len + strlen($pre) + strlen($post) > 50 ) { //TODO whole-chars if mb
      $parts = explode(' ',$match);
      if( ($n = count($parts)) > 1) {
        $i = 0;
        $k = (int)($n / 2);
        $x = $y = '';
        do {
            $x .= ' '.$parts[$i];
            $j = $n - 1 - $i;
            $y = $parts[$j].' '.$y;
            ++$i;
        } while( $i < $k && strlen($x) + strlen($y) < 22 );
        if( $i < $k ) {
          $match = ltrim($x).' &hellip; '.rtrim($y);
        }
      }
      elseif( $len > 25) { //TODO whole-chars if mb
        $match = substr($match,0,11).' &hellip; '.substr($match,$len-11);
      }
    }
    //sanitize and format for display
    $match = '<span class="search_oneresult">'.cms_htmlentities($match).'</span>';
    $text = cms_htmlentities($pre).$match.cms_htmlentities($post);
    return $text;
  }
}
