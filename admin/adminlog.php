<?php
/*
Display or process the admin log
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

//use CMSMS\Url;
//use CMSMS\UserParams;
use CMSMS\AppParams;
use CMSMS\Log\logfilter;
use CMSMS\SingleItem;
use CMSMS\UserParams;
use function CMSMS\de_specialize_array;
use function CMSMS\log_notice;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$themeObject = SingleItem::Theme();

$userid = get_userid(false);
$pclear = check_permission($userid, 'Clear Admin Log');
$psee = $pclear || check_permission($userid, 'View Admin Log');

if ($pclear && isset($_GET['clear'])) {
    SingleItem::LogOperations()->clear();
    unset($_SESSION['adminlog_filter']);
    log_notice('Admin log cleared');
    $themeObject->RecordNotice('success', _la('adminlogcleared'));
} elseif (isset($_GET['download'])) {
    if (isset($_SESSION['adminlog_filter']) && $_SESSION['adminlog_filter'] instanceof logfilter) {
        $filter = $_SESSION['adminlog_filter'];
    } else {
        $filter = new logfilter();
    }
    // override the limit to 1000000 lines
    $filter->limit = 1000000;
    $query = SingleItem::LogOperations()->query($filter);
    if ($query && !$query->EOF()) {
        $format = trim(UserParams::get_for_user($userid, 'datetime_format'));
        if (!$format) $format = trim(AppParams::get('datetime_format'));
        if (!$format) $format = 'Y-m-d H:i';
        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="adminlog.txt"');
        do {
            $row = $query->GetObject(); // timestamp severity user_id username item_id subject message ip_addr
            echo date($format, $row['timestamp']).'|';
            echo $row['username'] . '|';
            echo (((int)$row['item_id'] == -1) ? '' : $row['item_id']) . '|';
            echo $row['subject'] . '|';
            echo $row['message'];
            echo "\n";
            $query->MoveNext();
        } while (!$query->EOF());
    }
    if ($query) {
        $query->Close();
    }
    exit;
}

if (isset($_POST['filter'])) {
    de_specialize_array($_POST);
    $filter = new logfilter();
    $filter->severity = (int)($_POST['f_sev'] ?? 0);
    $filter->username = trim($_POST['f_user'] ?? ''); // TODO , CMSMS\sanitizeVal(, CMSSAN_ACCOUNT) etc
    $filter->subject = trim($_POST['f_subj'] ?? ''); // sanitizeVal(, CMSSAN_NONPRINT)
    $filter->message = trim($_POST['f_msg'] ?? ''); // sanitizeVal(, CMSSAN_NONPRINT)
    $_SESSION['adminlog_filter'] = $filter;
    $filter_applied = true;
} elseif (isset($_SESSION['adminlog_filter']) && $_SESSION['adminlog_filter'] instanceof filter) {
    $filter = $_SESSION['adminlog_filter'];
    $filter_applied = true;
} else {
    $filter = new logfilter();
    $filter_applied = false;
}

$page = (int)($_POST['page'] ?? 1);
if ($page > 1) {
    $filter->offset = ($page - 1) * $filter->limit;
}

$pagelist = [];
$query = SingleItem::LogOperations()->query($filter);
$np = $query->numpages;
if ($np > 0) {
    if ($np < 25) {
        for ($i = 1; $i <= $np; $i++) {
            $pagelist[$i] = $i;
        }
    } else {
        // first 5
        for ($i = 0; $i <= 5; $i++) {
            $pagelist[$i] = $i;
        }
        $tpage = $page;
        if ($tpage <= 5 || $tpage >= ($np - 5)) $tpage = $np / 2;
        $x1 = max(1, (int)($tpage - 5 / 2));
        $x2 = min($np, (int)($tpage + 5 / 2));
        for ($i = $x1; $i <= $x2; $i++) {
            $pagelist[] = $i;
        }
        for ($i = max(1,$np - 5); $i <= $np; $i++) {
            $pagelist[] = $i;
        }
        $pagelist = array_unique($pagelist);
        sort($pagelist);
        $pagelist = array_combine($pagelist, $pagelist);
    }
}

$results = $query->GetMatches();
if ($results) {
    $format = trim(UserParams::get_for_user($userid, 'datetime_format'));
    if (!$format) $format = trim(AppParams::get('datetime_format'));
    if (!$format) $format = 'Y-m-d H:i';
    foreach ($results as &$one) {
        $one['when'] = date($format, $one['timestamp']);
        unset($one['timestamp']);
    }
    unset($one);

    $query->Close();
}

$selfurl = basename(__FILE__);
$pageurl = $selfurl.get_secure_param().'&page=xxx';
$prompt = json_encode(_la('sysmain_confirmclearlog'));

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
  $('#pagenum').on('change', function() {
    var v = $(this).val();
    var t_url = '$pageurl'.replace('xxx', v);
    window.location = t_url;
  });
  $('#clearlink').on('click', function(ev) {
    ev.preventDefault();
    cms_confirm_linkclick(this,$prompt);
    return false;
  });
  $('#filterlink').on('click', function() {
    cms_dialog($('#filter_dlg'), {
      open: function(ev, ui) {
        cms_equalWidth($('#filter_dlg label.boxchild'));
      },
      modal: true,
      width: 'auto',
      height: 'auto'
    });
  });
});
//]]>
</script>
EOS;
add_page_foottext($js);

$severity_list = [
    _la('message'),
    _la('notice'),
    _la('warning'),
    _la('error'),
];

//$t = 'filter-title';
//$icon = $themeObject->DisplayImage('icons/extra/filter', $t, '', '', 'systemicon');
$urlext = get_secure_param();
$extras = get_secure_param_array();

$smarty = SingleItem::Smarty();

$smarty->assign([
    'selfurl' => $selfurl,
    'urlext' => $urlext,
    'extras' => $extras,
    'pclear' => $pclear,
    'psee' => $psee,
    'results' => $results,
    'pagelist' => $pagelist,
    'severity_list' => $severity_list,
    'page' => $page,
    'filter' => $filter,
//    'filterimage' => $icon,
    'filter_applied' => $filter_applied,
]);

$content = $smarty->fetch('adminlog.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
