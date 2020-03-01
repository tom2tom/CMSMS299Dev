<?php
namespace FilePicker;

use cms_utils;

// store in the session the CWD for every instance of a filepicker for each request
// this might be said to pollute the session, but meh we can deal with that later.
class TemporaryInstanceStorage
{
    private function __construct() {}

    public static function set($sig,$val)
    {
        $val = trim($val); // make sure it's a string
        $key = cms_utils::hash_string(__FILE__);
        $_SESSION[$key][$sig] = $val;
        return $sig;
    }

    public static function get($sig)
    {
        $key = cms_utils::hash_string(__FILE__);
        if( isset($_SESSION[$key][$sig]) ) return $_SESSION[$key][$sig];
    }

    public static function clear($sig)
    {
        $key = cms_utils::hash_string(__FILE__);
        if( isset($_SESSION[$key][$sig]) ) unset($_SESSION[$key][$sig]);
    }

    public static function reset()
    {
        $key = cms_utils::hash_string(__FILE__);
        if( isset($_SESSION[$key]) ) unset($_SESSION[$key]);
    }
}
