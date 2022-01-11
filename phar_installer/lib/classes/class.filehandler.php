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

    public function set_destdir(string $destdir)
    {
        if (!is_dir($destdir)) {
            throw new Exception(lang('error_dirnotvalid', $destdir));
        }
        if (!is_writable($destdir)) {
            throw new Exception(lang('error_dirnotvalid', $destdir));
        }
        $this->_destdir = $destdir;
    }

    public function get_destdir() : string
    {
        if (!$this->_destdir) {
            throw new Exception(lang('error_nodestdir'));
        }
        return $this->_destdir;
    }

    public function set_languages($lang)
    {
        if (!is_array($lang)) {
            return;
        }
        $this->_languages = $lang;
    }

    public function get_languages() : array
    {
        return $this->_languages;
    }

    public function set_output_fn($fn)
    {
        if (!is_callable($fn)) {
            throw new Exception(lang('error_internal', f102));
        }
        $this->_output_fn = $fn;
    }

    public function output_string($txt)
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

    protected function get_config()
    {
        return get_app()->get_config();
    }

    /**
     * @param string $filespec site-root-relative filepath, but with leading separator (or empty string)
     * @return boolean
     * @throws Exception
     */
    protected function is_excluded(string $filespec) : bool
    {
        $filespec = trim($filespec);
//        if( !$filespec ) throw new Exception(lang('error_internal',f104));
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
    protected function dir_exists(string $filespec) : bool
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
    protected function create_directory(string $filespec) : bool
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
    protected function is_imagefile(string $filespec) : bool
    {
        // this method uses (ugly) extensions because we cannot rely on finfo_open being available.
        $image_exts = ['bmp', 'jpg', 'jpeg', 'gif', 'png', 'svg', 'webp', 'ico'];
        $ext = strtolower(substr(strrchr($filespec, '.'), 1));
        return in_array($ext, $image_exts);
    }

    /**
     * @param string $filespec site-root-relative filepath, but with leading separator
     * @return boolean
     * @throws Exception
     */
    protected function is_langfile(string $filespec) : bool
    {
        $filespec = trim($filespec);
        if (!$filespec) {
            throw new Exception(lang('error_invalidparam', 'filespec'));
        }
//        if( $this->is_imagefile($filespec) ) return false;
        $bn = basename($filespec);
        // support language-codes per ISO 639-1, 639-2, 639-3
        // and country codes per ISO ISO 3166-1, 3166-2, 3166-3 (the latter 2 unlikely to be found here)
        $fnmatch = preg_match('/^[a-z]{2,}_[0-9A-Z]{2,4}(\.nls)?\.php$/', $bn);
        if ($fnmatch) {
            return true;
        }

        $nls = get_app()->get_nls();
        if (!is_array($nls)) {
            return false; // problem
        }

        $bn = substr($bn, 0, strpos($bn, '.'));
        foreach ($nls['alias'] as $alias => $code) {
            if ($bn == $alias) {
                return (bool)$code;
            }
        }
        foreach ($nls['htmlarea'] as $code => $short) {
            if ($bn == $short) {
                return (bool)$code;
            }
        }

        return false;
    }

    /**
     * @param string $filespec site-root-relative filepath, but with leading separator
     * @return boolean
     * @throws Exception
     */
    protected function is_accepted_lang($filespec) : bool
    {
        $res = $this->is_langfile($filespec);
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
