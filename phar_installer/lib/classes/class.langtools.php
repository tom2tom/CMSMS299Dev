<?php
namespace cms_installer;

//use cms_installer\installer_base;
use cms_installer\request;
use cms_installer\session;
use Exception;
use function cms_installer\nls;

class langtools_Exception extends Exception
{
}

final class langtools
{
    const DFLT_REALM = '__:DFLT:__';

    private static $_instance;

    private $_allowed_languages;
    private $_dflt_language;
    private $_cur_language;
    private $_langdata;
    private $_realm = '__:DFLT:__';

    private function __construct()
    {
    }

    public static function get_instance() : self
    {
        if (!is_object(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Get the language(s) that the browser allows
     *
     * @return array of hashes. Each element of the array will have
     *  members lang and priority, where priority is between 0 and 1
     */
    public function get_browser_langs() : array
    {
        $request = request::get_instance();
        $langs = $request->accept_language();
        $tmp = explode(',', $langs);

        $out = [];
        for ($i = 0, $n = count($tmp); $i < $n; ++$i) {
            $tmp2 = explode(';q=', $tmp[$i], 2);
            if ($tmp2[0] == '' || $tmp2[0] == '*') {
                continue;
            }
            $priority = (!empty($tmp2[1])) ? (float)$tmp2[1] : 1.0;
            $out[] = ['lang' => $tmp2[0], 'priority' => $priority];
        }

        if ($out) {
            array_multisort(array_column($out, 'priority'), SORT_ASC, SORT_NUMERIC, array_column($out, 'lang'), SORT_ASC, SORT_LOCALE_STRING, $out);
        }
        return $out;
    }

    /**
     * Test if a language is available
     *
     * @param string The language identifier/locale
     * @return boolean
     */
    public function language_available(string $str) : bool
    {
        $obj = (new nlstools())->find($str);
        return is_object($obj);
    }

    /**
     * Get the list of installer translations
     *
     * @return array of available language-locales c.f. en_US
     */
    public function get_available_languages() : array
    {
        return (new nlstools())->get_list();
    }

    /**
     * Set the allowed languages.
     *
     * @param mixed string one, or several comma-delimited, language(s), or array of them
     * @return void
     */
    public function set_allowed_languages($data)
    {
        if (!is_array($data)) {
            $data = explode(',', $data);
        }

        $out = [];
        for ($i = 0, $n = count($data); $i < $n; ++$i) {
            if ($this->language_available($data[$i])) {
                $out[] = $data[$i];
            }
        }

        if (!$out) {
            throw new langtools_Exception(__METHOD__.': no wanted language is available');
        }
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
     * @return boolean
     *  true if no allowed languages are set,
     *  true if the specified language is allowed,
     *  false if not in the allowed list.
     */
    public function language_allowed(string $str) : bool
    {
        if ($this->_allowed_languages) {
            return in_array($str, $this->_allowed_languages);
        }
        return true;
    }

    /**
     * Find the first allowed language that the browser supports
     *
     * @return mixed string | null
     */
    public function match_browser_lang()
    {
        $langs = $this->get_browser_langs();
        if (is_array($langs) && ($n = count($langs))) {
            $ops = new nlstools();
            for ($i = 0; $i < $n; ++$i) {
                $obj = $ops->find($langs[$i]['lang']); // does alias lookup.
                if ($obj) {
                    // it's available, check if it's allowed
                    if ($this->language_allowed($obj->name())) {
                        return $obj->name();
                    }
                }
            }
        }
    }

    /**
     * Set the default language
     *
     * @param string language name.
     * @throws langtools_Exception if the specified language is not available, or not allowed.
     */
    public function set_default_language(string $str)
    {
        if (!$this->language_available($str) || !$this->language_allowed($str)) {
            throw new langtools_Exception($str.' may not be set as the default language');
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
        if ($this->_dflt_language) {
            return $this->_dflt_language;
        }
        throw new langtools_Exception('No default language is set');
    }

    /**
     * Get the user's selected language.
     * May use advanced methods to store the user's selected language or
     * retrieve it from cookies, session variables, or the request, or
     * from ini data.
     *
     * @return mixed string | null
     */
    public function get_selected_language()
    {
        $request = request::get_instance();
        $session = session::get_instance();

        // get the user's preferred language.
        $lang = $request['curlang'] ?? null; // it's stored in the get or post
        if (!$lang && isset($session['current_language'])) {
            $lang = $session['current_language'];
        } // it's stored in the session
        if (!$lang) {
            $lang = $this->match_browser_lang();
        } // get it from the browser

        return $lang;
    }

    /**
     * Set the current language
     * This method sets the 'current' language, and also updates the
     * locale for the selected language.
     *
     * @param string the requested language
     * @throws langtools_Exception if the specified language is not available or allowed
     */
    public function set_current_language($str)
    {
        if (!$this->language_available($str) || !$this->language_allowed($str)) {
            throw new langtools_Exception('default language is not in list of allowed langages');
        }

        $this->_cur_language = $str;
        $obj = (new nlstools())->find($str);
        $locale = $obj->locale();
        if (!is_array($locale)) {
            $locale = explode(',', $locale);
        }
        $old = setlocale(LC_ALL, '0');
        $tmp = setlocale(LC_ALL, $locale);
        if ($tmp === false) {
            setlocale(LC_ALL, $old);
        }
    }

    /**
     * Get the current language
     *
     * @return string The current language, if set, otherwise the default language.
     * @throws langtools_Exception if the current language and the default language have not been set
     */
    public function get_current_language()
    {
        if (!$this->_cur_language) {
            if (!$this->_dflt_language) {
                throw new langtools_Exception('Cannot get language, no default set');
            }
            return $this->_dflt_language;
        }
        return $this->_cur_language;
    }

    /**
     * Get a hash of languages suitable for display in a dropdown
     *
     * @return mixed array | null
     */
    public function get_language_list($langs)
    {
        $outp = null;
        foreach ($langs as $one) {
            $tmp = nls()->find($one);
            if (!is_object($tmp)) {
                continue;
            }

            if (!is_array($outp)) {
                $outp = [];
            }
            $outp[$one] = $tmp->display();
        }
        return $outp;
    }

    /**
     * Set the selected language
     * This method may store the selected language in the session, or a cookie etc.
     *
     * @param string The user selected language
     */
    public function set_selected_language($str)
    {
        if (!$this->language_available($str)) {
            throw new langtools_Exception('Cannot set selected language \''.$str.'\'. It is not available.');
        }
        if (!$this->language_allowed($str)) {
            throw new langtools_Exception('Cannot set selected language \''.$str.'\'. It is not allowed.');
        }
        $session = session::get_instance();
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
        if (!$str) {
            $str = self::DFLT_REALM;
        }
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
     * @param string The realm name. If empty, the default realm is assumed.
     * @return string
     */
    public function get_lang_dir($realm = '')
    {
        if (!$realm || $realm == self::DFLT_REALM) {
            $realm = 'app';
        }
        $dir = dirname(__DIR__).DIRECTORY_SEPARATOR.'lang/'.$realm;
        if (!is_dir($dir)) {
            throw new langtools_Exception('Language directory '.$dir.' not found');
        }
        return $dir;
    }

    /**
     * Load a language realm
     *
     * @param string, The realm name.    If empty the default realm is assumed.
     * @return array of translated lang strings.
     */
    public function load_realm($realm = '')
    {
        $dir = $this->get_lang_dir($realm).DIRECTORY_SEPARATOR;
        $cur = DIRECTORY_SEPARATOR.$this->get_current_language().'.php';
        $lang = []; // populate this by inclusions
        foreach ([
            $dir.'en_US.php',
            $dir.'ext'.$cur,
            $dir.'custom'.$cur,
        ] as $fn) {
            if (is_file($fn)) {
                include_once $fn;
            }
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
        if (!$realm) {
            $realm = self::DFLT_REALM;
        }
        if (isset($this->_langdata[$realm])) {
            unset($this->_langdata[$realm]);
        }
    }

    /**
     * Translate a string
     * Uses the current realm, and the currently selected language.
     *
     * @param mixed - uses sprintf formatting,
     * @return string
     */
    public function translate(...$args) : string
    {
        if (count($args) == 1 && is_array($args[0])) {
            $args = $args[0];
        }

        $key = array_shift($args);
        if (!$key) {
            return '-- Missing Language Key --';
        }

        if (!$this->_langdata) {
            $this->_langdata = [];
        }
        if (!isset($this->_langdata[$this->_realm])) {
            $this->_langdata[$this->_realm] = $this->load_realm($this->_realm);
        }

        if (!isset($this->_langdata[$this->_realm][$key])) {
            return '-- Missing Language String - '.$key.' --';
        } elseif ($args) {
            return vsprintf($this->_langdata[$this->_realm][$key], $args);
        } else {
            return $this->_langdata[$this->_realm][$key];
        }
    }
} // class
