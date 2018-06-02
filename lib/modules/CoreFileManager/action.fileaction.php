<?php
# CoreFileManager module action: handles various operations
# Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CoreFileManager\UnifiedArchive\UnifiedArchive;

if (!isset($gCms)) {
    exit;
}
$pdev = $this->CheckPermission('Modify Site Code') || !empty($config['developer_mode']);
if (!($pdev || $this->CheckPermission('Modify Files'))) {
    exit;
}

$FM_ROOT_PATH = ($pdev) ? CMS_ROOT_PATH : $config['uploads_path'];
$FM_PATH = $params['p'] ?? '';

$path = $FM_ROOT_PATH;
if ($FM_PATH) {
    $path .= DIRECTORY_SEPARATOR . $FM_PATH;
}
if (!is_dir($path)) { //CHECKME link to a dir ok?
    $path = $FM_ROOT_PATH;
    $FM_PATH = '';
}

// various globals used downstream
global $FM_IS_WIN;
$FM_IS_WIN = DIRECTORY_SEPARATOR == '\\';

require_once __DIR__.DIRECTORY_SEPARATOR.'function.filemanager.php';

function fm_response($type, $msg)
{
    echo json_encode([$type, $msg]);
}
function fm_tarify($ext) {
    if (in_array($ext,['gz','bz2','xz','lzma'])) {
        return 'tar.'.$ext;
    }
    return $ext;
}

if (isset($params['create'], $params['type'])) {
    // Create folder or file
    $newitem = fm_clean_path($params['create']);
    if (!($newitem === '' || $newitem == '..' || $newitem == '.')) {
        $item_path = $path . DIRECTORY_SEPARATOR . $newitem;
        if ($params['type'] == 'file') {
            if (!file_exists($item_path)) {
                if (@fopen($item_path, 'w')) {
                    fm_response('success', $this->Lang('stat_create', fm_enc($newitem)));
                } else {
                    fm_response('error', $this->Lang('err_nofile5', fm_enc($newitem)));
                }
            } else {
                fm_response('error', $this->Lang('err_dup2', fm_enc($newitem)));
            }
        } elseif ($params['type'] == 'folder') {
            if (fm_mkdir($$item_path, false) === true) {
                fm_response('success', $this->Lang('stat_create2', $newitem));
            } elseif (fm_mkdir($$item_path, false) === $item_path) {
                fm_response('error', $this->Lang('err_dup3', fm_enc($newitem)));
            } else {
                fm_response('error', $this->Lang('err_nocreate', fm_enc($newitem)));
            }
        }
    } else {
        fm_response('error', $this->Lang('err_badname'));
    }
    exit;
}

if (isset($params['del'])) {
    if (isset($params['sel'])) {
        // Mass delete
        $errors = 0;
        $items = json_decode(rawurldecode($params['sel']));
        if (is_array($items) && count($items)) {
            foreach ($items as $f) {
                $file = trim($f, ' "\'');
                if ($file !== '') {
                    $item_path = $path . DIRECTORY_SEPARATOR . $file;
                    if (!fm_rdelete($item_path)) {
                        ++$errors;
                    }
                }
            }
            if ($errors == 0) {
                fm_response('success', $this->Lang('stat_del3'));
            } else {
                fm_response('error', $this->Lang('err_del'));
            }
        } else {
            fm_response('error', $this->Lang('err_sel'));
        }
    } else {
        // Delete file / folder
        $del = fm_clean_path($params['del']);
        if ($del !== '' && $del != '..' && $del != '.') {
            $item_path = $path . DIRECTORY_SEPARATOR . $del;
            $is_dir = is_dir($item_path);
            if (fm_rdelete($item_path)) {
                $msg = $is_dir ? 'stat_del2' : 'stat_del';
                fm_response('success', $this->Lang($msg, fm_enc($del)));
            } else {
                $msg = $is_dir ? 'err_nodel' : 'err_nodel2';
                fm_response('error', $this->Lang($msg, fm_enc($del)));
            }
        } else {
            fm_response('error', $this->Lang('err_badname2'));
        }
    }
    exit;
}

if (isset($params['todir'], $params['sel'])) {
    // Mass copy/move files/folders from $path to
    $dest = fm_clean_path($params['todir']);
    if ($dest !== '') {
        $dest_path .= DIRECTORY_SEPARATOR . $dest;
    } else {
        $dest_path = $FM_ROOT_PATH;
    }
    if ($path == $dest_path) {
        fm_response('error', $this->Lang('err_samepath'));
        exit;
    }
    if (!is_dir($dest_path)) {
        if (!fm_mkdir($dest_path, true)) {
            fm_response('error', $this->Lang('err_nocreate2'));
            exit;
        }
    }

    $errors = 0;
    $items = json_decode(rawurldecode($params['sel']));
    if (is_array($items) && count($items)) {
        $move = !isset($params['copy']) && isset($params['move']); //move instead of copy
        foreach ($items as $f) {
            $file = trim($f, ' "\'');
            if ($file !== '') {
                // abs path from
                $from = $path . DIRECTORY_SEPARATOR . $file;
                // abs path to
                $dest = $dest_path . DIRECTORY_SEPARATOR . $file;
                if ($move) {
                    $rename = fm_rename($from, $dest);
                    if ($rename === false) {
                        $errors++;
                    }
                } else {
                    if (!fm_rcopy($from, $dest)) {
                        $errors++;
                    }
                }
            }
        }
        if ($errors == 0) {
            $msg = $move ? 'stat_move2' : 'stat_copy2';
            fm_response('success', $this->Lang($msg));
        } else {
            $msg = $move ? 'err_move' : 'err_copy';
            fm_response('error', $this->Lang($msg));
        }
    } else {
        fm_response('error', $this->Lang('err_sel'));
    }
    exit;
}

if (isset($params['oneto'])) {
    // Copy/move one folder/file
    $msg = [];
    // from
    $file = fm_clean_path($params['from']);
    if ($file === '') {
        $msg[] = $this->Lang('err_nofile3');
    }
    // to
    $file2 = fm_clean_path($params['to']);
    if ($file2 === '') {
        $msg[] = $this->Lang('err_nofile2');
    } elseif ($file2 == $file) {
        $msg[] = $this->Lang('err_samepath');
    }
    if ($msg) {
        fm_response('error', $msg);
        exit;
    }
    // abs paths
    $from = FM_ROOT_PATH . DIRECTORY_SEPARATOR . $file;
    $dest = FM_ROOT_PATH . DIRECTORY_SEPARATOR . $file2;
    // copy/move
    $msg_from = trim(FM_PATH . DIRECTORY_SEPARATOR . basename($from), DIRECTORY_SEPARATOR);
    if (isset($params['copy'])) {
        if (fm_rcopy($from, $dest)) {
            fm_response('success', $this->Lang('stat_copy', fm_enc($copy), fm_enc($msg_from)));
        } else {
            fm_response('error', $this->Lang('err_copy2', fm_enc($copy), fm_enc($msg_from)));
        }
    } elseif (isset($params['move'])) {
        $rename = fm_rename($from, $dest);
        if ($rename) {
            fm_response('success', $this->Lang('stat_move', fm_enc($copy), fm_enc($msg_from)));
        } elseif ($rename === null) {
            fm_response('info', $this->Lang('err_dup'));
        } else {
            fm_response('error', $this->Lang('err_move2', fm_enc($copy), fm_enc($msg_from)));
        }
//    } else {
    }
    exit;
}

if (isset($params['ren'], $params['to'])) {
    // Rename, from
    $old = fm_clean_path($params['ren']);
    // to new name
    $newitem = fm_clean_path($params['to']);

    // rename
    if ($old !== '' && $newitem !== '') {
        if (fm_rename($path . DIRECTORY_SEPARATOR . $old, $path . DIRECTORY_SEPARATOR . $newitem)) {
            fm_response('success', $this->Lang('stat_ren', fm_enc($old), fm_enc($newitem)));
        } else {
            fm_response('error', $this->Lang('err_ren', fm_enc($old), fm_enc($newitem)));
        }
    } else {
        fm_response('error', $this->Lang('err_noname'));
    }
    exit;
}

if (isset($params['ul'])) {
    // Upload
    if (!empty($_FILES)) {
        $f = $_FILES['file'];
        $from = $f['tmp_name'];
        if (empty($f['error']) && !empty($from) && $from != 'none') {
            $dest = $f['name'];
            //TODO validity checks e.g.
            //$profile->can_mkdir
            //$profile->exclude_prefix
            //$profile->match_prefix
            //$profile->type
            $allowed = (empty($FM_EXTENSION)) ? false : explode(',', $FM_EXTENSION);
            if ($allowed) {
                $ext = pathinfo($dest, PATHINFO_EXTENSION);
                $accept = in_array($ext, $allowed);
            } else {
                $accept = true;
            }
            if ($accept) {
                $item_path = $path . DIRECTORY_SEPARATOR . $dest;
                if (move_uploaded_file($from, $item_path)) {
                    fm_response('success', $this->Lang('stat_upped'));
                } else {
                    fm_response('error', $this->Lang('err_upload'));
                }
            }
        }
    } else {
        fm_response('error', $this->Lang('err_nofile'));
    }
    exit;
}

if (isset($params['dl'])) {
    // Download
    $file = fm_clean_path($params['dl']);
    $item_path = $path . DIRECTORY_SEPARATOR . $file;
    if ($file !== '' && is_dir($item_path)) {
        $istmp = false;
        foreach (fm_get_arch_types(true) as $ext => $one) {
            if (!empty($one['use'])) {
                $istmp = true;
                break;
            }
        }
        if ($istmp) {
            fm_response('error', $this->Lang('err_noarch'));
            exit;
        }
        $ext = fm_tarify($ext);
        $base = basename($file);
        $tmp = tempnam(sys_get_temp_dir(), $base);
        unlink($tmp);
        $tmp .= '.'.$ext;
        try {
            if (UnifiedArchive::archiveFiles([$item_path], $tmp) === false) {
                fm_response('error', $this->Lang('err_noarch'));
                exit;
            }
        } catch (\Exception $e) {
            fm_response('error', $this->Lang('err_noarch').' : '.$e->getMessage());
            exit;
        }

        $base .= '.'.$ext;
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $base . '"');
        header('Content-Transfer-Encoding: binary');
        header('Connection: Keep-Alive');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        unlink($tmp);
        exit;
    }

    if ($file !== '' && is_file($item_path)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($item_path) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Connection: Keep-Alive');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($item_path));
        readfile($item_path);
        exit;
    } else {
        fm_response('error', $this->Lang('err_nofile'));
        exit;
    }
}

if (isset($params['compress'], $params['sel'])) {
    // Pack selected items(s)
    $aname = $params['aname'] ?? '';
    $ext = $params['archtype'];
    $items = json_decode(rawurldecode($params['sel']));

    if (count($items) == 1) {
        $f = reset($items);
        $one_file = trim($f, ' "\'');
        $item_path = $path . DIRECTORY_SEPARATOR . $one_file;
        if (is_dir($item_path)) {
            if ($aname === '') {
                $aname = basename($one_file);
            }
            $ext = tarify($ext);
        } elseif ($aname === '') {
            $one_file = basename($one_file);
            $aname = substr($one_file, 0, strrpos($one_file, '.'));
        }
        $items = [$item_path];
    } else {
        if ($aname === '') {
            $aname = 'multi-item-archive';
        }
        $ext = fm_tarify($ext);
        //fullpath for each
        array_walk($items, function (&$val, $key, $path) {
            $val = $path.DIRECTORY_SEPARATOR.$val;
        }, $path);
    }
    $aname .= '.'.$ext;

    $item_path = $path.DIRECTORY_SEPARATOR.$aname;
    try {
        if (UnifiedArchive::archiveNodes($items, $item_path) !== false) {
            fm_response('success', $this->Lang('stat_arch', fm_enc($aname)));
        } else {
            fm_response('error', $this->Lang('err_noarch'));
        }
    } catch (\Exception $e) {
        fm_response('error', $this->Lang('err_noarch').' : '.$e->getMessage());
    }
    exit;
}

if (isset($params['decompress'], $params['sel'])) {
    // Unpack selected file(s)
    $items = json_decode(rawurldecode($params['sel']));
    if (is_array($items) && count($items)) {
        $errors = 0;
        foreach ($items as $f) {
            $file = trim($f, ' "\'');
            $item_path = $path . DIRECTORY_SEPARATOR . $file;
            if ($file !== '' && is_file($item_path)) {
                try {
                    $archive = UnifiedArchive::open($item_path);
                    if ($archive && $archive->extractFiles($path) !== false) {
                    } else {
                        ++$errors;
                    }
                } catch (Exception $e) {
                    ++$errors;
                }
            } else {
                ++$errors;
            }
        }
        if ($errors == 0) {
//          if (count($items) == 1) {
//              fm_response('success', $this->Lang('stat_unpack2'));
//          } else {
                fm_response('success', $this->Lang('stat_unpack'));
//          }
        } else {
            fm_response('error', $this->Lang('err_unpack'));
        }
    } else {
        fm_response('error', $this->Lang('err_sel'));
    }
    exit;
}

if (isset($params['chmod']) && !$FM_IS_WIN) {
    // Change Perms (not for Windows)
    $file = fm_clean_path($params['chmod']);
    $item_path = $path . DIRECTORY_SEPARATOR . $file;
    if ($file === '' || (!(is_file($item_path) || is_dir($item_path)))) {
        fm_response('error', $this->Lang('err_nofile'));
        exit;
    }

    $mode = (int)$params['mode'] & 07777;
    if (@chmod($item_path, $mode)) {
        fm_response('success', $this->Lang('stat_perm'));
    } else {
        fm_response('error', $this->Lang('err_noperm'));
    }
    exit;
}
