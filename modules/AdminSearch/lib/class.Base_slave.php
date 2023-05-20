<?php
/*
Base class for searching for matches
Copyright (C) 2012-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file AdminSearch.module.php
*/
namespace AdminSearch;

use BadMethodCallException;
use OutOfBoundsException;
use function cms_to_bool;
use function CMSMS\specialize;

abstract class Base_slave
{
    /**
     * @ignore
     * @var string
     * Possibly-re-encoded search-target string, populated once-per-request
     */
    private static $needle = null;

    /**
     * @ignore
     * @var boolean
     * Whether this search (needle and/or haystack) includes non-UTF8 char(s)
     */
    private $_warnchars = false;

    /**
     * @ignore
     * @var array
     * Search parameters cache
     */
    private $_params = [];

    /**
     * Cache the (verbatim) search-target string
     *
     * @param string $text
     * @throws BadMethodCallException if $text is empty
     */
    public function set_text($text)
    {
        if ($text || is_numeric($text)) {
            $this->_params['search_text'] = $text; // verbatim, might need processing before use and/or display
            return;
        }
        throw new BadMethodCallException('Invalid parameter search_text');
    }

    /**
     * Cache some/all of the current search parameters
     *
     * @param array $params
     * @throws OutOfBoundsException if any supplied parameter is unrecognized
     */
    public function set_params($params)
    {
        foreach ($params as $key => &$value) {
            switch ($key) {
            // valid keys
            case 'search_text': //not (yet?) processed in any way
              break;
            case 'search_descriptions':
            case 'search_casesensitive':
            case 'verbatim_search':
            case 'search_fuzzy':
            case 'save_search':
              $value = cms_to_bool($value);
              break;
            default:
              throw new OutOfBoundsException('Invalid parameter '.$key.' in search params');
            }
        }
        unset($value);

        $this->_params = $params;
    }

    /**
     * Report whether the current needle and/or haystack includes non-UTF-8 char(s)
     * @since 1.2
     *
     * @return boolean
     */
    public function has_badchars() : bool
    {
        return $this->_warnchars;
    }

    /**
     * Report whether to use this slave in the currrent search
     *  (i.e. the specified or current user is authorized)
     * @since 1.2
     * @abstract
     *
     * @param int $uid the enumerator of the user conducting the search
     *  or 0 to retrieve such value on demand. Default 0.
     * @return boolean
     */
    public function use_slave(int $userid = 0) : bool
    {
        return true;
    }

    /**
     * Report whether editing the item in which match(es) is/are found is allowed
     * @abstract
     *
     * @param int $userid Since 1.2 the enumerator of the user conducting
     *  the search, or 0 to retrieve such value on demand. Default 0.
     * @return boolean
     */
    abstract protected function check_permission(int $userid = 0);

    /**
     * Report an identifier for an item in which match(es) have been found
     * @abstract
     *
     * @return string
     */
    abstract public function get_name();

    /**
     * Report a short-form description for an item in which match(es) have been found
     * @abstract
     *
     * @return string
     */
    abstract public function get_description();

    /**
     * @abstract
     * @return array, containing arrays or empty
     */
    abstract public function get_matches();

    /**
     * Report a short-form description for a group|section of match(es)
     * @optional
     *
     * @return string
     */
    public function get_section_description() {}

    /**
     * @return mixed cached search-text | null
     */
    protected function get_text()
    {
        return $this->_params['search_text'] ?? null;
    }

    /**
     * Retrieve some/all of the cached search parameters
     * @since 1.2
     *
     * @param mixed $params string | string(s)[]
     * @return mixed wanted param value (string|bool) | array of them | null if not found
     */
    protected function get_params($params)
    {
        if (is_array($params)) {
            $ret = [];
            foreach ($params as $key) {
                if (isset($this->_params[$key])) {
                    $ret[$key] = $this->_params[$key];
                }
            }
            if ($ret) {
                return $ret;
            }
        } elseif (isset($this->_params[$params])) {
            return $this->_params[$params];
        }
    }

    /**
     * Report whether the current search also includes descriptions
     *
     * @return boolean
     */
    protected function search_descriptions()
    {
        if (isset($this->_params['search_descriptions'])) {
            return cms_to_bool($this->_params['search_descriptions']);
        }
        return false;
    }

    /**
     * Report whether the current search is case-sensitive
     *
     * @return boolean
     */
    protected function search_casesensitive()
    {
        if (isset($this->_params['search_casesensitive'])) {
            return cms_to_bool($this->_params['search_casesensitive']);
        }
        return false;
    }

    /**
     * Report whether the current search is fuzzy
     * @since 1.2
     *
     * @return boolean
     */
    protected function search_fuzzy()
    {
        if (isset($this->_params['search_fuzzy'])) {
            return cms_to_bool($this->_params['search_fuzzy']);
        }
        return false;
    }

    /**
     * Report whether the current search is verbatim
     * @since 1.2
     *
     * @return boolean
     */
    protected function search_verbatim()
    {
        if (isset($this->_params['verbatim_search'])) {
            return cms_to_bool($this->_params['verbatim_search']);
        }
        return false;
    }

    /**
     * Get a regex, derived from the supplied string, and which is
     * suitable for fuzzy matching
     * Adapted from https://codereview.stackexchange.com/questions/23899/faster-javascript-fuzzy-string-matching-function
     * @since 1.2
     *
     * @param string $needle the raw search string
     * @param bool $forphp optional flag whether to format for PHP (false for SQL) Default true
     * @return string regular expression
     */
    protected function get_regex_pattern(string $needle, bool $forphp = true) : string
    {
        $reserved = '/\\^-]'; // intra-class reserves
        $reserved2 = '/\\.,+-*?^$[](){}'; // extra-class reserves
        //UTF-8 has single bytes (0-127), leading bytes (192-254) and continuation bytes (128-191)
        if (preg_match('/[\xc0-\xfe][\x80-\xbf]+/', $needle)) {
            $chars = preg_split('//u', $needle, null, PREG_SPLIT_NO_EMPTY);
            $tail = ($forphp) ? '/u' : '';
        } else {
            $chars = str_split($needle);
            $tail = ($forphp) ? '/' : '';
        }
        if ($forphp && !$this->search_casesensitive()) {
            $tail .= 'i';
        }
        $c = $chars[0];
        if (strpos($reserved2, $c) !== false) {
            $c = "\\$c";
        }
        $t = $forphp ? "/$c" : $c;
        unset($chars[0]);
        $patn = array_reduce($chars, function($m, $c) use ($reserved, $reserved2) {
            $a = strpos($reserved, $c) !== false;
            $b = strpos($reserved2, $c) !== false;
            if ($a && $b) {
                return $m . "[^\\$c]{0,3}\\$c";
            } elseif ($a) {
                return $m . "[^\\$c]{0,3}$c";
            } elseif ($b) {
                return $m . "[^$c]{0,3}\\$c";
            } else {
                return $m . "[^$c]{0,3}$c";
            }
        }, $t);
        $patn .= $tail;
        return $patn;
    }

    /**
     * Get displayable details of matches (if any) in $haystack
     * @since 1.2
     *
     * @param string $haystack text to be scanned for match(es)
     * @return string html presenting match-results, or maybe empty
     */
    protected function get_matches_info(string $haystack) : string
    {
        $rawneedle = $this->_params['search_text'] ?? ''; // not sanitized etc
        if (!($rawneedle || is_numeric($rawneedle))) {
            return ''; // ignore attempt to find nothing
        }
        if (self::$needle === null) {
/*          if (!preg_match('/[\xc0-\xfe][\x80-\xbf]+/', $rawneedle)) {
                $rawneedle = $this->forceUTF($rawneedle); maybe worth repetition as we guess more ??
            }
*/
            self::$needle = $rawneedle;
        }
        if (!($this->_warnchars || preg_match('/[\xc0-\xfe][\x80-\xbf]+/', self::$needle))) {
            $this->_warnchars = true;
        }

        $html = [];
        if ($this->search_fuzzy()) {
            $patn = $this->get_regex_pattern(self::$needle); //e.g. "/κ[^έ]*?έ[^θ]*?θ[^ι]*?ι[^ ]*? [^δ]*?δ[^έ]*?έ[^ο]*?ο/ui"
            $matches = [];
            if (preg_match_all($patn, $haystack, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $found) {
                   $html[] = self::contextize($found[0], $haystack, $found[1]);
                }
            }
        } else {
            $vb = $this->search_verbatim();
            $cs = $this->search_casesensitive();
            if ($vb || !preg_match('/[\x80-\xFF]/', self::$needle)) {
                // use str[i]pos for matching
                $fname = ($cs) ? 'strpos' : 'stripos';
            } elseif (!$vb && preg_match('/[\x80-\xFF]/', $haystack)) {
                if (!$this->_warnchars) {
                    $this->_warnchars = (bool)preg_match('/[\xc0-\xfe][\x80-\xbf]+/', $haystack);
                }
                return ''; // no possible match
            } else {
                if (!preg_match('/[\xc0-\xfe][\x80-\xbf]+/', $haystack)) {
//                  $haystack = $this->forceUTF($haystack);
                    $this->_warnchars = true;
                }
                $fname = ($cs) ? 'strpos' : 'mb_stripos';
            }

            $pos = $fname($haystack, self::$needle); // TODO bytes- or chars-offset
            while ($pos !== false) {
                $html[] = self::contextize(self::$needle, $haystack, $pos);
                $pos = $fname($haystack, self::$needle, $pos + 1);
            }
        }

        if ($html) {
            return implode('<br>', $html);
        }
        return '';
    }

    /**
     * Get a shortened variant of $text
     * @since 1.2 Migrated from Tools class
     *
     * @param string $text
     * @param int $len
     * @return string
     */
    protected function summarize(string $text, int $len = 255) : string
    {
        $text = strip_tags($text);
        return substr($text, 0, $len);
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
     * @param int $haypos byte-offset of the start of $needle in $haystack TODO char-offset from mb_*()
     * @return string sanitized html ready for display
     */
    protected function contextize(string $needle, string $haystack, int $haypos): string
    {
//TODO ensure that it does not split multi-byte chars
//TODO case-sensitivity, UTF8 conversion (if wanted & possible)
//TODO use wordwrap() if sensible
        $p = max(0, $haypos - 25);
        $pre = substr($haystack, $p, $haypos - $p); //TODO whole-chars if mb
        if ($pre) {
            //smart-collapse
            $pre = str_replace(
          ["\r\n", "\r", "\n"],
          [' ', ' ', ' '],
          $pre);
            $parts = explode(' ', $pre, 2);
            if (isset($parts[1])) {
                $pre = ltrim($parts[1]);
            }
        }
        $len = strlen($needle);
        $p = min(strlen($haystack), $haypos + $len + 25);
        $post = substr($haystack, $haypos + $len, $p - $haypos - $len); //TODO whole-chars if mb
        if ($post) {
            //smart-collapse
            $post = str_replace(
          ["\r\n", "\r", "\n"],
          [' ', ' ', ' '],
          $post);
            $parts = explode(' ', $post);
            if (($n = count($parts)) > 1) {
                unset($parts[$n - 1]);
                $post = rtrim(join(' ', $parts));
            }
        }

        $match = str_replace(
         ["\r\n", "\r", "\n"],
         [' ', ' ', ' '],
         substr($haystack, $haypos, $len)); //maybe this is not exactly $needle
        if ($len + strlen($pre) + strlen($post) > 50) { //TODO whole-chars if mb
            $parts = explode(' ', $match);
            if (($n = count($parts)) > 1) {
                $i = 0;
                $k = (int)($n / 2);
                $x = $y = '';
                do {
                    $x .= ' '.$parts[$i];
                    $j = $n - 1 - $i;
                    $y = $parts[$j].' '.$y;
                    ++$i;
                } while ($i < $k && strlen($x) + strlen($y) < 22);
                if ($i < $k) {
                    $match = ltrim($x).' &hellip; '.rtrim($y);
                }
            } elseif ($len > 25) { //TODO whole-chars if position found by mb_str*()
                $match = substr($match, 0, 11).' &hellip; '.substr($match, $len - 11);
            }
        }
        //sanitize and format for display
        $match = '<span class="search_oneresult">'.specialize($match).'</span>';
        $text = specialize($pre).$match.specialize($post);
        return $text;
    }
}
