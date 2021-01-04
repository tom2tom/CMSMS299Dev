<?php
/*
FilePicker module action: ajax_cmd
Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Fernando Morgado and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use FilePicker\PathAssistant;
use FilePicker\TemporaryProfileStorage;
use FilePicker\Utils;

if (!function_exists('cmsms')) exit;

try {
    if (strtolower($_SERVER['REQUEST_METHOD']) != 'post') {
        throw new RuntimeException('Invalid request method');
    }

    $inst = $params['inst'] ?? '';
    // get the profile
    $profile = ($inst) ? TemporaryProfileStorage::get($inst) : null;
    if (!$profile) {
        throw new RuntimeException('Missing profile data');
    }
    $topdir = $profile->top;
    if (!$topdir) {
        throw new RuntimeException('Invalid profile data');
    }
    $assistant = new PathAssistant($config, $topdir);

    // check that the cwd is ok
    $cwd = $params['cwd'] ?? '';
    $fullpath = $assistant->to_absolute($cwd);
    if (!$assistant->is_relative($fullpath)) {
        throw new RuntimeException('Invalid cwd ' . $cwd);
    }

    $cmd = $params['cmd'] ?? '';
    $val = $params['val'] ?? '';
    switch ($cmd) {
        case 'mkdir':
            if (!$profile->can_mkdir) {
                throw new LogicException('Internal error: mkdir command executed, but profile says we cannot do this');
            }
            // no hidden folders
            if (startswith($val, '.') || startswith($val, '_')) {
                throw new RuntimeException($this->Lang('error_ajax_invalidfilename'));
            }
            if (!is_writable($fullpath)) {
                throw new RuntimeException($this->Lang('error_ajax_writepermission'));
            }
            $testpath = cms_join_path($fullpath, $val);
            $destpath = Utils::clean_path($topdir, $testpath);
            if (!$destpath) {
                throw new RuntimeException('Invalid path: '.$testpath);
            }
            if (is_dir($destpath) || is_file($destpath)) {
                throw new RuntimeException($this->Lang('error_ajax_fileexists'));
            }
            if (!@mkdir($destpath)) {
                throw new RuntimeException($this->Lang('error_ajax_mkdir ', $cwd . '/' . $val));
            }
            break;

        case 'del':
            if (!$profile->can_delete) {
                throw new LogicException('Internal error: del command executed, but profile forbids deletions');
            }
            if (startswith($val, '.') || startswith($val, '_')) {
                throw new RuntimeException($this->Lang('error_ajax_invalidfilename'));
            }
            $testpath = cms_join_path($fullpath, $val);
            $destpath = Utils::clean_path($topdir, $testpath);
            if (!$destpath) {
                throw new RuntimeException('Invalid path: '.$testpath);
            }
            if (!(is_file($destpath) || is_dir($destpath))) {
                throw new RuntimeException($this->Lang('error_ajax_invalidfilename'));
            }
            if (!is_writable($destpath)) {
                throw new RuntimeException($this->Lang('error_ajax_writepermission') . ' ' . $destpath);
            }
            if (is_dir($destpath)) {
                recursive_delete($destpath);
            }
            else {
                if ($this->is_image($destpath)) {
                    $thumbnail = $fullpath . DIRECTORY_SEPARATOR . 'thumb_' . $val;
                    if (is_file($thumbnail)) {
                        @unlink($thumbnail);
                    }
                }
                @unlink($destpath);
            }
            break;

        case 'upload':
            if ($profile->can_upload) {
                require_once __DIR__ . DIRECTORY_SEPARATOR . 'method.upload.php';
                //should never return
            }
            else {
                throw new LogicException('Internal error: profile forbids uploads');
            }
            // no break here
        default:
            throw new RuntimeException('Invalid command ' . $cmd);
        }
    }
    catch (Exception $e) {
        debug_to_log('Exception: ' . $e->GetMessage());
//      if (CMS_DEBUG) {
        debug_to_log($e->GetTraceAsString());
//      }
        // throw a 500 error
        header('HTTP/1.1 500 ' . $e->GetMessage());
    }
exit;
