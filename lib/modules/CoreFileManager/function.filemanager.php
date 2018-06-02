<?php
/**
 * Derived from H3K | Tiny File Manager https://github.com/prasathmani/tinyfilemanager
 * CCP Programmers http://fb.com/ccpprogrammers
 * Licence GPL3
 */

/**
 * Recursive function called by fm_file_tree() to list directories
 * @param string $directory of this dir
 * @param string $current 'current' path
 * @param int  $depth 0-based recursion depth
 */
function _fm_dir_tree($directory, $current, $depth)
{
    global $FM_ROOT_PATH, $FM_EXCLUDE_FOLDERS, $FM_FOLDER_URL, $FM_FOLDER_TITLE;

    if (!is_readable($directory)) {
        return '';
    }

    $len = strlen($FM_ROOT_PATH) + 1; //skip to relative-path
    $tree_content = '';
    // Get and sort directories
    $file = glob($directory. DIRECTORY_SEPARATOR . '*', GLOB_NOSORT|GLOB_NOESCAPE|GLOB_ONLYDIR);
    if ($file) {
        natcasesort($file);
        $dirs = [];
        foreach ($file as $this_file) {
            $name = basename($this_file);
            if (!($name == '.' || $name == '..' || in_array($this_file, $FM_EXCLUDE_FOLDERS))) {
                $dirs[$name] = $this_file;
            }
        }

        $tree_content = '<ul';
        if ($depth == 0) {
            $tree_content .= ' id="fm-tree"';
        }
        $tree_content .= '>';
        foreach ($dirs as $name => $path) {
            // $data includes " chars  
            $data = json_encode(['name'=>$name,'open'=>0,'opendown'=>0], JSON_NUMERIC_CHECK); //TODO name suited to incremental comparison during searches
            $relpath = substr($path, $len);
            $tree_content .= '<li class="fm-directory tree-closed" data-node=\''.$data.'\'><a href="'.$FM_FOLDER_URL.rawurlencode($relpath).'"';
            if ($FM_FOLDER_TITLE) {
                $tree_content .= ' title="'.$FM_FOLDER_TITLE.'"';
            }
            $tree_content .= '>' . htmlspecialchars($name) . '</a>';
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            $tree_content .= _fm_dir_tree($path, $current, $depth+1) . '</li>';
        }
        $tree_content .= '</ul>';
    }
    return $tree_content;
}

/**
  * Scan directory and populate elements to display folders treeview
  * @param string $directory
  */
function fm_dir_tree($directory, $current = '')
{
    // Remove trailing separator
    if (substr($directory, -1) == DIRECTORY_SEPARATOR) {
        $directory = substr($directory, 0, strlen($directory) - 1);
    }
    if ($current && substr($current, -1) == DIRECTORY_SEPARATOR) {
        $current = substr($current, 0, strlen($current) - 1);
    }
    return _fm_dir_tree($directory, $current, 0);
}

/**
 * Delete  file or folder (recursively)
 * @param string $path
 * @return bool
 */

function fm_rdelete($path)
{
    if (is_link($path)) {
        return unlink($path);
    } elseif (is_dir($path)) {
        $objects = scandir($path);
        $ok = true;
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (!fm_rdelete($path . DIRECTORY_SEPARATOR . $file)) {
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
 * @param int $filemode
 * @param int $dirmode
 * @return bool
 * @todo Will use in mass chmod
 */
function fm_rchmod($path, $filemode, $dirmode)
{
    if (is_dir($path)) {
        if (!chmod($path, $dirmode)) {
            return false;
        }
        $objects = scandir($path);
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (!fm_rchmod($path . DIRECTORY_SEPARATOR . $file, $filemode, $dirmode)) {
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
 * @return bool|null
 */
function fm_rename($old, $new, $force = true)
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
function fm_rcopy($path, $dest, $upd = true, $force = true)
{
    if (is_dir($path)) {
        if (!fm_mkdir($dest, $force)) {
            return false;
        }
        $objects = scandir($path);
        $ok = true;
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (!fm_rcopy($path . DIRECTORY_SEPARATOR . $file, $dest . DIRECTORY_SEPARATOR . $file)) {
                        $ok = false;
                    }
                }
            }
        }
        return $ok;
    } elseif (is_file($path)) {
        return fm_copy($path, $dest, $upd);
    }
    return false;
}

/**
 * Safely create folder
 * @param string $dir
 * @param bool $force
 * @return bool
 */
function fm_mkdir($dir, $force)
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
function fm_copy($f1, $f2, $upd)
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
function fm_clean_path($path)
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
 * @return bool|string
 */
function fm_get_parent_path($path)
{
    $path = fm_clean_path($path);
    if ($path != '') {
        return dirname($path);
    }
    return false;
}

/**
 * Get real path
 * @param string $path
 * @return string|false
 */
function fm_real_path($path)
{
    return stream_resolve_include_path($path);
}

/**
 * Encode html entities
 * @param string $text
 * @return string
 */
function fm_enc($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if string is in UTF-8
 * @param string $string
 * @return int
 */
function fm_is_utf8($string)
{
    return preg_match('//u', $string);
}

/**
 * Convert file name to UTF-8 in Windows
 * @param string $filename
 * @return string
 */
function fm_convert_win($filename)
{
    global $FM_IS_WIN, $FM_ICONV_INPUT_ENC;
    if ($FM_IS_WIN && function_exists('iconv')) {
        $filename = iconv($FM_ICONV_INPUT_ENC, 'UTF-8//IGNORE', $filename);
    }
    return $filename;
}

/**
 * Get nicely-formatted permissions
 * @param int $mode
 * @param bool $isdir
 * @return string
 */
function fm_get_fileperms($mode, $isdir = false)
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
function fm_get_filesize($size)
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
function fm_get_mime_type($path)
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
function fm_get_file_icon_class($path)
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
function fm_get_arch_picker($mod)
{
    global $FM_IS_WIN;

    $types = fm_get_arch_types(true);
    $keeps = ($FM_IS_WIN) ? ['zip'] : ['gz','bz2','xz','zip'];
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
function fm_get_arch_types($best = false)
{
    global $FM_IS_WIN;

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
        $uses = ($FM_IS_WIN) ? ['zip','rar','7z'] : ['xz','bz2','gz','zip'];
        foreach ($uses as $t) {
            if (isset($types[$t])) {
                $types[$t]['use'] = 1;
                break;
            }
        }
    }
    return $types;
}
