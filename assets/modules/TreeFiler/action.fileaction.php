<?php
/*
TreeFiler module action: handles various operations
Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use TreeFiler\UnifiedArchive\UnifiedArchive;

if (!function_exists('cmsms')) {
    exit;
}
$pdev = $this->CheckPermission('Modify Site Code') || !empty($config['developer_mode']);
$pass = false; //$this->CheckPermission('Modify Site Assets');
if (!($pdev || $pass || $this->CheckPermission('Modify Files'))) {
    if (!isset($params['dl'])) {  // download doesn't need permission
        exit;
    }
}

$doass = false; //!empty(($params['astfiles']));
if ($pdev && !$doass) {
    $CFM_ROOTPATH = CMS_ROOT_PATH;
//} elseif (($pdev || $pass) && $doass) {
//    $CFM_ROOTPATH = CMS_ASSETS_PATH;
} else {
    $CFM_ROOTPATH = $config['uploads_path'];
}
$CFM_RELPATH = $params['p'] ?? '';

$path = $CFM_ROOTPATH;
if ($CFM_RELPATH) {
    $path .= DIRECTORY_SEPARATOR . $CFM_RELPATH;
}
if (!is_dir($path)) { //CHECKME link to a dir ok?
    $path = $CFM_ROOTPATH;
    $CFM_RELPATH = '';
}

// various globals used downstream
global $CFM_IS_WIN;
$CFM_IS_WIN = DIRECTORY_SEPARATOR == '\\';

require_once __DIR__.DIRECTORY_SEPARATOR.'function.filemanager.php';

function cfm_response($type, $msg)
{
    echo json_encode([$type, $msg]);
}

if (isset($params['create'], $params['type'])) {
    // Create folder or file
    $newitem = cfm_clean_path($params['create']);
    if (!($newitem === '' || $newitem == '..' || $newitem == '.')) {
        $item_path = $path . DIRECTORY_SEPARATOR . $newitem;
        $user_id = get_userid(false);
        if ($params['type'] == 'file') {
            if (!cfm_validate($this, $item_path, 1, $user_id)) {
                cfm_response('error', $this->Lang('err_auth'));
            } elseif (!file_exists($item_path)) {
                if (@fopen($item_path, 'w')) {
                    cfm_response('success', $this->Lang('stat_create', cfm_enc($newitem)));
                } else {
                    cfm_response('error', $this->Lang('err_nofile5', cfm_enc($newitem)));
                }
            } else {
                cfm_response('error', $this->Lang('err_dup2', cfm_enc($newitem)));
            }
        } elseif ($params['type'] == 'folder') {
            if (is_dir($item_path)) {
                cfm_response('error', $this->Lang('err_dup3', cfm_enc($newitem)));
            } elseif (!cfm_validate($this, $item_path, 2, $user_id)) {
                cfm_response('error', $this->Lang('err_auth'));
            } elseif (cfm_mkdir($item_path)) {
                cfm_response('success', $this->Lang('stat_create2', $newitem));
            } else {
                cfm_response('error', $this->Lang('err_nocreate', cfm_enc($newitem)));
            }
        }
    } else {
        cfm_response('error', $this->Lang('err_badname'));
    }
    exit;
}

if (isset($params['del'])) {
    $user_id = get_userid(false);
    if (!cfm_validate($this, $path, 3, $user_id)) {
        cfm_response('error', $this->Lang('err_auth'));
    } elseif (isset($params['sel'])) {
        // Multi delete
        $errors = 0;
        $items = json_decode(rawurldecode($params['sel']));
        if (is_array($items) && count($items)) {
            foreach ($items as $f) {
                $file = trim($f, ' "\'');
                if ($file !== '') {
                    $item_path = $path . DIRECTORY_SEPARATOR . $file;
                    if (!cfm_rdelete($item_path)) {
                        ++$errors;
                    }
                }
            }
            if ($errors == 0) {
                cfm_response('success', $this->Lang('stat_del3'));
            } else {
                cfm_response('error', $this->Lang('err_del'));
            }
        } else {
            cfm_response('error', $this->Lang('err_sel'));
        }
    } else {
        // Delete file / folder
        $del = cfm_clean_path($params['del']);
        if ($del !== '' && $del != '..' && $del != '.') {
            $item_path = $path . DIRECTORY_SEPARATOR . $del;
            $is_dir = is_dir($item_path);
            if (cfm_rdelete($item_path)) {
                $msg = $is_dir ? 'stat_del2' : 'stat_del';
                cfm_response('success', $this->Lang($msg, cfm_enc($del)));
            } else {
                $msg = $is_dir ? 'err_nodel' : 'err_nodel2';
                cfm_response('error', $this->Lang($msg, cfm_enc($del)));
            }
        } else {
            cfm_response('error', $this->Lang('err_badname2'));
        }
    }
    exit;
}

if (isset($params['todir'], $params['sel'])) {
    // Multi copy/move from $path to
    $dest = cfm_clean_path($params['todir']);
    if ($dest !== '') {
        $dest_path .= DIRECTORY_SEPARATOR . $dest;
    } else {
        $dest_path = $CFM_ROOTPATH;
    }
    if ($path == $dest_path) {
        cfm_response('error', $this->Lang('err_samepath'));
        exit;
    }
    $user_id = get_userid(false);
    if (!is_dir($dest_path)) {
        if (!cfm_validate($this, $dest_path, 2, $user_id)) {
            cfm_response('error', $this->Lang('err_auth'));
            exit;
        }
        if (!cfm_mkdir($dest_path)) {
            cfm_response('error', $this->Lang('err_nocreate2'));
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
                $op = (is_dir($from)) ? 2 : 1;
                if (!cfm_validate($this, $dest, $op, $user_id)) {
                    ++$errors;
                }
                if ($move) {
                    if (cfm_rename($from, $dest) === false) {
                        ++$errors;
                    }
                } elseif (!cfm_rcopy($from, $dest)) {
                    ++$errors;
                }
            }
        }
        if ($errors == 0) {
            $msg = $move ? 'stat_move2' : 'stat_copy2';
            cfm_response('success', $this->Lang($msg));
        } else {
            $msg = $move ? 'err_move' : 'err_copy';
            cfm_response('error', $this->Lang($msg));
        }
    } else {
        cfm_response('error', $this->Lang('err_sel'));
    }
    exit;
}

if (isset($params['oneto'])) {
    // Copy/move one folder/file
    $msg = [];
    // from
    $file = cfm_clean_path($params['from']);
    if ($file === '') {
        $msg[] = $this->Lang('err_nofile3');
    }
    // to
    $file2 = cfm_clean_path($params['to']);
    if ($file2 === '') {
        $msg[] = $this->Lang('err_nofile2');
    } elseif ($file2 == $file) {
        $msg[] = $this->Lang('err_samepath');
    }
    if ($msg) {
        cfm_response('error', $msg);
        exit;
    }
    // abs paths
    $from = $CFM_ROOTPATH . DIRECTORY_SEPARATOR . $file;
    $dest = $CFM_ROOTPATH . DIRECTORY_SEPARATOR . $file2;
    // copy/move
    $op = (is_dir($from)) ? 2 : 1;
    $user_id = get_userid(false);
    if (!cfm_validate($this, $dest, $op, $user_id)) {
        cfm_response('error', $this->Lang('err_auth'));
        exit;
    }
    $msg_from = trim($CFM_RELPATH . DIRECTORY_SEPARATOR . basename($from), DIRECTORY_SEPARATOR);
    if (isset($params['copy'])) {
        if (cfm_rcopy($from, $dest)) {
            cfm_response('success', $this->Lang('stat_copy', cfm_enc($copy), cfm_enc($msg_from)));
        } else {
            cfm_response('error', $this->Lang('err_copy2', cfm_enc($copy), cfm_enc($msg_from)));
        }
    } elseif (isset($params['move'])) {
        $rename = cfm_rename($from, $dest);
        if ($rename) {
            cfm_response('success', $this->Lang('stat_move', cfm_enc($copy), cfm_enc($msg_from)));
        } elseif ($rename === null) {
            cfm_response('info', $this->Lang('err_dup'));
        } else {
            cfm_response('error', $this->Lang('err_move2', cfm_enc($copy), cfm_enc($msg_from)));
        }
//    } else {
    }
    exit;
}

if (isset($params['ren'], $params['to'])) {
    // Rename, from
    $old = cfm_clean_path($params['ren']);
    // to new name
    $newitem = cfm_clean_path($params['to']);

    // rename
    if ($old !== '' && $newitem !== '') {
        if (cfm_rename($path . DIRECTORY_SEPARATOR . $old, $path . DIRECTORY_SEPARATOR . $newitem)) {
            cfm_response('success', $this->Lang('stat_ren', cfm_enc($old), cfm_enc($newitem)));
        } else {
            cfm_response('error', $this->Lang('err_ren', cfm_enc($old), cfm_enc($newitem)));
        }
    } else {
        cfm_response('error', $this->Lang('err_noname'));
    }
    exit;
}

if (isset($params['dnd'])) {
    // DnD
    $from = $path;
    $srcdata = json_decode($params['from'], true);
    if (!empty($srcdata['dir'])) {
        $from .= DIRECTORY_SEPARATOR . $srcdata['dir'];
    }
    if (!empty($srcdata['file'])) {
        $from .= DIRECTORY_SEPARATOR . $srcdata['file'];
    }

    $dest = $path;
    $destdata = json_decode($params['to'], true);
    if (!empty($destdata['dir'])) {
        $dest .= DIRECTORY_SEPARATOR . $destdata['dir'];
    }
    if (!empty($srcdata['dir'])) {
        $dest .= DIRECTORY_SEPARATOR . $srcdata['dir'];
    }
    if (!empty($srcdata['file'])) {
        $dest .= DIRECTORY_SEPARATOR . $srcdata['file'];
    }

    if ($from == $dest) {
        cfm_response('error', $this->Lang('err_samepath'));
        exit;
    } elseif ($from == $path) {
        cfm_response('error', $this->Lang('err_nofile3'));
        exit;
    }

    $op = (is_dir($from)) ? 2 : 1;
    $user_id = get_userid(false);
    if (!cfm_validate($this, $dest, $op, $user_id)) {
        cfm_response('error', $this->Lang('err_auth'));
        exit;
    }

    switch ($params['dnd']) { //'move' or 'copy'
        case 'copy':
            if (is_dir($from)) {
                if (cfm_mkdir($dest)) {
                    if (!cfm_rcopy($from, $dest, false, false)) {
                        cfm_response('error', $this->Lang('err_copy2', cfm_enc(basename($dest)), cfm_enc(dirname($dest))));
                    }
                } else {
                    cfm_response('error', $this->Lang('err_dup3', cfm_enc(basename($dest))));
                }
            } elseif (!cfm_copy($from, $dest, false)) {
                cfm_response('error', $this->Lang('err_copy2', cfm_enc(basename($from)), cfm_enc(dirname($dest))));
            }
            break;
        default:
            if (!cfm_rename($from, $dest)) {
                cfm_response('error', $this->Lang('err_move2', cfm_enc(basename($from)), cfm_enc(dirname($dest))));
            }
            break;
    }
    exit;
}

if (isset($params['ul'])) {
    // Upload
    if (!empty($_FILES)) {
        $f = $_FILES['file'];
        $from = $f['tmp_name'];
        if (empty($f['error']) && !empty($from) && $from != 'none') {
            $dest = $path . DIRECTORY_SEPARATOR . $f['name'];
            $op = (is_dir($from)) ? 2 : 1;
            $user_id = get_userid(false);
            if (!cfm_validate($this, $dest, $op, $user_id)) {
                cfm_response('error', $this->Lang('err_auth'));
            } elseif (move_uploaded_file($from, $dest)) {
                cfm_response('success', $this->Lang('stat_upped'));
            } else {
                cfm_response('error', $this->Lang('err_upload'));
            }
        }
    } else {
        cfm_response('error', $this->Lang('err_nofile'));
    }
    exit;
}

if (isset($params['dl'])) {
    // Download
    $file = cfm_clean_path($params['dl']);
    $item_path = $path . DIRECTORY_SEPARATOR . $file;
    if ($file !== '' && is_dir($item_path)) {
        $istmp = false;
        foreach (cfm_get_arch_types(true) as $ext => $one) {
            if (!empty($one['use'])) {
                $istmp = true;
                break;
            }
        }
        if ($istmp) {
            cfm_response('error', $this->Lang('err_noarch'));
            exit;
        }
        $ext = cfm_tarify($ext);
        $base = basename($file);
        $tmp = tempnam(sys_get_temp_dir(), $base);
        unlink($tmp);
        $tmp .= '.'.$ext;
        try {
            if (UnifiedArchive::archiveFiles([$item_path], $tmp) === false) {
                cfm_response('error', $this->Lang('err_noarch'));
                exit;
            }
        } catch (\Exception $e) {
            cfm_response('error', $this->Lang('err_noarch').' : '.$e->getMessage());
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
        cfm_response('error', $this->Lang('err_nofile'));
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
            $ext = cfm_tarify($ext);
        } elseif ($aname === '') {
            $one_file = basename($one_file);
            $aname = substr($one_file, 0, strrpos($one_file, '.'));
        }
        $items = [$item_path];
    } else {
        if ($aname === '') {
            $aname = 'multi-item-archive';
        }
        $ext = cfm_tarify($ext);
        //fullpath for each
        array_walk($items, function (&$val, $key, $path) {
            $val = $path.DIRECTORY_SEPARATOR.$val;
        }, $path);
    }
    $aname .= '.'.$ext;

    $item_path = $path.DIRECTORY_SEPARATOR.$aname;
    try {
        if (UnifiedArchive::archiveNodes($items, $item_path) !== false) {
            cfm_response('success', $this->Lang('stat_arch', cfm_enc($aname)));
        } else {
            cfm_response('error', $this->Lang('err_noarch'));
        }
    } catch (\Exception $e) {
        cfm_response('error', $this->Lang('err_noarch').' : '.$e->getMessage());
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
//              cfm_response('success', $this->Lang('stat_unpack2'));
//          } else {
                cfm_response('success', $this->Lang('stat_unpack'));
//          }
        } else {
            cfm_response('error', $this->Lang('err_unpack'));
        }
    } else {
        cfm_response('error', $this->Lang('err_sel'));
    }
    exit;
}

if (isset($params['chmod']) && !$CFM_IS_WIN) {
    // Change perms (not for Windows)
    $file = cfm_clean_path($params['chmod']);
    $item_path = $path . DIRECTORY_SEPARATOR . $file;
    if ($file === '' || (!(is_file($item_path) || is_dir($item_path)))) {
        cfm_response('error', $this->Lang('err_nofile'));
        exit;
    }

    $mode = (int)$params['mode'] & 07777;
    // see also: cfm_rchmod()
    if (@chmod($item_path, $mode)) {
        cfm_response('success', $this->Lang('stat_perm'));
    } else {
        cfm_response('error', $this->Lang('err_noperm'));
    }
    exit;
}
