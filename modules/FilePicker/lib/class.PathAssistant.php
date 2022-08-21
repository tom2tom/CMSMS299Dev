<?php
/*
Class which ...
Copyright (C) 2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace FilePicker;

use CMSMS\AppConfig;
use LogicException;
use const CMS_ROOT_PATH;
use const CMS_ROOT_URL;
use const CMS_UPLOADS_URL;
use function cms_join_path;
use function startswith;

class PathAssistant
{
    private $_topdir;
    private $_topurl;

    public function __construct(AppConfig $config, $topdir)
    {
        if (!$topdir || !is_dir($topdir)) throw new LogicException("Invalid topdir-value '$topdir' provided to ".__METHOD__);
        if (!$this->is_relative_to($topdir, CMS_ROOT_PATH)) throw new LogicException("'$topdir' is not a descendant of '".CMS_ROOT_PATH."' in ".__METHOD__);

//      if (endswith($topdir, DIRECTORY_SEPARATOR)) $topdir = substr($topdir,0,-1);
        $this->_topdir = rtrim($topdir, ' '.DIRECTORY_SEPARATOR);

        // now, look at the image uploads path, the image path, the admin path, and the root path
        if ($this->is_relative_to($this->_topdir, $config['image_uploads_path'])) {
            $rel_url = $this->to_relative_sub($this->_topdir, $config['image_uploads_path']);
            $this->_topurl = $config['image_uploads_url'].'/'.$rel_url;
        }
        elseif ($this->is_relative_to($this->_topdir, $config['uploads_path'])) {
            $rel_url = $this->to_relative_sub($this->_topdir, $config['uploads_path']);
            $this->_topurl = CMS_UPLOADS_URL.'/'.$rel_url;
        }
        elseif ($this->is_relative_to($this->_topdir, $config['admin'])) {
            $rel_url = $this->to_relative_sub($this->_topdir, $config['admin']);
            $this->_topurl = $config['admin_url'].'/'.$rel_url;
        }
        elseif ($this->is_relative_to($this->_topdir, CMS_ROOT_PATH)) {
            $rel_url = $this->to_relative_sub($this->_topdir, CMS_ROOT_PATH);
            $this->_topurl = CMS_ROOT_URL.'/'.$rel_url;
        }
    }

    protected function to_relative_sub($path_a, $path_b)
    {
        $path_a = realpath($path_a);
        $path_b = realpath($path_b);
        if (!(is_dir($path_a) || is_file($path_a))) throw new LogicException("Invalid path_a-value '$path_a' provided to ".__METHOD__);
        if (!is_dir($path_b)) throw new LogicException("Invalid path_b-value '$path_b' provided to ".__METHOD__);

        if (!$this->is_relative_to($path_a, $path_b)) throw new LogicException("'$path_a' is not a descendant of '$path_b' in ".__METHOD__);
        $out = substr($path_a, strlen($path_b));
        $out = ltrim($out, ' \/');
        return $out;
    }

    public function get_top_url()
    {
        return $this->_topurl;
    }

    public function is_relative_to($path_a, $path_b)
    {
        $path_a = realpath($path_a);
        $path_b = realpath($path_b);
        if (!$path_a || !$path_b) return false;
        return startswith($path_a, $path_b);
    }

    public function is_relative($path)
    {
        return $this->is_relative_to($path, $this->_topdir);
    }

    public function to_relative($path)
    {
        return $this->to_relative_sub($path, $this->_topdir);
    }

    public function to_absolute($relative)
    {
        return cms_join_path($this->_topdir, $relative);
    }

    public function relative_path_to_url($relative)
    {
        $prefix = rtrim($this->get_top_url(), ' /');
        $relative = trim($relative, ' \/');
        if ($relative) {
            return $prefix . '/' . strtr($relative, '\\', '/');
        }
        return $prefix;
    }

    public function is_valid_relative_path($path)
    {
        $absolute = $this->to_absolute(trim($path));
        return $this->is_relative($absolute);
    }

    /**
     * Get the extension of the specified file
     * @since 2.0
     * @param string $path Filesystem path, or at least the basename, of a file
     * @param bool $lower Optional flag, whether to lowercase the result. Default TRUE.
     * @return string, lowercase if $lower is true or not set
     */
    public function get_extension($path, $lower = TRUE)
    {
        $p = strrpos($path, '.');
        if( !$p ) { return ''; } // none or at start
        $ext = substr($path, $p + 1);
        if( $lower) {
            if( function_exists('mb_strtolower') ) {
                return mb_strtolower($ext);
            }
            return strtolower($ext);
        }
        return $ext;
    }

    /**
     * Get a variant of the supplied $path with definitely-lowercase filename extension
     * @since 2.0
     * @param string $path Filesystem path, or at least the basename, of a file
     * @return string
     */
    public function lower_extension($path)
    {
        $ext = $this->get_extension($path);
        if ($ext !== '') {
            $p = strrpos($path, '.');
            return substr($path, 0, $p + 1) . $ext;
        }
        return $path;
    }
} // class
