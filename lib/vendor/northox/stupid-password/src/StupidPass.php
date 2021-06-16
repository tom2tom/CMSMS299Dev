<?php
/**
 * StupidPass - simple password-quality checker
 *
 * This class provides a simple way of preventing user from using easy to
 * guess/bruteforce password. It has been develop to get rid of the
 * *crack-lib PHP extension*.
 *
 * It provides simple, yet pretty effective password validation rules. The
 * library introduces 1337 speaking extrapolation. What we mean by this is
 * converting the supplied password to an exhaustive list of possible simple
 * alteration such as changing the letter a to @ or 4. The complete list of
 * alteration can be found below (section 1337 speak conversion table). This
 * list is then compared against common passwords based on researches done on
 * the latest password database breaches (stratfor, sony, phpbb, rockyou, myspace).
 * Additionally, it may validates the extent of use of multiple char types
 * (uppercase, lowercase, numeric, special). The later reduce drastically
 * the size of the common password list.
 * Additionally, it may evaluate password strength, using rules adapted
 * from Plesk Onyx and Obsidian, instead of individual attribute(s) length etc.
 *
 * @author Danny Fullerton - Mantor Organization www.mantor.org
 * @version 2.0
 * @license BSD
 *
 * Usage:
 *   $sp = new StupidPass();
 *   $sp = new StupidPass($maxlen, $environ, $dict, $lang, $params);
 *   $boolResult = $sp->validate($PasswordToTest);
 */
namespace StupidPass;

use Exception;

class StupidPass
{
    const DEFAULT_DICTIONARY = 'StupidPass.default.dict';

    private $params = array();
    private $original = null;
    private $pass = array();
    private $errors = array();
    private $minlen = 8; // No, this is not an option.
    private $maxlen = 0; // Password max byte-length. 0 = unlimited. Should be set according to your system.
    private $dict = null; // Path to the dictionary
    private $environ = array(); // Array of 'environmental' info such as the name of the company.
    private $lang = array(
        'length' => 'Password length must be between %s and %s characters inclusively',
        'minlength' => 'Password length must be at least %s characters',
        'upper' => 'Password must contain at least one uppercase character',
        'lower' => 'Password must contain at least one lowercase character',
        'numeric' => 'Password must contain at least one numeric character',
        'special' => 'Password must contain at least one special character',
        'common' => 'Password is too common',
        'environ' => 'Password uses identifiable information and is guessable',
        'onlynumeric' => 'Password must not be entirely numeric',
        'strength' => 'Password strength does not meet the \'%s\' criterion',
    );

    /**
     * StupidPass constructor.
     * @param int $maxlen Optional max valid password length (default 40, 0 for no limit)
     * @param string[] $environ Optional regex(s) to be explicitly disallowed
     * @param string $dict Optional rel. or absolute filepath of alternate dictionary file
     * @param string[] $lang Optional replacement error messages to report if a specific test fails
     * @param assoc. array $params Optional validation paramerers e.g. to disable or enable
     */
    public function __construct($maxlen = 40, $environ = array(), $dict = '', $lang = array(), $params = array())
    {
        if (is_array($params)) {
            $this->options = $params;
        } elseif ($params) {
            $this->options = array($params); //useless: what parameter ??
        }
        if (!isset($this->options['disable'])) {
            $this->options['disable'] = array();
        }
        if (!isset($this->options['maxlen-guessable-test'])) {
            $this->options['maxlen-guessable-test'] = 24;
        }
        $this->maxlen = max(0, (int)$maxlen);
        if (is_array($environ)) {
            $this->environ = $environ;
        } elseif ($environ) {
            $this->environ = array($environ);
        }
        if ($dict && is_file($dict)) {
            $this->dict = $dict;
        } else {
            $this->dict = realpath(__DIR__ . DIRECTORY_SEPARATOR . self::DEFAULT_DICTIONARY);
        }
        if ($lang) {
            $this->lang = $lang + $this->lang;
        }
    }

    /**
     * Validate a password based on the configuration in the constructor.
     * @param string $pass
     * @return bool true if validated, false if failed.  Call $this->getErrors() to retrieve the array of errors.
     * @throws DictionaryNotFoundException
     */
    public function validate($pass)
    {
        $this->errors = array();
        $this->original = $pass;

        if (!in_array('strength', $this->options['disable'])) {
            $this->strength();
        } else {
            if (!in_array('length', $this->options['disable'])) {
                $this->length();
            }
            if (!in_array('upper', $this->options['disable'])) {
                $this->upper();
            }
            if (!in_array('lower', $this->options['disable'])) {
                $this->lower();
            }
            if (!in_array('numeric', $this->options['disable'])) {
                $this->numeric();
            }
            if (!in_array('special', $this->options['disable'])) {
                $this->special();
            }
            if (!in_array('onlynumeric', $this->options['disable'])) {
                $this->onlyNumeric();
            }
        }

        $passLen = strlen($pass); // ok to treat multi-byte chars as bytes, here
        if ($passLen <= $this->options['maxlen-guessable-test']) {
            $this->extrapolate();
        }
        if (!in_array('environ', $this->options['disable'])) {
            $this->environmental();
        }
        if (!in_array('common', $this->options['disable'])) {
            $this->common();
        }

        $this->pass = null;

        return empty($this->errors);
    }

    /**
     * Retrieve an array of text from lang enumerating the errors.
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    private function length()
    {
        $passLen = strlen($this->original);
        if ($this->maxlen > 0) {
            if ($passLen < $this->minlen || $passLen > $this->maxlen) {
                $err = sprintf($this->lang['length'], $this->minlen, $this->maxlen);
                $this->errors[] = $err;
            }
        } elseif ($passLen < $this->minlen) {
            $err = sprintf($this->lang['minlength'], $this->minlen);
            $this->errors[] = $err;
        }
    }

    private function upper()
    {
        if (!preg_match('/[A-Z]/', $this->original)) {
            $this->errors[] = $this->lang['upper'];
        }
    }

    private function lower()
    {
        if (!preg_match('/[a-z]/', $this->original)) {
            $this->errors[] = $this->lang['lower'];
        }
    }

    private function numeric()
    {
        if (!preg_match('/\d/', $this->original)) {
            $this->errors[] = $this->lang['numeric'];
        }
    }

    private function onlyNumeric()
    {
        if (preg_match('/^\d+$/', $this->original)) {
            $this->errors[] = $this->lang['onlynumeric'];
        }
    }

    private function special()
    {
        if (!preg_match('/[\W_]/', $this->original)) {
            $this->errors[] = $this->lang['special'];
        }
    }

    private function environmental()
    {
        foreach ($this->environ as $env) {
            foreach ($this->pass as $pass) {
                if (preg_match("/$env/i", $pass) == 1) {
                    $this->errors[] = $this->lang['environ'];
                    return;
                }
            }
        }
    }

    private function strength()
    {
        /*
        Password strength validation rules, adapted from Plesk Onyx and Obsidian:
         Length at least 8 gains 14 points.
         Length at least 16 gains 6 points.
         At least one lower-case letter 'a'..'z' gains 1 point.
         At least one upper-case letter 'A'..'Z' gains 5 points.
         At least one byte > 0x80 gains 5 points, if such bytes are not disabled.
         At least one number gains 5 points.
         At least three numbers gains 5 points.
         At least one non-word char e.g. " ! @ # $ % ^ & * ? _ ~ gains 5 points.
         At least two non-word chars gains 5 points.
         Upper- plus lower-case letters gains 2 points.
         Letter(s) plus number(s) gains 2 points.
         Letter(s) plus number(s) plus non-word char(s) gains 2 points.
        */
        $pass = $this->original;
        $passLen = strlen($pass); // ok to treat multi-byte chars as bytes, here
        if ($passLen >= $this->minlen + 8) { $strength = 20; }
        elseif ($passLen >= $this->minlen) { $strength = 14; }
        else { $strength = 0; }

        $cs = preg_match_all('/[\W_]/', $pass);
        if ($cs) {
            if ($cs >= 2) { $strength += 10; }
            else { $strength += 5; } // $cs = 1
        }
        $cn = preg_match_all('/\d/', $pass);
        if ($cn) {
            if ($cn >= 3) { $strength += 10; }
            else { $strength += 5; } // $cn >= 1
        }
        $cl = preg_match_all('/[a-z]/', $pass);
        if ($cl) { $strength++; }
        $cu = preg_match_all('/[A-Z]/', $pass);
        if ($cu) { $strength += 5; }
        if (!in_array('non-ascii', $this->options['disable'])) {
            $cb = preg_match_all('/[0x80-0xff]/', $pass);
            if ($cb) { $strength += 5; }
        } else {
            $cb = 0;
        }
        if ($cl && ($cu || $cb)) { $strength += 2; }
        if ($cn && ($cl || $cu || $cb)) {
            $strength += 2;
            if ($cs) { $strength += 2; }
        }

        if (!isset($this->options['strength']) || !in_array($this->options['strength'], array(
            'Very Weak',
            'VeryWeak',
            'Weak',
            'Medium',
            'Strong',
            'Very Strong',
            'VeryStrong'))
           ) {
            $this->options['strength'] = 'Medium';
        }
        $s = $this->options['strength'];
        /*
        If the sum of points is:
         less than 5, the password is Very Weak.
         between 5 and 14, it is Weak.
         between 15 and 24, it is Medium.
         between 25 and 34, it is Strong.
         more than 34, it is Very Strong.
        */
        switch ($s) {
            case 'Weak':
                if ($strength < 5) {
                    $this->errors[] = sprintf($this->lang['strength'], $s);
                }
                break;
            case 'Medium':
                if ($strength < 15) {
                    $this->errors[] = sprintf($this->lang['strength'], $s);
                }
                break;
            case 'Strong':
                if ($strength < 25) {
                    $this->errors[] = sprintf($this->lang['strength'], $s);
                }
                break;
            case 'VeryStrong':
                $s = 'Very Strong';
                // no break here
            case 'Very Strong':
                if ($strength < 35) {
                    $this->errors[] = sprintf($this->lang['strength'], $s);
                }
                break;
        }
    }

    /**
     * @throws Exception
     */
    private function common()
    {
        if (!file_exists($this->dict)) {
            throw new Exception("Can't open dictionary file: " . $this->dict);
        }

        $fp = @fopen($this->dict, 'r');
        if (!$fp) {
            throw new Exception("Can't open dictionary file: " . $this->dict);
        }
        while (($buf = fgets($fp, 1024)) !== false) {
            $buf = rtrim($buf);
            foreach ($this->pass as $pass) {
                if ($pass == $buf) {
                    $this->errors[] = $this->lang['common'];
                    return;
                }
            }
        }
    }

    private function extrapolate()
    {
        // don't put too much stuff here, it has exponential performance impact.
        $leet = array(
            '@' => array('a', 'o'),
            '4' => array('a'),
            '8' => array('b'),
            '3' => array('e'),
            '1' => array('i', 'l'),
            '!' => array('i', 'l', '1'),
            '0' => array('o'),
            '$' => array('s', '5'),
            '5' => array('s'),
            '6' => array('b', 'd'),
            '7' => array('t')
        );
        $map = array();
        $pass_array = str_split(strtolower($this->original));
        foreach ($pass_array as $i => $ch) {
            $map[$i][] = $ch;
            foreach ($leet as $pattern => $replace) {
                if ($ch === (string)$pattern) {
                    for ($j = 0, $c = count($replace); $j < $c; $j++) {
                        $map[$i][] = $replace[$j];
                    }
                }
            }
        }
        $this->pass = $this->expand($map);
    }

    // expand all possible passwords recursively

    private function expand(&$map, $old = array(), $index = 0)
    {
        $xtras = array();
        foreach ($map[$index] as $ch) {
            $c = count($old);
            if ($c == 0) {
                $xtras[] = $ch;
            } else {
                for ($i = 0; $i < $c; $i++) {
                    $xtras[] = @$old[$i] . $ch;
                }
            }
        }
        unset($old);
        $r = ($index == count($map) - 1) ? $xtras : $this->expand($map, $xtras, $index + 1);

        return $r;
    }
}
