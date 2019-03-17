<?php

namespace PHPArchive;

use RuntimeException;

/**
 * Class FileInfo
 *
 * stores metadata about a file in an archive
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @package PHPArchive
 * @license MIT
 */
class FileInfo
{
    public $comment = '';
    public $csize = 0;
    public $gid = 0;
    public $group = '';
    public $isdir = false;
    public $mode = 0;
    public $mtime = 0;
    public $owner = '';
    public $path = '';
    public $size = 0;
    public $uid = 0;

    /**
     * initialize dynamic defaults
     *
     * @param string $path The path of the file, can also be set later through setPath()
     */
    public function __construct($path = '')
    {
        $this->setPath($path);
        if ($this->path && is_file($this->path)) {
            $this->mtime = filemtime($this->path);
        } else {
            $this->mtime = time();
        }
    }

    /**
     * Factory to build FileInfo from existing file or directory
     *
     * @param string $path path to a file on the local file system
     * @param string $as   optional path to use inside the archive
     * @throws RuntimeException
     * @return FileInfo
     */
    public static function fromPath($path, $as = '')
    {
        $path = $this->cleanPath($path);
        if (!is_file($path)) {
            throw new RuntimeException("$path does not exist");
        }

        $file = new FileInfo();

        $file->setIsdir(is_dir($path));
        $file->setMode(fileperms($path));
        $file->setSize(filesize($path));
        $file->setMtime(filemtime($path));
        $o = fileowner($path);
        $file->setUid($o);
        $g = filegroup($path);
        $file->setGid($g);
        if (function_exists('posix_getpwuid')) {
            $info = posix_getpwuid($o);
            $file->setOwner($info['name']);
            $info = posix_getgrgid($g);
            $file->setGroup($info['name']);
        }

        if ($as) {
            $file->setPath($as); //no cleaning
        } else {
            $file->setPath($path);
        }

        return $file;
    }

    /**
     * @return int the filesize. always 0 for directories
     */
    public function getSize()
    {
        if($this->isdir) return 0;
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return int
     */
    public function getCompressedSize()
    {
        return $this->csize;
    }

    /**
     * @param int $csize
     */
    public function setCompressedSize($csize)
    {
        $this->csize = $csize;
    }

    /**
     * @return int
     */
    public function getMtime()
    {
        return $this->mtime;
    }

    /**
     * @param int $mtime
     */
    public function setMtime($mtime)
    {
        $this->mtime = $mtime;
    }

    /**
     * @return int
     */
    public function getGid()
    {
        return $this->gid;
    }

    /**
     * @param int $gid
     */
    public function setGid($gid)
    {
        $this->gid = $gid;
    }

    /**
     * @return int
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param string $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * @return boolean
     */
    public function getIsdir()
    {
        return $this->isdir;
    }

    /**
     * @param boolean $isdir
     */
    public function setIsdir($isdir)
    {
        // default mode for directories
        if ($isdir && $this->mode === 0664) {
            $this->mode = 0775;
        }
        $this->isdir = $isdir;
    }

    /**
     * @return int
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param string $owner
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $this->cleanPath($path);
    }

    /**
     * Cleans up a path and removes relative parts, also strips leading slashes
     *
     * @param string $path
     * @return string
     */
    protected function cleanPath($path)
    {
        if ($path === '') return '';
        $path = str_replace('\\', '/', $path);
        $path = explode('/', $path);
        $newpath = array();
        foreach ($path as $p) {
            if ($p === '' || $p === '.') {
                continue;
            } elseif ($p === '..') {
                array_pop($newpath);
                continue;
            }
            $newpath[] = $p;
        }
        return trim(implode(DIRECTORY_SEPARATOR, $newpath), ' /\\');
    }

    /**
     * Strip given prefix or number of path segments from the filename
     *
     * The $strip parameter allows you to strip a certain number of path components from the filenames
     * found in the tar file, similar to the --strip-components feature of GNU tar. This is triggered when
     * an integer is passed as $strip.
     * Alternatively a fixed string prefix may be passed in $strip. If the filename matches this prefix,
     * the prefix will be stripped. It is recommended to give prefixes with a trailing slash.
     *
     * @param  int|string $strip
     */
    public function strip($strip)
    {
        $filename = $this->getPath();
        if (is_int($strip)) {
            // if $strip is an integer we strip this many path components
            $filename = str_replace('\\', '/', $filename);
            $parts = explode('/', $filename);
            if (!$this->getIsdir()) {
                $base = array_pop($parts); // keep filename itself
            } else {
                $base = '';
            }
            $filename = implode(DIRECTORY_SEPARATOR, array_slice($parts, $strip));
            if ($base) {
                $filename .= DIRECTORY_SEPARATOR.$base;
            }
        } else {
            // if strip is a string, we strip a prefix here
            $striplen = strlen($strip);
            if (substr($filename, 0, $striplen) == $strip) {
                $filename = substr($filename, $striplen);
            }
        }

        $this->setPath($filename);
    }

    /**
     * Does this file's path match the given include or exclude expressions?
     *
     * Exclude rules take precedence over include rules
     *
     * @param string $include Regular expression of files to include
     * @param string $exclude Regular expression of files to exclude
     * @return bool
     */
    public function match($include = '', $exclude = '')
    {
        if ($include && !preg_match($include, $this->getPath())) {
            return false;
        }
        if ($exclude && preg_match($exclude, $this->getPath())) {
            return false;
        }

        return true;
    }
}
