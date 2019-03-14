<?php

namespace cms_installer;

use cms_installer\installer_base;
use cms_installer\request;
use cms_installer\session;
use Exception;
use function cms_installer\nls;

class langtools_Exception extends Exception {}

final class langtools
{
  const DFLT_REALM = '__:DFLT:__';
  private static $_instance;
  private $_allowed_languages;
  private $_dflt_language;
  private $_cur_language;
  private $_langdata;
  private $_realm = '__:DFLT:__';

  protected function __construct() {}

  public static function get_instance() : self
  {
    if( !is_object(self::$_instance) ) self::$_instance = new self();
    return self::$_instance;
  }


  /**
   *
   * @param cms_installer\langtools $obj
   */
  public static function set_translator(langtools &$obj)
  {
    self::$_instance = $obj;
  }


  /**
   * Get the language(s) that the browser allows
   *
   * @return array of hashes.  Each element of the array will have members lang, and priority, where priority is between 0 and 1
   */
  public static function get_browser_langs() : array
  {
    $request = request::get();
    $langs = $request->accept_language();
    $tmp = explode(',',$langs);

    $out = [];
    for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
      $tmp2 = explode(';q=',$tmp[$i],2);
      if( $tmp2[0] == '' || $tmp2[0] == '*' ) continue;
      $priority = ( !empty($tmp2[1]) ) ? (float)$tmp2[1] : 1.0;
      $out[] = ['lang'=>$tmp2[0],'priority'=>$priority];
    }

    if( $out ) {
        array_multisort(array_column($out,'priority'),SORT_ASC,SORT_NUMERIC,array_column($out,'lang'),SORT_ASC,SORT_LOCALE_STRING,$out);
    }
    return $out;
  }


  /**
   * Test if a language is available
   *
   * @param string The language naem
   * @return boolean
   */
  public function language_available(string $str) : bool
  {
    $tmp = nlstools::get_instance()->find($str);
    return ( is_object($tmp) );
  }


  /**
   * Get the list of available languages
   *
   * @return array of available language-codes c.f. en_US
   */
  public function get_available_languages() : array
  {
    $list = nlstools::get_instance()->get_list();
    foreach( $list as &$one ) {
      $one = substr($one,0,-4);
    }
    unset($one);
    return $list;
  }


  /**
   * Set the allowed languages.
   *
   * @param mixed String of comma delimited languages, or array of languages
   * @return void
   */
  public function set_allowed_languages($data)
  {
    if( !is_array($data) ) $data = explode(',',$data);

    $out = [];
    for( $i = 0, $n = count($data); $i < $n; $i++ ) {
      if( $this->language_available($data[$i]) ) $out[] = $data[$i];
    }

    if( !$out ) throw new langtools_Exception('set_allowed_languages no matches with available languages');

    $this->_allowed_languages = $out;
  }


  /**
   * Get list of allowed languages
   *
   * @return array of language strings
   */
  public function get_allowed_languages() : array
  {
    return $this->_allowed_languages;
  }


  /**
   * Test if a language is allowed
   *
   * @param string language string
   * @return boolean TRUE if no allowed languages are set, TRUE if the specified language is allowed, false if not in the allowed list.
   */
  public function language_allowed(string $str) : bool
  {
    if( $this->_allowed_languages ) {
      return ( in_array($str,$this->_allowed_languages) );
    }
    return TRUE;
  }


  /**
   * Find the first allowed language that the browser supports
   *
   * @return mixed lang string, or null
   */
  public function match_browser_lang()
  {
    $langs = $this->get_browser_langs();
    if( is_array($langs) && ($n = count($langs)) ) {
      for( $i = 0; $i < $n; $i++ ) {
        $obj = nlstools::get_instance()->find($langs[$i]['lang']); // does alias lookup.
        if( $obj ) {
          // it's available... now check if it's allowed.
          if( $this->language_allowed($obj->name()) ) return $obj->name();
        }
      }
    }
  }


  /**
   * Set the default language
   * Throws an exception if the specified language is not available, or not allowed.
   *
   * @param string language name.
   */
  public function set_default_language(string $str)
  {
    if( !$this->language_available($str) || !$this->language_allowed($str) ) {
      throw new langtools_Exception('default language is not in list of allowed langages');
    }

    $this->_dflt_language = $str;
  }


  /**
   * Get the default language
   * Throws an exception of no default language has been set.
   *
   * @return string
   */
  public function get_default_language() : string
  {
    if( !$this->_dflt_language ) throw new langtools_Exception('cannot get the default language, if it is not set');

    return $this->_dflt_language;
  }


  /**
   * Get the users selected language.  May use advanced methods to store the users selected language
   * or retrieve it from cookies, session variables, or the request, or from ini data.
   *
   * @virtual
   * @return mixed string | null
   */
  public function get_selected_language()
  {
    $request = request::get();
    $session = session::get();

    // get the users preferred language.
    $lang = null;
    if( isset($request['curlang']) ) $lang = $request['curlang']; // it's stored in the get (or post)
    if( !$lang && isset($session['current_language']) ) $lang = $session['current_language']; // it's stored in the session
    if( !$lang ) $lang = $this->match_browser_lang(); // not set anywhere. get it from the browser.

    // match available languages.
    return $lang;
  }


  /**
   * Set the current language
   * This method sets the 'current' language, and also updates the locale for the selected language.
   *
   * @virtual
   * @param string the requested language
   * @throws langtools_Exception if the specified language is not available or allowed
   */
  public function set_current_language($str)
  {
    if( !$this->language_available($str) || !$this->language_allowed($str) ) {
      throw new langtools_Exception('default language is not in list of allowed langages');
    }

    $this->_cur_language = $str;
    $obj = nlstools::get_instance()->find($str);
    $locale = $obj->locale();
    if( !is_array($locale) ) $locale = explode(',',$locale);
    $old = setlocale(LC_ALL,'0');
    $tmp = setlocale(LC_ALL,$locale);
    if( $tmp === FALSE ) setlocale(LC_ALL,$old);
  }


  /**
   * Get the current language
   *
   * @virtual
   * @returns string The current language, if set, otherwise the default language.
   * @throws langtools_Exception if the current language and the default language have not been set
   */
  public function get_current_language()
  {
    if( !$this->_cur_language ) {
      if( !$this->_dflt_language ) throw new langtools_Exception('Cannot get language, no default set');
      return $this->_dflt_language;
    }
    return $this->_cur_language;
  }

  /**
   * Get a hash of languages suitable for display in a dropdown
   *
   * @returns mixed array | null
   */
  public function get_language_list($langs)
  {
    $outp = null;
    foreach( $langs as $one ) {
      $tmp = nls()->find($one);
      if( !is_object($tmp) ) continue;

      if( !is_array($outp) ) $outp = [];
      $outp[$one] = $tmp->display();
    }
    return $outp;
  }

  /**
   * Set the selected language
   * This method may store the selected language in the session, or a cookie etc.
   *
   * @virtual
   * @param string The user selected language
   */
  public function set_selected_language($str)
  {
    if( !$this->language_available($str) ) throw new langtools_Exception('cannot set selected language to a language that is not available');
    if( !$this->language_allowed($str) ) throw new langtools_Exception('cannot set selected language to a language that is not allowed');

    $session = session::get();
    $session['current_language'] = $str;
    $this->set_current_language($str);
  }

  /**
   * Set the language realm
   *
   * @param string the realm name, if empty the default realm will be used.
   */
  public function set_realm($str = '')
  {
    if( !$str ) $str = self::DFLT_REALM;
    $this->_realm = $realm;
  }

  /**
   * Return the current realm name
   *
   * @return string
   */
  public function get_realm()
  {
    return $this->_realm;
  }


  /**
   * Return the absolute path to the language directory.
   * Throws an exception if the realm directory does not exist.
   *
   * @param string The realm name.  If empty, the default realm can be assumed.
   * @returns string
   */
  public function get_lang_dir($realm = '')
  {
    if( !$realm ) $realm = self::DFLT_REALM;
    if( $realm == self::DFLT_REALM ) $realm = 'app';
    $dir = installer_base::get_assetsdir().'/lang/'.$realm;
    if( !is_dir($dir) ) throw new langtools_Exception('Language directory '.$dir.' not found');

    return $dir;
  }


  /**
   * Load a language realm.
   *
   * @param string, The realm name.  If empty the default realm is assumed.
   * @return array of translated lang strings.
   */
  public function load_realm($realm = '')
  {
    // load the realm.
    $dir = $this->get_lang_dir($realm);
    $cur = $this->get_current_language();
    $fns = [];
    $fns[] = $dir.'/en_US.php';
    $fns[] = $dir.'/ext/'.$cur.'.php';
    $fns[] = $dir.'/custom/'.$cur.'.php';

    $lang = [];
    foreach( $fns as $fn ) {
      if( is_file($fn) ) include_once $fn;
    }

    return $lang;
  }

  /**
   * Unload the realm
   *
   * @param string, The realm name. If empty, the default realm is assumed.
   */
  public function unload_realm($realm = '')
  {
    if( !$realm ) $realm = self::DFLT_REALM;
    if( isset($this->_langdata[$realm]) ) unset($this->_langdata[$realm]);
  }

  /**
   * Translate a string
   * uses the current realm, and the currently selected language.
   *
   * @param mixed - uses sprintf formatting,
   * @return string
   */
  public function translate(...$args)
  {
    if( count($args) == 0 ) return;
    if( count($args) == 1 && is_array($args[0]) ) $args = $args[0];

    if( !$this->_langdata ) $this->_langdata = [];
    if( !isset($this->_langdata[$this->_realm]) ) $this->_langdata[$this->_realm] = $this->load_realm($this->_realm);

    // check to see if the key is available.
    $key = array_shift($args);
    if( !$key ) return;

    if( !isset($this->_langdata[$this->_realm][$key]) ) {
      return '-- Missing Languagestring - '.$key.' --';
    }
    else if( $args ) {
      return vsprintf($this->_langdata[$this->_realm][$key], $args);
    }
    else {
      return $this->_langdata[$this->_realm][$key];
    }
  }
} // class


function lang(...$args)
{
  try {
    return langtools::get_instance()->translate($args);
  }
  catch( Exception $e ) {
    // nothing here.
  }
}
