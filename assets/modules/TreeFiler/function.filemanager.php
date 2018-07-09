<?php
/**
 * Derived in part from H3K | Tiny File Manager https://github.com/prasathmani/tinyfilemanager
 * CCP Programmers http://fb.com/ccpprogrammers
 * Licence GPL3
 */

/**
 * Recursive function called by cfm_dir_tree() to accumulate directories
 * @param string $path path of this directory
 * @param string $current 'current' path TODO use this
 * @param int  $depth 0-based recursion depth
 * @return string
 */
function _cfm_dir_tree(string $path, string $current, int $depth) : string
{
    global $CFM_ROOTPATH, $CFM_EXCLUDE_FOLDERS, $CFM_FOLDER_URL, $CFM_FOLDER_TITLE;

    if (!is_readable($path)) {
        return '';
    }

    $tree_content = '';
    // Get directories
    $alldirs = glob($path.DIRECTORY_SEPARATOR.'*', GLOB_NOSORT|GLOB_NOESCAPE|GLOB_ONLYDIR);
    if ($alldirs) {
        $p1 = DIRECTORY_SEPARATOR.'.';
        $p2 = DIRECTORY_SEPARATOR.'..';
        foreach ($alldirs as &$onedir) {
            if (endswith($onedir,$p1) || endswith($onedir,$p2)) {
                unset($onedir);
            } elseif (in_array($onedir, $CFM_EXCLUDE_FOLDERS)) {
                unset($onedir);
            }
        }
        unset($onedir);
    }

    if ($alldirs) {
        natcasesort($alldirs); //TODO mb_ sorting

        $len = strlen($CFM_ROOTPATH) + 1; //to skip to relative-path
        $tree_content = '<ul';
        if ($depth == 0) {
            $tree_content .= ' id="cfm-tree"';
        }
        $tree_content .= '>';
        foreach ($alldirs as $onedir) {
            $name = basename($onedir);
            // $data includes " chars
            $relpath = substr($onedir, $len);
            $tree_content .= '<li><a href="'.$CFM_FOLDER_URL.rawurlencode($relpath).'"';
            if ($CFM_FOLDER_TITLE) {
                $tree_content .= ' title="'.$CFM_FOLDER_TITLE.'"';
            }
            $tree_content .= '>' . htmlspecialchars($name) . '</a>';
            $tree_content .= _cfm_dir_tree($onedir, $current, $depth+1) . '</li>';
        }
        $tree_content .= '</ul>';
    }
    return $tree_content;
}

/**
  * Scan directory $path and populate ul,li elements to represent a folders-treeview
  * @param string $path
  * @param string $current Optional 'current'-directory path
  * @return string
  */
function cfm_dir_tree(string $path, string $current = '') : string
{
    // Remove trailing separator(s)
    if (endswith($path, DIRECTORY_SEPARATOR)) {
        $path = substr($path, 0, -1);
    }
    if ($current && endswith($current, DIRECTORY_SEPARATOR)) {
        $current = substr($current, 0, -1);
    }
    return _cfm_dir_tree($path, $current, 0);
}

/**
 * Delete  file or folder (recursively)
 * @param string $path
 * @return bool
 */
function cfm_rdelete(string $path) : bool
{
    if (is_link($path)) {
        return unlink($path);
    } elseif (is_dir($path)) {
        $objects = scandir($path);
        $ok = true;
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (!cfm_rdelete($path . DIRECTORY_SEPARATOR . $file)) {
                        $ok = false;
                    }
                }
            }
        }
        return ($ok) ? rmdir($path) : false;
    } elseif (is_file($path)) {
        return unlink($path);
    }
    return false;
}

/**
 * Recursive chmod
 * @param string $path
 * @param int $filemode mode flags to set for files
 * @param int $dirmode mode flags to set for folders
 * @return bool
 */
function cfm_rchmod(string $path, int $filemode, int $dirmode) : bool
{
    if (is_dir($path)) {
        if (!chmod($path, $dirmode)) {
            return false;
        }
        $objects = scandir($path);
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (!cfm_rchmod($path . DIRECTORY_SEPARATOR . $file, $filemode, $dirmode)) {
                        return false;
                    }
                }
            }
        }
        return true;
    } elseif (is_link($path)) {
        return true;
    } elseif (is_file($path)) {
        return chmod($path, $filemode);
    }
    return false;
}

/**
 * Safely rename
 * @param string $old
 * @param string $new
 * @param bool $force whether to overwrite existing item with new name
 * @return mixed bool|null
 */
function cfm_rename(string $old, string $new, bool $force = true)
{
    return (file_exists($old) && ($force || !file_exists($new))) ? rename($old, $new) : null;
}

/**
 * Copy file or folder (recursively).
 * @param string $path
 * @param string $dest
 * @param bool $upd Update files
 * @param bool $force Create folder with same names instead file
 * @return bool
 */
function cfm_rcopy(string $path, string $dest, bool $upd = true, bool $force = true) : bool
{
    if (is_dir($path)) {
        if (!cfm_mkdir($dest, $force)) {
            return false;
        }
        $objects = scandir($path);
        $ok = true;
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (!cfm_rcopy($path . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file)) {
                        $ok = false;
                    }
                }
            }
        }
        return $ok;
    } elseif (is_file($path)) {
        return cfm_copy($path, $dest, $upd);
    }
    return false;
}

/**
 * Safely create folder
 * @param string $dir
 * @param bool $force
 * @return bool
 */
function cfm_mkdir(string $dir, bool $force) : bool
{
    if (file_exists($dir)) {
        if (is_dir($dir)) {
            return $dir;
        } elseif (!$force) {
            return false;
        }
        unlink($dir);
    }
    return mkdir($dir, 0771, true);
}

/**
 * Safely copy file
 * @param string $f1
 * @param string $f2
 * @param bool $upd
 * @return bool
 */
function cfm_copy(string $f1, string $f2, bool $upd) : bool
{
    $time1 = filemtime($f1);
    if (file_exists($f2)) {
        $time2 = filemtime($f2);
        if ($time2 >= $time1 && $upd) {
            return false;
        }
    }
    $ok = copy($f1, $f2);
    if ($ok) {
        @chmod($f2, octdec('644'));
    }
    return $ok;
}

/**
 * Clean path
 * @param string $path
 * @return string
 */
function cfm_clean_path(string $path) : string
{
    $path = trim($path);
    $path = trim($path, '\\/');
    $path = str_replace(['../', '..\\'], ['', ''], $path);
    if ($path !== '..') {
        return str_replace(['\\', '/'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $path);
    }
    return '';
}

/**
 * Get parent path
 * @param string $path
 * @return mixed string|false
 */
function cfm_get_parent_path(string $path)
{
    $path = cfm_clean_path($path);
    if ($path != '') {
        return dirname($path);
    }
    return false;
}

/**
 * Get real path
 * @param string $path
 * @return mixed string|false
 */
function cfm_real_path(string $path)
{
    return stream_resolve_include_path($path);
}

/**
 * Encode html entities
 * @param string $text
 * @return string
 */
function cfm_enc(string $text) : string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if string is in UTF-8
 * @param string $string
 * @return int, effectively a bool
 */
function cfm_is_utf8(string $string) : int
{
    return preg_match('//u', $string);
}

/**
 * Convert file name to UTF-8 in Windows
 * @param string $filename
 * @return string
 */
function cfm_convert_win(string $filename) : string
{
    global $CFM_IS_WIN, $CFM_ICONV_INPUT_ENC;
    if ($CFM_IS_WIN && function_exists('iconv')) {
        $filename = iconv($CFM_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $filename);
    }
    return $filename;
}

/**
 * Get nicely-formatted permissions
 * @param int $mode
 * @param bool $isdir
 * @return string
 */
function cfm_get_fileperms(int $mode, bool $isdir = false) : string
{
    global $pr, $pw, $px, $pxf;

    $perms = [];
    if ($mode & 0x0100) $perms[] = $pr;
    if ($mode & 0x0080) $perms[] = $pw;
    if ($mode & 0x0040) $perms[] = ($isdir) ? $pxf : $px; //ignore static flag
    return implode('+',$perms);
}

/**
 * Get nicely-formatted filesize
 * @param int $size
 * @return string
 */
function cfm_get_filesize(int $size) : string
{
    global $bytename, $kbname, $mbname, $gbname; //$tbname

    if ($size < 1000) {
        return ($size > 0) ? sprintf('%s %s', $size, $bytename) : '0';
    } elseif (($size / 1024) < 1000) {
        return sprintf('%s %s', round(($size / 1024), 2), $kbname);
    } elseif (($size / 1024 / 1024) < 1000) {
        return sprintf('%s %s', round(($size / 1024 / 1024), 2), $mbname);
    } elseif (($size / 1024 / 1024 / 1024) < 1000) {
        return sprintf('%s %s', round(($size / 1024 / 1024 / 1024), 2), $gbname);
    } else {
        return sprintf('%s TiB', round(($size / 1024 / 1024 / 1024 / 1024), 2));
    }
}

/**
 * Get mime type
 * @param string $path
 * @return mixed|string
 */
function cfm_get_mime_type(string $path)
{
    global $helper;
    if ($helper == null) {
        global $config;
        $helper = new \CMSMS\FileTypeHelper($config);
    }
    return $helper->get_mime_type($path);
}

/**
 * Get CSS classname for file
 * @param string $path
 * @return string
 */
function cfm_get_file_icon_class(string $path) : string
{
    global $helper;
    if ($helper == null) {
        global $config;
        $helper = new \CMSMS\FileTypeHelper($config);
    }

    if ($helper->is_image($path)) {
        return 'if-file-image';
    }
    if ($helper->is_archive($path)) {
        return 'if-file-archive';
    }
    if ($helper->is_audio($path)) {
        return 'if-file-audio';
    }
    if ($helper->is_video($path)) {
        return 'if-file-video';
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'passwd': case 'ftpquota': case 'sql': case 'js': case 'json':
        case 'config': case 'twig': case 'tpl':
        case 'c': case 'cpp': case 'cs': case 'py': case 'map': case 'lock': case 'dtd':
        case 'php': case 'php4': case 'php5': case 'phps': case 'phtml':
            return 'if-file-code';
        case 'txt': case 'ini': case 'conf': case 'log': case 'htaccess': case 'md': case 'gitignore':
            return 'if-doc-text';
        case 'css': case 'less': case 'sass': case 'scss':
            return 'if-css3';
        case 'htm': case 'html': case 'shtml': case 'xhtml':
            return 'if-html5';
        case 'xml': case 'xsl':
            return 'if-doc-text';
        case 'pdf':
            return 'if-file-pdf';
        case 'm3u': case 'm3u8': case 'pls': case 'cue':
            return 'if-headphones';
        case 'eml': case 'msg':
            return 'if-chat';
        case 'xls': case 'xlsx':
            return 'if-file-excel';
        case 'csv':
            return 'if-doc-text';
        case 'bak':
            return 'if-history';
        case 'doc': case 'docx':
            return 'if-file-word';
        case 'ppt': case 'pptx':
            return 'if-file-powerpoint';
        case 'ttf': case 'ttc': case 'otf': case 'woff': case 'woff2': case 'eot': case 'fon':
            return 'if-font';
        case 'exe': case 'msi': case 'so': case 'dll':
            return 'if-cog';
        case 'bat': case 'sh':
            return 'if-terminal';
        default:
            return 'if-doc';
    }
}

/**
 * Get data for archive-type selection UI
 * @param module-object $mod
 * @return array
 */
function cfm_get_arch_picker(\CoreFileManager $mod) : array
{
    global $CFM_IS_WIN;

    $types = cfm_get_arch_types(true);
    $keeps = ($CFM_IS_WIN) ? ['zip'] : ['gz','bz2','xz','zip'];
    foreach ($types as $t => $one) {
        if (in_array($t, $keeps)) {
            $types[$t]['label'] = $mod->Lang('arch_'.$t);
        } else {
            unset($types[$t]);
        }
    }
    return $types;
}

/**
 * Get the archive-types supported by UnifiedArchive class (represented as file-extensions)
 * @param bool $best Optional flag whether to flag (if possible) the type
 *  which is preferred for compression
 * @return array keys are (lowercase) file extensions (maybe 'compound' like 'tar.ext')
 */
function cfm_get_arch_types(bool $best = false) : array
{
    global $CFM_IS_WIN;

    $types = [];

    if (class_exists('ZipArchive')) $types['zip'] = [];
    $tar = class_exists('PharData') || class_exists('Archive_Tar');
    if (function_exists('gzopen')) {
        $types['gz'] = [];
        if ($tar) {
            $types['tar.gz'] = [];
            $types['tgz'] = [];
        }
    }
    if (function_exists('bzopen')) {
        $types['bz2'] = [];
        if ($tar) {
            $types['tar.bz2'] = [];
            $types['tbz2'] = [];
        }
	}
    if (function_exists('xzopen')) {
        $types['xz'] = [];
        $types['lzma'] = [];
        if ($tar) {
            $types['txz'] = [];
            $types['tar.xz'] = [];
            $types['tar.lzma'] = [];
        }
    }
    if ($tar) {
        $types['tar'] = [];
    }
    if (extension_loaded('Rar')) $types['rar'] = [];
    if (class_exists('\Archive7z\Archive7z')) $types['7z'] = [];
    if (class_exists('\CISOFile')) $types['iso'] = [];
    if (class_exists('\CabArchive')) $types['cab'] = [];

    if ($best) {
        $uses = ($CFM_IS_WIN) ? ['zip','rar','7z'] : ['xz','bz2','gz','zip'];
        foreach ($uses as $t) {
            if (isset($types[$t])) {
                $types[$t]['use'] = 1;
                break;
            }
        }
    }
    return $types;
}

/**
 * Get the archive-type for a multi-item archive
 * @param string $ext archive type/extension
 * @return string
 */
function cfm_tarify(string $ext) : string
{
    if (in_array($ext,['gz','bz2','xz','lzma'])) {
        return 'tar.'.$ext;
    }
    return $ext;
}

