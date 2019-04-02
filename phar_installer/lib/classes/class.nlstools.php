<?php

namespace cms_installer;

use DirectoryIterator;
use RegexIterator;

/**
 * Class of methods for working with installer (as distinct from intalled) translations
 */
final class nlstools
{
//  private static $_instance;
    //TODO namespaced global variables here
    /**
     * Array, each member like locale=>nls-derived object
     * @ignore
     */
    private static $_nls;

//  private function __construct() {}

    /* *
     * Get an instance of this class.
     * @deprecated since 1.4 use new nlstools() instead
     * @return self
     */
/*    public static function get_instance() : self
    {
//      if( !self::$_instance ) { self::$_instance = new self(); } return self::$_instance;
        return new self();
    }
*/
    private function load_nls()
    {
        if( is_array(self::$_nls) ) return;

        // find all nls classes in their directory (intra-phar globbing N/A)
        // they are named like class.en_US.nls.php
        $iter = new RegexIterator(
            new DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR.'nls'),
            '/^class\..+\.nls\.php$/'
        );

        self::$_nls = [];
        $type = __NAMESPACE__.'\\nls';
        foreach( $iter as $inf ) {
            $filename = $inf->getFilename();
            $locale = substr($filename,6,-8);
            $classpath = $type.'\\'.$locale.'_nls'; // like en_US_nls

            $file = $inf->getPathname();
            include_once $file;
            $obj = new $classpath();
            if( $obj instanceof $type ) {
                self::$_nls[$locale] = $obj;
            } else {
                unset($obj);
            }
        }
    }

    public function get_list() : array
    {
        $this->load_nls();
        return array_keys(self::$_nls);
    }

    public function &find(string $str)
    {
        $this->load_nls();
        foreach( self::$_nls as $locale => &$nls ){
            if( $locale == $str ) return $nls;
            if( $nls->matches($str) ) return $nls;
        }
        unset($nls);
        $obj = null;
        return $obj;
    }
} // class
