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

    public function get_list() : array
    {
        $this->load_nls();
        return array_keys(self::$_nls);
    }

    public function find(string $str)
    {
        $this->load_nls();
        foreach (self::$_nls as $locale => &$nls) {
            if ($locale == $str) {
                return $nls;
            }
            if ($nls->matches($str)) {
                return $nls;
            }
        }
        unset($nls);
    }

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
        if (is_array(self::$_nls)) {
            return;
        }
        /*
         Find all nls classes in their directory (intra-phar globbing N/A)
         They are named like class.en_US.nls.php

         To constrain the classes to language-codes per ISO 639-1, 639-2, 639-3
         and country codes per ISO ISO 3166-1, 3166-2, 3166-3
         (tho' the latter 2 are unlikely to be found here),
         regex pattern = '/^class\.[a-z]{2,}_[0-9A-Z]{2,4}(\.nls)?\.php$/'
        */
        $iter = new RegexIterator(
            new DirectoryIterator(__DIR__.DIRECTORY_SEPARATOR.'nls'),
            '/^class\..+\.nls\.php$/'
        );

        self::$_nls = [];
        $type = __NAMESPACE__.'\nls';
        foreach ($iter as $inf) {
            $filename = $inf->getFilename();
            $locale = substr($filename, 6, -8);
            $classpath = $type.'\\'.$locale.'_nls'; // like en_US_nls

            $file = $inf->getPathname();
            include_once $file;
            $obj = new $classpath();
            if ($obj instanceof $type) {
                self::$_nls[$locale] = $obj;
            } else {
                unset($obj);
            }
        }
    }
} // class
