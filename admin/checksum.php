<?php
/*
Admin operation: generate and display checksum
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Error403Exception;
use CMSMS\Lone;
use function CMSMS\get_site_UUID;

$orig_memory = (function_exists('memory_get_usage') ? memory_get_usage() : 0);

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$userid = get_userid();
$access = check_permission($userid, 'Modify Site Preferences');
if (!$access) {
    //TODO some pushed popup  _la('needpermissionto', '"Modify Site Preferences"')
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

$themeObject = Lone::get('Theme');
$urlext = get_secure_param();

require_once cms_join_path(CMS_ROOT_PATH, 'lib', 'test.functions.php');

function checksum_lang($params, $smarty)
{
    if (isset($params['key'])) {
        return _la($params['key']);
    }
}

function check_checksum_data(&$report)
{
    if ((!isset($_FILES['cksumdat'])) || empty($_FILES['cksumdat']['name'])) {
        $report = _la('error_nofileuploaded');
        return false;
    } elseif ($_FILES['cksumdat']['error'] > 0) {
        $report = _la('error_uploadproblem');
        return false;
    } elseif ($_FILES['cksumdat']['size'] == 0) {
        $report = _la('error_uploadproblem');
        return false;
    }

    $fh = fopen($_FILES['cksumdat']['tmp_name'], 'r');
    if (!$fh) {
        $report = _la('error_uploadproblem');
        return false;
    }

    $salt = get_site_UUID();
    $filenotfound = [];
    $notreadable = 0;
    $md5failed = 0;
    $filesfailed = [];
    $filespassed = 0;
    $errorlines = 0;
    $root = realpath(CMS_ROOT_PATH);
    while (!feof($fh)) {
        // get a line
        set_time_limit(10);
        $line = fgets($fh, 4096);

        // strip out comments
        $pos = strpos($line, '#');
        if ($pos !== false) {
            $line = substr($line, 0, $pos);
        }

        // trim the line
        $line = trim($line);

        // skip empty line
        if (empty($line)) {
            continue;
        }

        // split it into fields
        if (strstr($line, '--::--') === false) {
            ++$errorlines;
            continue;
        }
        list($md5sum, $file) = explode('--::--', $line, 2);

        if (!$md5sum || !$file) {
            ++$errorlines;
            continue;
        }

        $md5sum = trim($md5sum);
        $file = trim($file);

        $fn = cms_join_path(CMS_ROOT_PATH, $file);

        $fn = realpath($fn);
        if ($fn === false) {
            $filenotfound[] = $file;
            continue;
        }

        if (!startswith($fn, $root)) {
            continue;
        }
        $file = substr($fn, strlen($root));

        if (is_dir($fn)) {
            continue;
        }

        if (!is_readable($fn)) {
            ++$notreadable;
            continue;
        }

        $md5 = md5($salt.md5_file($fn));
        if (!$md5) {
            ++$md5failed;
            continue;
        }

        if ($md5sum != $md5) {
            $filesfailed[] = $file;
        }

        // it passed.
        ++$filespassed;
    }
    fclose($fh);

    if ($filespassed == 0 || count($filenotfound) || $errorlines || $notreadable || $md5failed || count($filesfailed)) {
        // build the error report
        $tmp2 = [];
        if ($filespassed == 0) {
            $tmp2[] = _la('no_files_scanned');
        }
        if ($errorlines) {
            $tmp2[] = _la('lines_in_error', $errorlines);
        }
        if ($filenotfound) {
            $tmp2[] = sprintf('%d %s', count($filenotfound), _la('files_not_found'));
        }
        if ($notreadable) {
            $tmp2[] = sprintf('%d %s', $notreadable, _la('files_not_readable'));
        }
        if ($md5failed) {
            $tmp2[] = sprintf('%d %s', $md5failed, _la('files_checksum_failed'));
        }
        if (!empty($tmp)) {
            $tmp .= '<br>';
        }

        $tmp = implode('<br>', $tmp2);
        if ($filenotfound) {
            $tmp .= '<br>'._la('files_not_found').':';
            $tmp .= '<br>'.implode('<br>', $filenotfound).'<br>';
        }
        if ($filesfailed) {
            $tmp .= '<br>'.count($filesfailed).' '._la('files_failed').':';
            $tmp .= '<br>'.implode('<br>', $filesfailed).'<br>';
        }

        $report = $tmp;
        return false;
    }

    return true;
}

function generate_checksum_file(&$report)
{
    $excludes = ['.*\.svn.*', '.*\.git.*', 'CVS$', '^\#.*\#$', '~$', '\.bak$', '^uploads$', '^tmp$', '^captchas$', '.*UNUSED.*', '.*phar_installer.*'];
    try {
        $tmp = get_recursive_file_list(CMS_ROOT_PATH, $excludes, -1, 'FILES');
    } catch (Throwable $t) {
        $report = $t->getMessage();
        if (($p = stripos($report, CMS_ROOT_PATH)) !== false) {
            $report = substr($report, $p + strlen(CMS_ROOT_PATH) + 1);
        }
        return false;
    }
    if (count($tmp) <= 1) {
        $report = _la('error_retrieving_file_list');
        return false;
    }
    // lang files last (per FR)
    $m = DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR;
    usort($tmp, function($pa, $pb) use ($m) {
        if (strpos($pa, $m) === false) {
            return -1;
        }
        if (strpos($pb, $m) === false) {
            return 1;
        }
        $i = strcasecmp(dirname($pa), dirname($pb));
        if ($i === 0) {
            $i = strcasecmp(basename($pa), basename($pb));
        }
        return $i;
    });

    $output = '';
    $salt = get_site_UUID();

    foreach ($tmp as $file) {
        if (!is_link($file)) {
            $md5sum = md5($salt.md5_file($file));
            $file = str_replace(CMS_ROOT_PATH, '', $file);
            $output .= "{$md5sum}--::--{$file}\n";
        }
    }

    $handlers = ob_list_handlers();
    for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) {
        ob_end_clean();
    }
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false);
    header('Content-Description: File Transfer');
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="checksum.dat"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . strlen($output));
    echo $output;
    exit;
}

$smarty = Lone::get('Smarty');
// Get ready
$smarty->registerPlugin('function', 'lang', 'checksum_lang');
$smarty->force_compile = true;

// Handle output
$res = true;
$report = '';
if (isset($_POST['action'])) {
    switch ($_POST['action']) { // no sanitizeVal() etc cuz only explicit vals accepted
      case 'upload':
        $res = check_checksum_data($report);
        if ($res === true) {
            $themeObject->RecordNotice('success', _la('checksum_passed'));
        }
        break;
      case 'download':
        $res = generate_checksum_file($report);
        break;
    }
}

if (!$res) {
    $themeObject->RecordNotice('error', $report);
}

$extras = get_secure_param_array();

$smarty->assign([
    'urlext' => $urlext,
    'extraparms' => $extras,
]);

$content = $smarty->fetch('checksum.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
