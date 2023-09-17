<?php
namespace cms_installer;

use Exception;
use function cms_installer\get_server_permissions;
use function cms_installer\lang;

abstract class filehandler
{
    private $_destdir = '';
    private $_excludes = null;
    private $_languages = [];
    private $_output_fn = null;

    public function set_destdir(string $destdir): void
    {
        if (!is_dir($destdir)) {
            throw new Exception(lang('error_dirnotvalid', $destdir));
        }
        if (!is_writable($destdir)) {
            throw new Exception(lang('error_dirnotvalid', $destdir));
        }
        $this->_destdir = $destdir;
    }

    public function get_destdir(): string
    {
        if (!$this->_destdir) {
            throw new Exception(lang('error_nodestdir'));
        }
        return $this->_destdir;
    }

    public function set_languages(/*mixed */$lang): void
    {
        if (is_array($lang)) {
            $this->_languages = $lang;
        } elseif ($lang) {
            $this->_languages = [$lang];
        } else {
            $this->_languages = [];
        }
    }

    public function get_languages(): array
    {
        return $this->_languages;
    }

    public function set_output_fn($fn): void
    {
        if (is_callable($fn)) {
            $this->_output_fn = $fn;
        } else {
            throw new Exception(lang('error_internal', 'fh102'));
        }
    }

    public function output_string(string $txt): void
    {
        if ($this->_output_fn) {
            call_user_func($this->_output_fn, $txt);
        }
    }

    /**
     * @param string $filespec site-root-relative filepath, with leading separator
     * @param string $srcspec phar-URI corresponding to $filespec (i.e. phar://....)
     */
    abstract public function handle_file(string $filespec, string $srcspec);

    protected function get_config(): array
    {
        return get_app()->get_config();
    }

    /**
     * @param string $filespec site-root-relative filepath, but with leading separator (or empty string)
     * @return boolean
     * @throws Exception
     */
    protected function is_excluded(string $filespec): bool
    {
        $filespec = trim($filespec);
//        if (!$filespec) throw new Exception(lang('error_internal', 'fh104'));
        if ($this->_excludes === null) {
            $config = $this->get_config();
            if (empty($config['install_excludes'])) {
                $this->_excludes = [];
            } else {
                $this->_excludes = explode('||', $config['install_excludes']);
            }
        }
        if ($this->_excludes) {
            foreach ($this->_excludes as $excl) {
                if (preg_match($excl, $filespec)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param string $filespec site-root-relative filepath, but with leading separator
     * @return boolean
     * @throws Exception
     */
    protected function dir_exists(string $filespec): bool
    {
        $filespec = trim($filespec);
        if (!$filespec) {
            throw new Exception(lang('error_invalidparam', 'filespec'));
        }
        $dn = dirname($filespec);
        $tmp = $this->get_destdir().$dn;
        return is_dir($tmp);
    }

    /**
     * @param string $filespec site-root-relative filepath, but with leading separator
     * @return boolean
     * @throws Exception
     */
    protected function create_directory(string $filespec): bool
    {
        $filespec = trim($filespec);
        if (!$filespec) {
            throw new Exception(lang('error_invalidparam', 'filespec'));
        }
        $dn = dirname($filespec);
        $tmp = $this->get_destdir().$dn;
        $dirmode = get_server_permissions()[3]; // read+write+acess
        return @mkdir($tmp, $dirmode, true);
    }

    /**
     * @param string $filespec site-root-relative filepath, but with leading separator
     * @return boolean
     */
    protected function is_imagefile(string $filespec): bool
    {
        // this method uses (ugly) extensions because we cannot rely on finfo_open being available.
        // see also FileTypeHelper _image_extensions property which has more extensions
        $image_exts = ['bmp', 'jpg', 'jpeg', 'gif', 'png', 'svg', 'webp', 'ico'];
        $ext = strtolower(substr(strrchr($filespec, '.'), 1));
        return in_array($ext, $image_exts);
    }

    /**
     * @param string $filespec site-root-relative filepath, but with leading separator
     * @return string maybe empty
     * @throws Exception
     */
    protected function is_langfile(string $filespec): string
    {
        $filespec = trim($filespec);
        if (!$filespec) {
            throw new Exception(lang('error_invalidparam','filespec'));
        }
        $pchk = substr_compare($filespec,'.php', -4, 4) === 0;
        if (!($pchk || substr_compare($filespec, '.js', -3, 3) === 0)) {
            return '';
        }
        $bn = basename($filespec);
        if ($pchk) {
            //CMSMS-used locale identifiers have all been like ab_CD
            //valid identifiers are not confined to that pattern
            //e.g. {2,} is valid, but currently unused and in some tests catches too many files
            //see https://www.unicode.org/reports/tr35
            //To constrain the classes to language-codes per ISO 639-1, 639-2, 639-3
            //and country codes per ISO ISO 3166-1, 3166-2, 3166-3
            //(tho' the latter 2 are unlikely to be found here)
            //regex pattern = '/^[a-z]{2,}_[0-9A-Z]{2,4}(\.nls)?\.php$/'
            if (preg_match('/^[a-zA-Z]{2}_[a-zA-Z]{2}\.nls\.php$/',$bn)) {
                return substr($bn, 0, -8);
            }
            if (preg_match('/^[a-zA-Z]{2}_[a-zA-Z]{2}\.php$/', $bn)) { // ditto
                //(lazily) confirm it's a CMSMS translation
                if (preg_match('~[\\/]lang[\\/]en_US.php$~',$filespec)) {
                    return 'en_US';
                }
                if (preg_match('~[\\/]lib[\\/]lang[\\/]\w+[\\/]en_US.php$~',$filespec)) {
                    return 'en_US';
                }
                if (preg_match('~[\\/]lang[\\/]ext[\\/]'.$bn.'$~',$filespec)) {
                    return substr($bn, 0, -4);
                }
                if (preg_match('~[\\/]lib[\\/]lang[\\/]\w+[\\/]ext[\\/]'.$bn.'$~',$filespec)) {
                    return substr($bn, 0, -4);
                }
            }
        }

        $nls = get_app()->get_nls(); // all possible translations
        if (!is_array($nls)) { return ''; } // problem, treat file as non-lang

        //PHPMailer translations are named like .../phpmailer.lang-pt.php
        if (strncmp($bn, 'phpmailer.lang-', 15) != 0) {
            $p = strpos($bn, '.');
            if ($p > 0) {
                $bn = substr($bn, 0, $p);
                $xchk = true;
            } else {
                return '';
            }
        }
        else {
          $p = strpos($bn, '.', 15);
          if ($p > 15) {
              $bn = substr($bn, 15, $p-15);
              $xchk = false;
          } else {
              return '';
          }
        }
        // TODO [a-zA-Z]{2,} is valid, but catches most files
        if( !preg_match('/^[a-zA-Z]{2}(_[a-zA-Z]{2})?$/', $bn)) {
            return '';
        }
        foreach( $nls['alias'] as $alias => $code ) {
            if( strcasecmp($bn, $alias) == 0 ) {
                return $code;
            }
        }
        foreach( $nls['htmlarea'] as $code => $short ) {
            if( strcasecmp($bn, $short) == 0 ) {
                return $code;
            }
        }
        if( $xchk && stripos($filespec, 'lang') === FALSE ) {
            return '';
        }
        return $bn; //maybe keep this one
    }

    /**
     * @param string $res value returned by is_langfile()
     * @return boolean
     * @throws Exception
     */
    protected function is_accepted_lang(string $res): bool
    {
        if (!$res) {
            return false;
        }
        $langs = $this->get_languages();
        if (!$langs || !is_array($langs)) {
            return true;
        }
        return in_array($res, $langs);
    }
}
