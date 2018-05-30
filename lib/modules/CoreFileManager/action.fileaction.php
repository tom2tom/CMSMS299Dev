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

if (!function_exists('cmsms')) exit;
if (!$this->CheckPermission('Modify Files')) exit;

global $FM_IS_WIN;
$FM_IS_WIN = DIRECTORY_SEPARATOR == '\\';

//labels for sizing, used downstream
global $bytename, $kbname, $mbname, $gbname; //$tbname
$bytename = $this->Lang('bb');
$kbname = $this->Lang('kb');
$mbname = $this->Lang('mb');
$gbname = $this->Lang('gb');
//$tbname = $this->Lang('tb');
//$smarty->assign('bytename', $bytename);

require_once __DIR__.DIRECTORY_SEPARATOR.'function.filemanager.php';

//here we assume that simple-plugin processing is handled elsewhere, or else
//simple-plugins storage is somewhere in, or linked into, the uploads dir
$root = (!empty($config['developer_mode'])) ? CMS_ROOT_PATH : $config['uploads_path'];
//TODO maybe and/or some permission e.g. 'Manage Sitecode'
$relpath = $params['p'];
$dir_path = ($relpath) ? cms_join_path($root, $relpath) : $root;

if (isset($params['ajax'])) {
    //AJAX request
    if (isset($params['type']) && $params['type']=='backup') {
        //backup files
        $file = $params['sel'];
        $date = date('Ymd-His');
        $newFile = $file.'-'.$date.'.bak';
        if (copy($dir_path.DIRECTORY_SEPARATOR.$file, $dir_path.DIRECTORY_SEPARATOR.$newFile)) {
            echo "Backup $newFile Created"; //TODO $this->Lang('', $newfile);
        } else {
            echo 'Unable to backup';
        }
    }
    exit;
}

if (!empty($_FILES)) {
    // Upload
    $f = $_FILES;

    $errors = 0;
    $uploads = 0;
    $total = count($f['file']['name']);
    $allowed = (FM_EXTENSION) ? explode(',', FM_EXTENSION) : false;

    $filename = $f['file']['name'];
    $tmp_name = $f['file']['tmp_name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $isFileAllowed = ($allowed) ? in_array($ext, $allowed) : true;

    if (empty($f['file']['error']) && !empty($tmp_name) && $tmp_name != 'none' && $isFileAllowed) {
        if (move_uploaded_file($tmp_name, $dir_path . DIRECTORY_SEPARATOR . $f['file']['name'])) {
            echo 'Successfully uploaded';
        } else {
            echo sprintf('Error while uploading files. Uploaded files: %s', $uploads);
        }
    }
    exit;
}

if (isset($params['delete'])) {
    if (isset($params['sel'])) {
        // Mass delete
        $errors = 0;
        $files = $params['sel'];
        if (is_array($files) && count($files)) {
            foreach ($files as $f) {
                if ($f != '') {
                    $new_path = $dir_path . DIRECTORY_SEPARATOR . $f;
                    if (!fm_rdelete($new_path)) {
                        $errors++;
                    }
                }
            }
            if ($errors == 0) {
                $this->SetMessage('Selected file(s) and/or folder(s) deleted');
            } else {
                $this->SetError('Error while deleting items');
            }
        } else {
            $this->SetWarning('Nothing selected');
        }
    } else {
        // Delete file / folder
        $del = fm_clean_path($params['del']);
        if ($del != '' && $del != '..' && $del != '.') {
            $is_dir = is_dir($dir_path . DIRECTORY_SEPARATOR . $del);
            if (fm_rdelete($dir_path . DIRECTORY_SEPARATOR . $del)) {
                $msg = $is_dir ? 'Folder <strong>%s</strong> deleted' : 'File <strong>%s</strong> deleted';
                $this->SetMessage(sprintf($msg, fm_enc($del)));
            } else {
                $msg = $is_dir ? 'Folder <strong>%s</strong> not deleted' : 'File <strong>%s</strong> not deleted';
                $this->SetError(sprintf($msg, fm_enc($del)));
            }
        } else {
            $this->SetError('Wrong file or folder name');
        }
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['new'], $params['type'])) {
    // Create folder
    $new = strip_tags($params['new']);
    $new = fm_clean_path($params['type']);
    if ($new != '' && $new != '..' && $new != '.') {
        if ($params['type']=='file') {
            if (!file_exists($dir_path . DIRECTORY_SEPARATOR . $new)) {
                @fopen($dir_path . DIRECTORY_SEPARATOR . $new, 'w') or die('Cannot open file:  '.$new);
                $this->SetMessage(sprintf('File <strong>%s</strong> created', fm_enc($new)));
            } else {
                $this->SetInfo(sprintf('File <strong>%s</strong> already exists', fm_enc($new)));
            }
        } else {
            if (fm_mkdir($dir_path . DIRECTORY_SEPARATOR . $new, false) === true) {
                $this->SetMessage(sprintf('Folder <strong>%s</strong> created', $new));
            } elseif (fm_mkdir($dir_path . DIRECTORY_SEPARATOR . $new, false) === $dir_path . DIRECTORY_SEPARATOR . $new) {
                $this->SetInfo(sprintf('Folder <strong>%s</strong> already exists', fm_enc($new)));
            } else {
                $this->SetError(sprintf('Folder <strong>%s</strong> not created', fm_enc($new)));
            }
        }
    } else {
        $this->SetError('Wrong folder name');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['copy'], $params['finish'])) {
    // Copy folder / file
    // from
    $copy = fm_clean_path($params['copy']);
    // empty path
    if ($copy == '') {
        $this->SetError('Source path not defined');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }
    // abs path from
    $from = FM_ROOT_PATH . DIRECTORY_SEPARATOR . $copy;
    // abs path to
    $dest = FM_ROOT_PATH;
    if (FM_PATH != '') {
        $dest .= DIRECTORY_SEPARATOR . FM_PATH;
    }
    $dest .= DIRECTORY_SEPARATOR . basename($from);
    // copy/move
    if ($from != $dest) {
        $msg_from = trim(FM_PATH . DIRECTORY_SEPARATOR . basename($from), DIRECTORY_SEPARATOR);
        if (isset($params['move'])) {
            $rename = fm_rename($from, $dest);
            if ($rename) {
                $this->SetMessage(sprintf('Moved from <strong>%s</strong> to <strong>%s</strong>', fm_enc($copy), fm_enc($msg_from)));
            } elseif ($rename === null) {
                $this->SetInfo('File or folder with this path already exists');
            } else {
                $this->SetError(sprintf('Error while moving from <strong>%s</strong> to <strong>%s</strong>', fm_enc($copy), fm_enc($msg_from)));
            }
        } else {
            if (fm_rcopy($from, $dest)) {
                $this->SetMessage(sprintf('Copied from <strong>%s</strong> to <strong>%s</strong>', fm_enc($copy), fm_enc($msg_from)));
            } else {
                $this->SetError(sprintf('Error while copying from <strong>%s</strong> to <strong>%s</strong>', fm_enc($copy), fm_enc($msg_from)));
            }
        }
    } else {
        $this->SetWarn('Paths must be different');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['sel'], $params['copy_to'])) {
    // Mass copy/move files/folders from $dir_path to
    $copy_to = fm_clean_path($params['copy_to']);
    if ($copy_to != '') {
        $copy_to_path .= DIRECTORY_SEPARATOR . $copy_to;
    } else {
        $copy_to_path = $FM_ROOT_PATH;
    }
    if ($dir_path == $copy_to_path) {
        $this->SetInfo('Paths must be different');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }
    if (!is_dir($copy_to_path)) {
        if (!fm_mkdir($copy_to_path, true)) {
            $this->SetError('Unable to create destination folder');
            $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
        }
    }
    // copy/move
    $errors = 0;
    $files = $params['sel'];
    if (is_array($files) && count($files)) {
	    $move = isset($params['move']);
        foreach ($files as $f) {
            if ($f != '') {
                // abs path from
                $from = $dir_path . DIRECTORY_SEPARATOR . $f;
                // abs path to
                $dest = $copy_to_path . DIRECTORY_SEPARATOR . $f;
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
            $msg = $move ? 'Selected files and folders moved' : 'Selected files and folders copied';
            $this->SetMessage($msg);
        } else {
            $msg = $move ? 'Error while moving items' : 'Error while copying items';
            $this->SetError($msg);
        }
    } else {
        $this->SetWarning('Nothing selected');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['ren'], $params['to'])) {
    // Rename, from
    $old = fm_clean_path($params['ren']);
    // to new name
    $new = fm_clean_path($params['to']);

    // rename
    if ($old != '' && $new != '') {
        if (fm_rename($dir_path . DIRECTORY_SEPARATOR . $old, $dir_path . DIRECTORY_SEPARATOR . $new)) {
            $this->SetMessage(sprintf('Renamed from <strong>%s</strong> to <strong>%s</strong>', fm_enc($old), fm_enc($new)));
        } else {
            $this->SetError(sprintf('Error while renaming from <strong>%s</strong> to <strong>%s</strong>', fm_enc($old), fm_enc($new)));
        }
    } else {
        $this->SetError('Names not set');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['dl'])) {
    // Download
    $file = fm_clean_path($params['dl']);
    $fp = $dir_path . DIRECTORY_SEPARATOR . $file;

    if ($file != '' && is_file($fp)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($fp) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Connection: Keep-Alive');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fp));
        readfile($fp);
        exit;
    } else {
        $this->SetError($this->Lang('err_nofile'));
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }
}

if (isset($params['compress'])) {
    // Pack files
    // TODO per wanted archive-type
    if (!class_exists('ZipArchive')) {
        $this->SetError('Operations with archives are not available');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }

    $files = $params['sel'];
    if (!empty($files)) {
        chdir($dir_path);

        if (count($files) == 1) {
            $one_file = reset($files);
            $one_file = basename($one_file);
            $zipname = $one_file . '_' . date('Ymd-His') . '.zip';
        } else {
            $zipname = 'archive_' . date('Ymd-His') . '.zip';
        }

        $zipper = new FM_Zipper();
        $res = $zipper->create($zipname, $files);

        if ($res) {
            $this->SetMessage(sprintf('Archive <strong>%s</strong> created', fm_enc($zipname)));
        } else {
            $this->SetError('Archive not created');
        }
    } else {
        $this->SetInfo('Nothing selected');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['decompress'])) {
    // Unpack
    $file = fm_clean_path($params['unzip']);

    // TODO per archive-type
    if (!class_exists('ZipArchive')) {
        $this->SetError('Operations with archives are not available');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }

    $fp = $dir_path . DIRECTORY_SEPARATOR . $file;
    if ($file != '' && is_file($fp)) {
        //to folder
        $tofolder = '';
        if (isset($params['tofolder'])) {
            $tofolder = pathinfo($fp, PATHINFO_FILENAME);
            if (fm_mkdir($dir_path . DIRECTORY_SEPARATOR . $tofolder, true)) {
                $dir_path .= DIRECTORY_SEPARATOR . $tofolder;
            }
        }

        $zipper = new FM_Zipper();
        $res = $zipper->unzip($fp, $dir_path);

        if ($res) {
            $this->SetMessage('Archive unpacked');
        } else {
            $this->SetError('Archive not unpacked');
        }
    } else {
        $this->SetError($this->Lang('err_nofile'));
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['chmod']) && !FM_IS_WIN) {
    // Change Perms (not for Windows)
    $file = fm_clean_path($params['chmod']);
    $fp = $dir_path . DIRECTORY_SEPARATOR . $file;
    if ($file == '' || (!(is_file($fp) || is_dir($fp)))) {
        $this->SetError($this->Lang('err_nofile'));
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }

    $mode = 0;
    if (!empty($params['ur'])) {
        $mode |= 0400;
    }
    if (!empty($params['uw'])) {
        $mode |= 0200;
    }
    if (!empty($params['ux'])) {
        $mode |= 0100;
    }
    if (!empty($params['gr'])) {
        $mode |= 0040;
    }
    if (!empty($params['gw'])) {
        $mode |= 0020;
    }
    if (!empty($params['gx'])) {
        $mode |= 0010;
    }
    if (!empty($params['or'])) {
        $mode |= 0004;
    }
    if (!empty($params['ow'])) {
        $mode |= 0002;
    }
    if (!empty($params['ox'])) {
        $mode |= 0001;
    }

    if (@chmod($fp, $mode)) {
        $this->SetMessage('Permissions changed');
    } else {
        $this->SetError('Permissions not changed');
    }
}

$this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);

