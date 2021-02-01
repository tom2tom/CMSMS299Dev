<?php
/*
Class: theme utility methods
Copyright (C) 2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace ThemeManager;

//use CMSMS\FilePickerProfile;
use CMSMS\Utils as AppUtils;
use ThemeManager;
use Exception;
use const CMS_DB_PREFIX;
use function cmsms;
use function sanitizeVal;
use function get_recursive_file_list;

final class Utils
{
    /**
     *
     * @param string $content
     * @return array
     */
    public function _extractTemplateFiles($content)
    {
        $result = [];
        $urls = $this->get_urls($content);
        // we're only concerned about the src urls
        foreach ($urls['src'] as $src) {
            // if it's an external link we ignore it
            // if it's an internal link, we add it
            if (!preg_match('/^https?\:/', $src)) {
                $result[] = $src;
            }
        }
        foreach ($urls['href'] as $href) {
            // if it's an external link we ignore it
            // if it's an internal link, we add it
            if (!preg_match('/^https?\:/', $href)) {
                $result[] = $href;
            }
        }

        return $result;
    }

    /**
     *
     * @param string $stylesheet
     * @return array
     */
    public function _extractStylesheetImages($stylesheet)
    {
        $regex = '/url\s*\(\"*(.*)\"*\)/i';
        preg_match_all($regex, $stylesheet->value, $matches);
        // $matches[1] is all immportant
        // now strip out any that have ^http in them
        $result = [];
        foreach ($matches[1] as $match) {
            if (!preg_match('/^https?\:/', $match)) {
                $result[] = str_replace('[[root_url]]/', '', $match);
            }
        }

        return $result;
    }

    /**
     *
     * @param string $dir
     * @param string $path UNUSED
     * @return array
     */
    public function list_dir($dir, $path = '')
    {
        $filelist = [];
        if (($dh = opendir($dir))) {
            $gCms = cmsms();
            $config = $gCms->GetConfig();
            $listDir = [];
            $filemanager = AppUtils::get_module('FileManager');
            while (($sub = readdir($dh)) !== false) {
                if ($sub != '.' && $sub != '..' && $sub != 'Thumb.db') {
                    $fp = $dir . DIRECTORY_SEPARATOR . $sub;
                    if (is_file($fp)) {
                        $filesize = filesize($fp);
                        $filesize = $filemanager->FormatFileSize($filesize);
                        $filelist[] = str_replace($config['uploads_path'], '', $fp);
                        $sub = [$sub => ['filesize' => $filesize['size'] . ' ' . $filesize['unit'], 'path' => str_replace($config['uploads_path'], '', $fp)]];
                        $listDir[] = $sub;
                    } elseif (is_dir($fp)) {
                        $filelist[] = $this->list_dir($fp); //recurse
                        //$listDir[$sub] = $this->list_dir($fp); //directories
                    }
                }
            }
            closedir($dh);
        }
        return $filelist;
    }

    /**
     *
     * @param type $dir
     * @return string
     */
    public function ListFiles($dir)
    {
        return get_recursive_file_list($dir, [], -1, 'FILES');
    }

    /**
     *
     * @param type $file
     * @return array
     */
    public function xml_parse_into_assoc($file)
    {
        $data = implode('', file($file));
        $p = xml_parser_create();

        xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 1);

        xml_parse_into_struct($p, $data, $vals, $index);
        xml_parser_free($p);

        $levels = [null];

        foreach ($vals as $val) {
            if ($val['type'] == 'open' || $val['type'] == 'complete') {
                if (!array_key_exists($val['level'], $levels)) {
                    $levels[$val['level']] = [];
                }
            }

            $prevLevel = &$levels[$val['level'] - 1];
            $parent = $prevLevel[count($prevLevel) - 1];

            if ($val['type'] == 'open') {
                $val['children'] = [];
                $levels[$val['level']][] = $val;
                continue;
            } elseif ($val['type'] == 'complete' && isset($val['value'])) {
                $parent['children'][$val['tag']] = $val['value'];
            } elseif ($val['type'] == 'close') {
                $pop = array_pop($levels[$val['level']]);
                $tag = $pop['tag'];

                if ($parent) {
                    if (!array_key_exists($tag, $parent['children'])) {
                        $parent['children'][$tag] = $pop['children'];
                    } elseif (is_array($parent['children'][$tag])) {
                        if (!isset($parent['children'][$tag][0])) {
                            $oldSingle = $parent['children'][$tag];
                            $parent['children'][$tag] = null;
                            $parent['children'][$tag][] = $oldSingle;
                        }
                        $parent['children'][$tag][] = $pop['children'];
                    }
                } else {
                    return([$pop['tag'] => $pop['children']]);
                }
            }

            $prevLevel[count($prevLevel) - 1] = $parent;
        }
    }

    /**
     *
     * @param type $name
     * @return type
     */
    function getTemplateIdByName($name)
    {
        $db = cmsms()->GetDb();
        $query = 'SELECT template_id FROM ' . CMS_DB_PREFIX . 'templates WHERE template_type = ?'; //TODO support named/themed templates
        $result = $db->getOne($query, [$name]);
        return $result;
    }

    /**
     *
     * @param type $theme_id
     * @param type $type
     * @param type $name
     * @param type $system_id
     * @param type $module
     * @param type $location
     */
    public function insertImport($theme_id, $type, $name, $system_id = '', $module = '', $location = '')
    {
        $db = cmsms()->GetDb();
        $insert = false;
        switch ($type) {
            case 'template':
                $query = 'SELECT COUNT(1) FROM ' . CMS_DB_PREFIX . 'module_themes_comp WHERE theme_id = ? AND type =? AND name = ?';
                $found = $db->GetOne($query, [$theme_id, $type, $name]);
                if (!$found) {
                    $insert = true;
                } else {
                    $query = 'UPDATE ' . CMS_DB_PREFIX . 'module_themes_comp SET system_id = ? WHERE theme_id = ? AND type =? AND name = ?';
                    $db->Execute($query, [$system_id, $theme_id, $type, $name]);
                }
                break;
            case 'style':
                $query = 'SELECT COUNT(1) FROM ' . CMS_DB_PREFIX . 'module_themes_comp WHERE theme_id = ? AND type =? AND name = ?';
                $found = $db->GetOne($query, [$theme_id, $type, $name]);
                if (!$found) {
                    $insert = true;
                } else {
                    $query = 'UPDATE ' . CMS_DB_PREFIX . 'module_themes_comp SET system_id = ? WHERE theme_id = ? AND type =? AND name = ?';
                    $db->Execute($query, [$system_id, $theme_id, $type, $name]);
                }
                break;
            case 'page':
                $query = 'SELECT COUNT(1) FROM ' . CMS_DB_PREFIX . 'module_themes_comp WHERE theme_id = ? AND type =? AND name = ?';
                $found = $db->GetOne($query, [$theme_id, $type, $name]);
                if (!$found) {
                    $insert = true;
                }
                break;
            case 'module':
                $query = 'SELECT COUNT(1) FROM ' . CMS_DB_PREFIX . 'module_themes_comp WHERE theme_id = ? AND type =? AND name = ? and module = ?';
                $found = $db->GetOne($query, [$theme_id, $type, $name, $module]);
                if (!$found) {
                    $insert = true;
                }
                break;
            case 'udt':
                $query = 'SELECT COUNT(1) FROM ' . CMS_DB_PREFIX . 'module_themes_comp WHERE theme_id = ? AND type =? AND name = ?';
                $found = $db->GetOne($query, [$theme_id, $type, $name]);
                if (!$found) {
                    $insert = true;
                }
                break;
            case 'file':
                $query = 'SELECT COUNT(1) FROM ' . CMS_DB_PREFIX . 'module_themes_comp WHERE theme_id = ? AND type =? AND name = ? AND location = ?';
                $found = $db->GetOne($query, [$theme_id, $type, $name, $location]);
                if (!$found) {
                    $insert = true;
                }
                break;
        }
        if ($insert) {
            $query = 'INSERT INTO ' . CMS_DB_PREFIX . 'module_themes_comp (type, system_id, name, module, location) VALUES (?,?,?,?,?)';
            //TODO theme_id AUTO INC field
            $db->Execute($query, [$type, $system_id, $name, $module, $location]);
        }
    }

    /**
     *
     * @param ThemeManager $modinst
     * @param string $prefix string to be prepended to saved filepath
     * @param string $name UNUSED
     * @param string $location
     * @param bool $encoded whether $data is encoded
     * @param string $data
     * @return int 0 == fail, 1 == success
     */
    public function saveEncodedFile($modinst, $prefix, $name, $location, $encoded, $data)
    {
        // clean up the location
        $location = trim($location, '\\/ ');
        // translate separators if we have to
        $newloc = strtr($location, '\\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);

        $fn = ($prefix) ? $prefix . DIRECTORY_SEPARATOR . $newloc :  DIRECTORY_SEPARATOR . $newloc; //TODO e.g. WINOS path
        $dir = dirname($fn);
        if (!file_exists($dir)) {
            @mkdir($dir, 0771, true);
            if (!file_exists($dir)) {
                return 0;
            }
        }

        // and put it out there
        $fp = fopen($fn, 'w');
        if (!$fp) {
            return 0;
        }
        if ($encoded) {
            $data = base64_decode($data);  //OR urldecode
        }
        fwrite($fp, $data); //TODO handle errors
        fclose($fp);
        return 1;
    }

    /**
     *
     * @param type $page_hierarchy
     * @param type $found_pages
     * @return string 'caution'|'new'|'false'
     */
    public function checkPage($page_hierarchy, $found_pages)
    {
        $parents = [];
        foreach ($found_pages as $page) {
            $parents[] = $page['hierarchy_path'];
        }
        $paths = [];
        $db = cmsms()->GetDb();
        $query = 'SELECT hierarchy_path FROM ' . CMS_DB_PREFIX . 'content';
        $rst = $db->Execute($query);
        while ($rst && $row = $rst->FetchRow()) {
            $paths[] = $row['hierarchy_path'];
        }
        if ($rst) $rst->Close();
        $paths = array_unique($paths);
        $parents = array_unique($parents);
        $parent = substr($page_hierarchy, 0, strrpos($page_hierarchy, '/', -2));
        if (in_array($page_hierarchy, $paths)) {
            // page exists in the hierarchy, page can be overwritten!
            return 'caution';
        } elseif (in_array($parent, $paths) || empty($parent) || in_array($parent, $parents)) {
            // page does not exist, but hierarchy exists, page can be created
            return 'new';
        } else {
            // hierarchy path not found, page will not be created
            return 'false';
        }
    }

    /**
     *
     * @param type $string
     * @param bool $strict
     * @return array
     */
    public function get_urls($string, $strict = true)
    {
        $ret = [];
        $innerT = $strict ? '[a-z0-9:?=&@/._-]+?' : '.+?';
        foreach (['href', 'src', 'url'] as $type) {
            preg_match_all("|$type\=([\"'`])(".$innerT.')\\1|i', $string, $matches);
            $ret[$type] = $matches[2];
        }
        return $ret;
    }

    /**
     *
     * @param string $theme 'public' name of theme
     * @throws Exception if file cannot be written
     */
    public function create_manifest(string $theme)
    {
        $themename = sanitizeVal($theme, 3);
        $dir = CMS_THEMES_PATH . DIRECTORY_SEPARATOR . $themename;
        $items = get_recursive_file_list($dir, ['Theme\.cfg', 'Theme\.manifest'], -1, 'FILES');
        if ($items) {
            $outfile = $dir . DIRECTORY_SEPARATOR . 'Theme.manifest';
            $fh = fopen($outfile, 'wb');
            if (!$fh) {
                throw new Exception('Problem opening file ('.$outfile.') for writing');
            }
            $len = strlen($dir) + 1;
            foreach ($items as $path) {
                $rel = substr($path, $len);
//                if (0) { //TODO anything else skipped?
//                    continue;
//                }
                $sig = md5_file($path);
                fwrite($fh, "$rel :: $sig\n");
            }
            fclose($fh);
        }
    }

    /**
     * Quote $str if needed for use as ini-file value
     *
     * @param string $str string to process
     * @return string
     */
    public function smartquote(string $str) : string
    {
        $str = trim($str, ' "');
        if ($str && preg_match('/\s/', $str)) {
            $str = '"' . addcslashes($str, '"') . '"';
        }
        return $str;
    }

    /**
     * Ellipsize $str if needed.
     * Processes bytes, not multi-byte chars.
     *
     * @param string $str string to ellipsize
     * @param int $max_length max length of wanted string Default 15
     * @param int $position (1|0) or float, .5, .2, etc for position to split Default 1
     * @param string $ellipsis use this for ellipsis Default '...' ('&hellip;' can be bad unless double-escaping is prevented)
     * @return ellipsized string
     */
    public function shorten_string(string $str, int $max_length = 15, int $position = 1, string $ellipsis = '&hellip;') : string
    {
        $str = trim($str);
        // is the string long enough to ellipsize?
        if (strlen($str) <= $max_length) {
            return $str;
        }

        if ($position > 1) {
            $position = 1;
        } elseif ($position < 0) {
            $position = 0;
        }
        $beg = substr($str, 0, floor($max_length * $position));

        if ($position === 1) {
            $end = substr($str, 0, -($max_length - strlen($beg)));
        } else {
            $end = substr($str, -($max_length - strlen($beg)));
        }

        return $beg.$ellipsis.$end;
    }

    /**
     * Get a unique theme-folder name for storing files for a theme named $theme
     * @param string $theme theme name
     * @return string possibly with appended '(N)'
     */
    public function unique_name(string $theme) : string
    {
        $base = CMS_THEMES_PATH . DIRECTORY_SEPARATOR;
        $name = sanitizeVal($theme, 3);
        $i = 1;
        while (is_dir($base.$name)) {
            $name .= "($i)";
            ++$i;
        }
        return $name;
    }
}
