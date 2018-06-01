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

if (!isset($gCms)) exit;
$pdev = $this->CheckPermission('Modify Site Code') || !empty($config['developer_mode']);
if (!($pdev || $this->CheckPermission('Modify Files'))) exit;

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

if (isset($params['upload'])) {
    // Upload
    if (!empty($_FILES)) {
		$errors = 0;
		$uploads = 0;

		$f = $_FILES['file'];
		$dest = $f['name'];
//	    $total = count($dest);

		$allowed = (empty($FM_EXTENSION)) ? false : explode(',', $FM_EXTENSION);
		if ($allowed) {
			$ext = pathinfo($dest, PATHINFO_EXTENSION);
			$isFileAllowed = in_array($ext, $allowed);
		} else {
			$isFileAllowed = true;
		}

		$from = $f['tmp_name'] ?? null;
		if (empty($f['error']) && !empty($from) && $from != 'none' && $isFileAllowed) {
			if (move_uploaded_file($from, $path . DIRECTORY_SEPARATOR . $dest)) {
				echo 'Successfully uploaded';
				++$uploads;
			} else {
				++$errors;
				echo sprintf('Error while uploading files. Uploaded files: %s', $uploads);
			}
		}
	} else {
	    echo 'No file specified';
	}
    exit;
}

if (isset($params['create'], $params['type'])) {
    // Create folder or file
    $newitem = fm_clean_path($params['create']);
    if (!($newitem === '' || $newitem == '..' || $newitem == '.')) {
		$item_path = $path . DIRECTORY_SEPARATOR . $newitem;
        if ($params['type'] == 'file') {
            if (!file_exists($item_path)) {
                @fopen($item_path . $newitem, 'w') or die('Cannot open file:  '.$newitem);
                $this->SetMessage(sprintf('File <strong>%s</strong> created', fm_enc($newitem)));
            } else {
                $this->SetInfo(sprintf('File <strong>%s</strong> already exists', fm_enc($newitem)));
            }
        } elseif ($params['type'] == 'folder') {
            if (fm_mkdir($$item_path, false) === true) {
                $this->SetMessage(sprintf('Folder <strong>%s</strong> created', $newitem));
            } elseif (fm_mkdir($$item_path, false) === $path . DIRECTORY_SEPARATOR . $newitem) {
                $this->SetInfo(sprintf('Folder <strong>%s</strong> already exists', fm_enc($newitem)));
            } else {
                $this->SetError(sprintf('Folder <strong>%s</strong> not created', fm_enc($newitem)));
            }
        }
    } else {
        $this->SetError('Wrong folder name');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
}

if (isset($params['del'])) {
    if (isset($params['sel'])) {
        // Mass delete
        $errors = 0;
        $files = $params['sel'];
        if (is_array($files) && count($files)) {
            foreach ($files as $f) {
                if ($f != '') {
                    $item_path = $path . DIRECTORY_SEPARATOR . $f;
                    if (!fm_rdelete($item_path)) {
                        ++$errors;
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
			$item_path = $path . DIRECTORY_SEPARATOR . $del;
            $is_dir = is_dir($item_path);
            if (fm_rdelete($item_path)) {
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
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
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
        $this->SetInfo('Paths must be different');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
    }
    if (!is_dir($dest_path)) {
        if (!fm_mkdir($dest_path, true)) {
            $this->SetError('Unable to create destination folder');
            $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
        }
    }

    $errors = 0;
    $files = $params['sel'];
    if (is_array($files) && count($files)) {
        $move = !isset($params['copy']) && isset($params['move']); //move instead of copy
        foreach ($files as $f) {
            if ($f != '') {
                // abs path from
                $from = $path . DIRECTORY_SEPARATOR . $f;
                // abs path to
                $dest = $dest_path . DIRECTORY_SEPARATOR . $f;
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
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
}

if (isset($params['oneto'])) {
    // Copy/move one folder/file
    $err = false;
    // from
    $file = fm_clean_path($params['from']);
    if ($file === '') {
    	  $err = true;
        $this->SetError('Source file not defined');
    }
    // to
    $file2 = fm_clean_path($params['to']);
    if ($file2 === '') {
    	  $err = true;
        $this->SetError('Destination file not defined');
    } elseif ($file2) {
    	  $err = true;
        $this->SetError('Paths must be different');
    }
    if ($err) { //TODO
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
    }
    // abs paths
    $from = FM_ROOT_PATH . DIRECTORY_SEPARATOR . $file;
    $dest = FM_ROOT_PATH . DIRECTORY_SEPARATOR . $file2;
    // copy/move
    $msg_from = trim(FM_PATH . DIRECTORY_SEPARATOR . basename($from), DIRECTORY_SEPARATOR);
    if (isset($params['copy'])) {
        if (fm_rcopy($from, $dest)) {
            $this->SetMessage(sprintf('Copied from <strong>%s</strong> to <strong>%s</strong>', fm_enc($copy), fm_enc($msg_from)));
        } else {
            $this->SetError(sprintf('Error while copying from <strong>%s</strong> to <strong>%s</strong>', fm_enc($copy), fm_enc($msg_from)));
        }
    } elseif (isset($params['move'])) {
        $rename = fm_rename($from, $dest);
        if ($rename) {
            $this->SetMessage(sprintf('Moved from <strong>%s</strong> to <strong>%s</strong>', fm_enc($copy), fm_enc($msg_from)));
        } elseif ($rename === null) {
            $this->SetInfo('File or folder with this path already exists');
        } else {
            $this->SetError(sprintf('Error while moving from <strong>%s</strong> to <strong>%s</strong>', fm_enc($copy), fm_enc($msg_from)));
        }
//    } else {
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
}

if (isset($params['ren'], $params['to'])) {
    // Rename, from
    $old = fm_clean_path($params['ren']);
    // to new name
    $newitem = fm_clean_path($params['to']);

    // rename
    if ($old != '' && $newitem != '') {
        if (fm_rename($path . DIRECTORY_SEPARATOR . $old, $path . DIRECTORY_SEPARATOR . $newitem)) {
            $this->SetMessage(sprintf('Renamed from <strong>%s</strong> to <strong>%s</strong>', fm_enc($old), fm_enc($newitem)));
        } else {
            $this->SetError(sprintf('Error while renaming from <strong>%s</strong> to <strong>%s</strong>', fm_enc($old), fm_enc($newitem)));
        }
    } else {
        $this->SetError('Names not set');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
}

if (isset($params['dl'])) {
    // Download
    $file = fm_clean_path($params['dl']);
    $fp = $path . DIRECTORY_SEPARATOR . $file;
    if ($file != '' && is_dir($fp)) {
        $istmp = false;
        foreach (fm_get_arch_types(true) as $ext => $one) {
            if (!empty($one['use'])) {
                $istmp = true;
                break;
            }
        }
        if (!$istmp) {
            $this->SetError($this->Lang('err_noarch'));
            $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
        }
//		if ($ext != 'zip') { $ext = 'tar.'.$ext; }
        $base = basename($file);
        $tmp = tempnam(sys_get_temp_dir(), $base);
		unlink($tmp);
		$tmp .= '.'.$ext;
        try {
            if (UnifiedArchive::archiveFiles([$fp], $tmp) === false) {
                $this->SetError($this->Lang('err_noarch'));
                $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
            }
        } catch (\Exception $e) {
            $this->SetError($this->Lang('err_noarch').' : '.$e->getMessage());
            $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
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
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
    }
}

if (isset($params['compress'], $params['sel'])) {
   // Pack selected items(s)
    $aname = $params['aname'] ?? '';
    $ext = $params['archtype'];
    $files = $params['sel'];

    if (count($files) == 1) {
        $one_file = reset($files);
        $fp = $path . DIRECTORY_SEPARATOR . $one_file;
        if (is_dir($fp)) {
			if ($aname === '') { $aname = basename($one_file); }
//			if ($params['archtype'] != 'zip') { $ext = 'tar.'. $ext; }
        } elseif ($aname === '') {
            $one_file = basename($one_file);
            $aname = substr($one_file, strrpos($one_file, '.') + 1);
        }
        $files = [$fp];
    } else {
		if ($aname === '') { $aname = 'archive'; }
//		if ($params['archtype'] != 'zip') { $ext = 'tar.'. $ext; }
        //fullpath for each
        array_walk($files, function (&$val) {
          $val = $path.DIRECTORY_SEPARATOR.$val;
        }, $path);
    }
    $aname .= /*'_' . date('Ymd-His') .*/ '.' . $ext;

    try {
        if (UnifiedArchive::archiveFiles($files, $path.DIRECTORY_SEPARATOR.$aname) !== false) {
            $this->SetMessage($this->Lang('newarch', fm_enc($aname)));
        } else {
            $this->SetError($this->Lang('err_noarch'));
        }
    } catch (\Exception $e) {
        $this->SetError($this->Lang('err_noarch').' : '.$e->getMessage());
    }

    $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
}

if (isset($params['decompress'], $params['sel'])) {
    // Unpack selected file(s)
    foreach ($params['sel'] as $file) {
        $fp = $path . DIRECTORY_SEPARATOR . $file;
        if ($file != '' && is_file($fp)) {
            try {
                $archive = UnifiedArchive::open($fp);
                if ($archive && $archive->extractFiles($path) !== false) {
					if (count($params['sel']) == 1) { $this->SetMessage($this->Lang('unpackarch')); }
                } else {
                    $this->SetError($this->Lang('err_nounpack', fm_enc($file)));
                }
            } catch (Exception $e) {
                $this->SetError($this->Lang('err_nounpack', fm_enc($file)) .' : '. $e->getMessage());
            }
        } else {
            $this->SetError($this->Lang('err_nofile'));
        }
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
}

if (isset($params['chmod']) && !FM_IS_WIN) {
    // Change Perms (not for Windows)
    $file = fm_clean_path($params['chmod']);
    $fp = $path . DIRECTORY_SEPARATOR . $file;
    if ($file === '' || (!(is_file($fp) || is_dir($fp)))) {
        $this->SetError($this->Lang('err_nofile'));
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
    }

	//TODO get these from some popup ?
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

$this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);

