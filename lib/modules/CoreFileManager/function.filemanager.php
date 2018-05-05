<?php
/**
 * Derived from H3K | Tiny File Manager https://github.com/prasathmani/tinyfilemanager
 * CCP Programmers http://fb.com/ccpprogrammers
 * Licence GPL3
 */

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
 * @return bool|null
 */
function fm_rename($old, $new)
{
    return (!file_exists($new) && file_exists($old)) ? rename($old, $new) : null;
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
    return mkdir($dir, 0777, true);
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
        touch($f2, $time1);
    }
    return $ok;
}

/**
 * Get mime type
 * @param string $file_path
 * @return mixed|string
 */
function fm_get_mime_type($file_path)
{
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        return $mime;
    } elseif (function_exists('mime_content_type')) {
        return mime_content_type($file_path);
    } elseif (!stristr(ini_get('disable_functions'), 'shell_exec')) {
        $file = escapeshellarg($file_path);
        $mime = shell_exec('file -bi ' . $file);
        return $mime;
    } else {
        return '--';
    }
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
    $path = str_replace(array('../', '..\\'), array('', ''), $path);
    if ($path == '..') {
        $path = '';
    }
    return str_replace(array('\\', '/'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $path);
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
 * Get nice filesize
 * @param int $size
 * @return string
 */
function fm_get_filesize($size)
{
    global $bytename, $kbname, $mbname, $gbname; //$tbname

    if ($size < 1000) {
        return sprintf('%s %s', $size, $bytename);
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
 * Get info about zip archive
 * @param string $path
 * @return array|bool
 */
function fm_get_zif_info($path)
{
    if (function_exists('zip_open')) {
        $arch = zip_open($path);
        if ($arch) {
            $filenames = array();
            while ($zip_entry = zip_read($arch)) {
                $zip_name = zip_entry_name($zip_entry);
                $zip_folder = substr($zip_name, -1) == DIRECTORY_SEPARATOR;
                $filenames[] = array(
                    'name' => $zip_name,
                    'filesize' => zip_entry_filesize($zip_entry),
                    'compressed_size' => zip_entry_compressedsize($zip_entry),
                    'folder' => $zip_folder
                    //'compression_method' => zip_entry_compressionmethod($zip_entry),
                );
            }
            zip_close($arch);
            return $filenames;
        }
    }
    return false;
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
* Recursive function called by fm_file_tree() to list directories
* @param string $directory
* @param string $current
* @param boolean $first_call
*/
function fm_file_tree_dir($directory, $current, $first_call = true)
{
    global $FM_ROOT_PATH, $FM_EXCLUDE_FOLDERS, $FM_FOLDER_URL, $FM_FOLDER_TITLE;

    if( !is_readable($directory) ) {
        return "";
    }

    $len = strlen($FM_ROOT_PATH) + 1; //skip to relative-path
    $php_file_tree = "";
    // Get and sort directories
    $file = glob ($directory. DIRECTORY_SEPARATOR . "*", GLOB_NOSORT|GLOB_NOESCAPE|GLOB_ONLYDIR);
    if( $file ) {
        natcasesort($file);
        $dirs = array();
        foreach($file as $this_file) {
            $name = basename($this_file);
            if( !($name == '.' || $name == '..' || in_array($this_file, $FM_EXCLUDE_FOLDERS)) ) {
                $dirs[$name] = $this_file;
            }
        }

        $php_file_tree = "<ul";
        if( $first_call ) { $php_file_tree .= " id=\"fm-tree\""; $first_call = false; }
        $php_file_tree .= ">";
        foreach( $dirs as $name => $path ) {
            $relpath = substr($path, $len);
            $php_file_tree .= "<li class=\"fm-directory\"><a href=\"".$FM_FOLDER_URL.rawurlencode($relpath)."\"";
            if ($FM_FOLDER_TITLE) {
                $php_file_tree .= " title=\"".$FM_FOLDER_TITLE."\"";
            }
            $php_file_tree .= ">" . htmlspecialchars($name) . "</a>";
            $path = $directory . DIRECTORY_SEPARATOR . $name;
            $php_file_tree .= fm_file_tree_dir($path, $current, false) . "</li>";
        }
        $php_file_tree .= "</ul>";
    }
    return $php_file_tree;
}

/**
 * Scan directory and render tree view
 * @param string $directory
 */
function fm_dir_tree($directory, $current = '')
{
    // Remove trailing separator
    if( substr($directory, -1) == DIRECTORY_SEPARATOR ) $directory = substr($directory, 0, strlen($directory) - 1);
    if( $current && substr($current, -1) == DIRECTORY_SEPARATOR ) $current = substr($current, 0, strlen($current) - 1);
    return fm_file_tree_dir($directory, $current);
}

/**
 * Save message in session
 * @param string $msg
 * @param string $status
 */
/*function fm_set_msg($msg, $status = 'ok')
{
    $_SESSION['message'] = $msg;
    $_SESSION['status'] = $status;
}
*/
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
 * Get CSS classname for file
 * @param string $path
 * @return string
 */
function fm_get_file_icon_class($path)
{
    // get extension
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    if (in_array($ext, fm_get_image_exts())) return 'if-file-image';
    if (in_array($ext, fm_get_archive_exts())) return 'if-file-archive';
    if (in_array($ext, fm_get_audio_exts())) return 'if-file-audio';
    if (in_array($ext, fm_get_video_exts())) return 'if-file-video';

    switch ($ext) {
        case 'passwd': case 'ftpquota': case 'sql': case 'js': case 'json':
        case 'config': case 'twig': case 'tpl':
        case 'c': case 'cpp': case 'cs': case 'py': case 'map': case 'lock': case 'dtd':
        case 'php': case 'php4': case 'php5': case 'phps': case 'phtml':
            $img = 'if-file-code';
            break;
        case 'txt': case 'ini': case 'conf': case 'log': case 'htaccess': case 'md': case 'gitignore':
            $img = 'if-doc-text';
            break;
        case 'css': case 'less': case 'sass': case 'scss':
            $img = 'if-css3';
            break;
        case 'htm': case 'html': case 'shtml': case 'xhtml':
            $img = 'if-html5';
            break;
        case 'xml': case 'xsl':
            $img = 'if-doc-text';
            break;
        case 'pdf':
            $img = 'if-file-pdf';
            break;
        case 'm3u': case 'm3u8': case 'pls': case 'cue':
            $img = 'if-headphones';
            break;
        case 'eml': case 'msg':
            $img = 'if-chat-empty';
            break;
        case 'xls': case 'xlsx':
            $img = 'if-file-excel';
            break;
        case 'csv':
            $img = 'if-doc-text';
            break;
        case 'bak':
            $img = 'if-history';
            break;
        case 'doc': case 'docx':
            $img = 'if-file-word';
            break;
        case 'ppt': case 'pptx':
            $img = 'if-file-powerpoint';
            break;
        case 'ttf': case 'ttc': case 'otf': case 'woff': case 'woff2': case 'eot': case 'fon':
            $img = 'if-font';
            break;
        case 'exe': case 'msi': case 'so': case 'dll':
            $img = 'if-cog';
            break;
        case 'bat': case 'sh':
            $img = 'if-terminal';
            break;
        default:
            $img = 'if-doc';
    }

    return $img;
}

/**
 * Get image files extensions
 * @return array
 */
function fm_get_image_exts()
{
    return [
        'ai',
        'bmp',
        'eps',
        'fla',
        'gif',
        'ico',
        'jp2',
        'jpc',
        'jpeg',
        'jpg',
        'jpx',
        'png',
        'psd',
        'psd',
        'svg',
        'swf',
        'tif',
        'tiff',
        'wbmp',
        'webp',
        'xbm',
    ];
}

/**
 * Get video files extensions
 * @return array
 */
function fm_get_video_exts()
{
    return [
        '3gp',
        'asf',
        'avi',
        'f4v',
        'flv',
        'm4v',
        'mkv',
        'mov',
        'mp4',
        'mpeg',
        'mpg',
        'ogm',
        'ogv',
        'rm',
        'swf',
        'webm',
        'wmv',
    ];
}

/**
 * Get audio files extensions
 * @return array
 */
function fm_get_audio_exts()
{
    return [
        'aac',
        'ac3',
        'flac',
        'm4a',
        'mka',
        'mp2',
        'mp3',
        'oga',
        'ogg',
        'ra',
        'ram',
        'tds',
        'wav',
        'wm',
        'wma',
    ];
}

/**
 * Get text file extensions
 * @return array
 */
function fm_get_text_exts()
{
    return [
        'txt', 'css', 'ini', 'conf', 'log', 'htaccess', 'passwd', 'ftpquota', 'sql', 'js', 'json', 'sh', 'config',
        'php', 'php4', 'php5', 'phps', 'phtml', 'htm', 'html', 'shtml', 'xhtml', 'xml', 'xsl', 'm3u', 'm3u8', 'pls', 'cue',
        'eml', 'msg', 'csv', 'bat', 'twig', 'tpl', 'md', 'gitignore', 'less', 'sass', 'scss', 'c', 'cpp', 'cs', 'py',
        'map', 'lock', 'dtd',
    ];
}

/**
 * Get mime types of text files
 * @return array
 */
function fm_get_text_mimes()
{
    return array(
        'application/xml',
        'application/javascript',
        'application/x-javascript',
        'image/svg+xml',
        'message/rfc822',
    );
}

/**
 * Get file names of text files w/o extensions
 * @return array
 */
function fm_get_text_names()
{
    return array(
        'license',
        'readme',
        'authors',
        'contributors',
        'changelog',
    );
}

function fm_get_archive_exts()
{
    return array(
        '7z',
        'gz',
        'rar',
        's7z',
        'tar',
        'xz',
        'z',
        'zip',
    );
}

/**
 * Class to work with zip files (using ZipArchive)
 */
class FM_Zipper
{
    private $zip;

    public function __construct()
    {
        $this->zip = new ZipArchive();
    }

    /**
     * Create archive with name $filename and files $files (RELATIVE PATHS!)
     * @param string $filename
     * @param array|string $files
     * @return bool
     */
    public function create($filename, $files)
    {
        $res = $this->zip->open($filename, ZipArchive::CREATE);
        if ($res !== true) {
            return false;
        }
        if (is_array($files)) {
            foreach ($files as $f) {
                if (!$this->addFileOrDir($f)) {
                    $this->zip->close();
                    return false;
                }
            }
            $this->zip->close();
            return true;
        } else {
            if ($this->addFileOrDir($files)) {
                $this->zip->close();
                return true;
            }
            return false;
        }
    }

    /**
     * Extract archive $filename to folder $path (RELATIVE OR ABSOLUTE PATHS)
     * @param string $filename
     * @param string $path
     * @return bool
     */
    public function unzip($filename, $path)
    {
        $res = $this->zip->open($filename);
        if ($res !== true) {
            return false;
        }
        if ($this->zip->extractTo($path)) {
            $this->zip->close();
            return true;
        }
        return false;
    }

    /**
     * Add file/folder to archive
     * @param string $filename
     * @return bool
     */
    private function addFileOrDir($filename)
    {
        if (is_file($filename)) {
            return $this->zip->addFile($filename);
        } elseif (is_dir($filename)) {
            return $this->addDir($filename);
        }
        return false;
    }

    /**
     * Add folder recursively
     * @param string $path
     * @return bool
     */
    private function addDir($path)
    {
        if (!$this->zip->addEmptyDir($path)) {
            return false;
        }
        $objects = scandir($path);
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                        if (!$this->addDir($path . DIRECTORY_SEPARATOR . $file)) {
                            return false;
                        }
                    } elseif (is_file($path . DIRECTORY_SEPARATOR . $file)) {
                        if (!$this->zip->addFile($path . DIRECTORY_SEPARATOR . $file)) {
                            return false;
                        }
                    }
                }
            }
            return true;
        }
        return false;
    }
}
