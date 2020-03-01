<?php
/*
FilePicker module method - support for file uploading
Copyright (C) 2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\FileType;
use CMSMS\FileTypeHelper;

header('Content-type:application/json;charset=utf-8');

try {
    $key = $params['filefield'] ?? ''; // input[type=file] HTMLElement name
    if (!$key) {
        throw new RuntimeException('Invalid parameters');
    }
    $f = &$_FILES[$key];
    // undefined | $_FILES corruption attack
    // If this request falls under any of them, treat as invalid
    if (!isset($f['error']) || is_array($f['error'])) {
        throw new RuntimeException('Invalid parameters');
    }

    switch ($f['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new RuntimeException('No file sent');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Exceeded filesize limit');
        default:
            throw new RuntimeException('Unknown error');
    }

    $config = cms_config::get_instance();
    if (!empty($fullpath)) {
        $upload_dir = $fullpath;
    }
    else {
        $upload_dir = $config['uploads_path'];
        if (!empty($cwd)) {
            $upload_dir .= DIRECTORY_SEPARATOR . $cwd;
        }
    }
    if( !endswith($upload_dir, DIRECTORY_SEPARATOR) ) {
        $upload_dir .= DIRECTORY_SEPARATOR;
    }
    $maxsize = $config['max_upload_size'];
    $helper = new FileTypeHelper();
    $mime = $helper->get_file_type_mime($profile->type);
    $exts = $helper->get_file_type_extensions($profile->type);
    $dest = null;

    // crappy $_FILES[] arrangement forces these funcs
    $fileval = function($key, $idx = null) use($f)
    {
       return ($idx === null) ? $f[$key] : $f[$key][$idx];
    };
    $do_file = function($idx = null) use($f, $fileval, $upload_dir, $maxsize, $profile, $helper, $mime, $exts, &$dest)
    {
        // Check filesize
        if ($maxsize > 0 && $fileval('size', $idx) > $maxsize) {
            throw new RuntimeException('File exceeded size limit');
        }

        $tmppath = $fileval('tmp_name', $idx);
        // Check content
        if ($profile->type == FileType::IMAGE) {
            if( function_exists('exif_imagetype') ) {
                if (!exif_imagetype($tmppath)) {
                    if (1) { //extra check needed e.g. svg files
                        $here = 1; //DEBUG
                    } else {
                        throw new RuntimeException('Invalid file type');
                    }
                }
            }
            //else fallbacks
        }
        // else others TODO

        $fn = filter_var($fileval('name', $idx), FILTER_SANITIZE_STRING);
        $dest = $upload_dir . $fn;
        if (is_dir($dest) || is_file($dest)) {
            throw new RuntimeException($this->Lang('error_ajax_fileexists'));
        }
        if (move_uploaded_file($tmppath, $dest)) {
            // Check file extension (even tho it might be faked)
            $fileext = $helper->get_extension($dest, false);
            if (!$helper->match_extension($fileext, $exts)) {
                unlink($dest);
                throw new RuntimeException('Invalid file type');
            }
            // Check mimetype (maybe dodgy, depending on installed capabilities)
            $filemime = $helper->get_mime_type($dest);
            if (!startswith($filemime, 'text/html;')) { // skip if the check found nothing recognisable
                if (!$helper->match_mime($filemime, $mime)) {
                    unlink($dest);
                    throw new RuntimeException('Invalid file type');
                }
            }
            // ? $profile->match_prefix and $profile->exclude_prefix checks here
            chmod($dest, 0640);
            if ($profile->type == FileType::IMAGE) {
                if ($profile->show_thumbs && 0) { //$dest is scalable
                     $width = (int) cms_siteprefs::get('thumbnail_width', 96);
                    $height = (int) cms_siteprefs::get('thumbnail_height', 96);
                    if ($width >= 1 && $height >= 1) {
                        //$ores = imagecreatefrom*($dest);
//                      calc new height & width from current values
//                      $nres = imagescale($ores, int $new_width[, int $new_height = -1 [, int $mode = IMG_BILINEAR_FIXED]]);
//                      save as $upload_dir .'thumb_'. $fn;
                    }
                }
            }
        } else {
            throw new RuntimeException('Failed to move uploaded file');
        }
    };

    if (is_array($f['tmp_name'])) {
        for ($idx = 0, $n = count($f['tmp_name']); $idx < n; ++$idx) {
            $do_file($idx);
        }
    } else {
        $do_file();
    }

    // All good, send a response
    echo json_encode([
        'status' => 'ok',
        'path' => $dest
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    // Something went wrong, send an err response
    http_response_code(400);

    echo json_encode([
        'status' => 'error',
        'message' => trim($e->getMessage())
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    // mimic outer catcher
    debug_to_log('Exception: ' . $e->GetMessage());
    debug_to_log($e->GetTraceAsString());
}
exit;
