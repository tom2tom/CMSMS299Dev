<?php
/*
CMSMS News module edit-item action.
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AdminUtils;
use CMSMS\Events;
use CMSMS\FormUtils;
use CMSMS\RouteOperations;
use CMSMS\SingleItem;
use CMSMS\TemplateType;
use CMSMS\Utils;
use News\AdminOperations;
use function CMSMS\de_specialize_array;
use function CMSMS\specialize;
use function CMSMS\specialize_array;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify News')) exit;
if (isset($params['cancel'])) {
    $this->Redirect($id, 'defaultadmin', $returnid);
}

// TODO icon/image handling

$me = $this->GetName();
$cz = $config['timezone'];
$dt = new DateTime(null, new DateTimeZone($cz));
$toffs = $dt->getOffset();
$useexp = $params['inputexp'] ?? 1;

if (isset($params['submit']) || isset($params['apply'])) {

    de_specialize_array($params);
    $articleid    = $params['articleid'];
    $title        = $params['title'];
    $summary      = $params['summary'];
    $content      = $params['content'];
    $status       = $params['status'];
    $searchable   = $params['searchable'] ?? 0;
    $news_url     = $params['news_url'];
    $usedcategory = $params['category'];
    $author_id    = $params['author_id'] ?? 0;
    $extra        = trim($params['extra']);

    $error = false;

    $st = strtotime($params['fromdate']);
    if ($st !== false) {
        if (!empty($params['fromtime'])) {
            $stt = strtotime($params['fromtime'], 0);
            if ($stt !== false) {
                $st += $stt + $toffs;
            }
        }
        $startdate = $st;
    } elseif ($params['fromdate'] === '') {
        $startdate = 0;
    } else {
        $this->ShowErrors($this->Lang('error_invaliddates'));
        $error = true;
        $startdate = 0;
    }

    if ($useexp == 0) {
        $enddate = 0;
    } else {
        $st = strtotime($params['todate']);
        if ($st !== false) {
            if (!empty($params['totime'])) {
                $stt = strtotime($params['totime'], 0);
                if ($stt !== false) {
                    $st += $stt + $toffs;
                }
            }
            $enddate = $st;
        } elseif ($params['todate'] === '') {
            $useexp = 0;
            $enddate = 0;
        } else {
            if (!$error) {
                $this->ShowErrors($this->Lang('error_invaliddates'));
                $error = true;
            }
            $enddate = 0;
        }
    }

    // Validation
    if ($startdate && $enddate && $enddate <= $startdate) {
        $this->ShowErrors($this->Lang('error_invaliddates'));
        $error = true;
    }

    if (!$title) {
        $this->ShowErrors($this->Lang('notitlegiven'));
        $error = true;
    } elseif (empty($content)) {
        $this->ShowErrors($this->Lang('nocontentgiven'));
        $error = true;
    }

    if ($news_url) {
        // check for starting or ending slashes
        if (startswith($news_url, '/') || endswith($news_url, '/')) {
            $this->ShowErrors($this->Lang('error_invalidurl'));
            $error = true;
        }

        // check for invalid chars
        $translated = munge_string_to_url($news_url, false, true);
        if (strtolower($translated) != strtolower($news_url)) {
            $this->ShowErrors($this->Lang('error_invalidurl'));
            $error = true;
        }

        // check this URL isn't a duplicate
        RouteOperations::load_routes();
        $route = RouteOperations::find_match($news_url, true);
        if ($route) {
            $dest = $route->get_dest();
            $dflts = $route->get_defaults();
            if ($dest != $me || !isset($dflts['articleid']) || $dflts['articleid'] != $articleid) {
                $this->ShowErrors($this->Lang('error_invalidurl'));
                $error = true;
            }
        }
    }

    if (!$error) {
        //
        // database work
        //
        $query = 'UPDATE ' . CMS_DB_PREFIX . 'module_news SET
news_title=?,
news_data=?,
summary=?,
news_category_id=?,
status=?,
searchable=?,
start_time=?,
end_time=?,
modified=?,
news_extra=?,
news_url= ?
WHERE news_id=?';
        $now = time();
        $stsave = ($status == 'final' && $startdate > 0 && $startdate < $now && ($enddate == 0 || $enddate > $now)) ? 'published' : $status;
        $args = [
         $title,
         $content,
         $summary,
         $usedcategory,
         $stsave,
         $searchable,
         $startdate,
         (($useexp == 1)?$enddate:0),
         $now,
         $extra,
         $news_url,
         $articleid
        ];
        $db->execute($query, $args);

        if (!$error) {
            if (($status == 'published' || $status =='final') && $news_url != '') {
                AdminOperations::delete_static_route($articleid);
                AdminOperations::register_static_route($news_url, $articleid);
            }

            //Update search index
            $module = Utils::get_search_module();
            if (is_object($module)) {
                if ($status == 'draft' || $status == 'archived' || !$searchable) {
                    $module->DeleteWords($me, $articleid, 'article');
                } else {
                    if (!$useexp || ($enddate > time()) || $this->GetPreference('expired_searchable', 1) == 1) {
                        $text = '';
                    } else {
                        $text = ''; //CHECKME TODO
                    }
                    $text .= $content . ' ' . $summary . ' ' . $title . ' ' . $title;
                    $module->AddWords($me, $articleid, 'article', $text, ($useexp == 1 && $this->GetPreference('expired_searchable', 0) == 0) ? $enddate : null);
                }
            }

            Events::SendEvent('News', 'NewsArticleEdited', [
                'news_id' => $articleid,
                'category_id' => $usedcategory,
                'title' => $title,
                'content' => $content,
                'summary' => $summary,
                'status' => $status,
                'post_time' => $startdate, //deprecated
                'start_time' => $startdate,
                'end_time' => $enddate,
                'extra' => $extra,
                'useexp' => $useexp,
                'news_url' => $news_url
            ]);
            // put mention into the admin log
            audit($articleid, 'News: ' . $title, 'Article edited');
        } // !error

        if (isset($params['apply']) && isset($params['ajax'])) {
            // TODO sensible ajax status-reporting - error and success
            $response = '<EditArticle>';
            if ($error) {
                $response .= '<Response>Error</Response>';
                $response .= '<Details><![CDATA[' . $error . ']]></Details>';
            } else {
                $response .= '<Response>Success</Response>';
                $response .= '<Details><![CDATA[' . $this->Lang('articleupdated') . ']]></Details>';
            }
            $response .= '</EditArticle>';
            echo $response;
            exit;
        }

        if (!($error || isset($params['apply']))) {
            // redirect out of here
            $this->SetMessage($this->Lang('articlesubmitted'));
            $this->Redirect($id, 'defaultadmin', $returnid);
        }
    }

    $query = 'SELECT start_time,end_time,create_date,modified_date FROM ' . CMS_DB_PREFIX . 'module_news WHERE news_id=?';
    $row = $db->getRow($query, [$articleid]);
} elseif (!isset($params['preview'])) {
    //
    // Load data from database
    //
    $query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news WHERE news_id=?';
    $row = $db->getRow($query, [$params['articleid']]);

    if ($row) {
        $articleid    = $row['news_id'];
        $author_id    = $row['author_id'];
        $content      = $row['news_data'];
        $enddate      = $row['end_time'];
        $extra        = $row['news_extra'];
        $news_url     = $row['news_url'];
        $searchable   = $row['searchable'];
        $startdate    = $row['start_time'];
        $status       = $row['status'];
        if ($status == 'published') $status = 'final';
        $summary      = $row['summary'];
        $title        = $row['news_title'];
        $usedcategory = $row['news_category_id'];
    } else {
        //TODO handle error
        $error = true;
    }
} else {
    // save data for preview
    unset($params['apply'],
        $params['preview'],
        $params['submit'],
        $params['cancel'],
        $params['ajax']);

    $tmpfname = tempnam(TMP_CACHE_LOCATION, $me . '_preview');
    file_put_contents($tmpfname, serialize($params));

    $detail_returnid = $this->GetPreference('detail_returnid', -1);
    if ($detail_returnid <= 0) {
        // now get the default content id.
        $detail_returnid = SingleItem::ContentOperations()->GetDefaultContent();
    }
    if (isset($params['previewpage']) && (int)$params['previewpage'] > 0)
        $detail_returnid = (int)$params['previewpage'];

    $_SESSION['news_preview'] = [
        'fname' => basename($tmpfname),
        'checksum' => md5_file($tmpfname)
    ];
    $tparms = ['preview' => md5(serialize($_SESSION['news_preview']))];
    if (isset($params['detailtemplate']))
        $tparms['detailtemplate'] = trim($params['detailtemplate']);
    $url = $this->create_url('_preview_', 'detail', $detail_returnid, $tparms, true, false, '', false, 2);

    $response = '<?xml version="1.0"?>';
    $response .= '<EditArticle>';
    if (!empty($error)) {
        $response .= '<Response>Error</Response>';
        $response .= '<Details><![CDATA[' . $error . ']]></Details>';
    } else {
        $response .= '<Response>Success</Response>';
        $response .= '<Details><![CDATA[' . $url . ']]></Details>';
    }
    $response .= '</EditArticle>';

    $handlers = ob_list_handlers();
    for ($cnt = 0, $n = count($handlers); $cnt <$n; ++$cnt) {
        ob_end_clean();
    }
    header('Content-Type: text/xml');
    echo $response;
    exit;
}

$fmt = $this->GetDateFormat();
$created = $this->FormatforDisplay($row['create_date']);
if ($row['modified_date'] && strtotime($row['modified_date']) > strtotime($row['create_date'])) { // don't care about timezones
    $modified = $this->FormatforDisplay($row['modified_date']);
} else {
    $modified = null;
}
if ($status == 'final') {
    if ($row['start_time']) {
        $published = $this->FormatforDisplay($row['start_time']);
    } else {
        $published = '?';
    }
} else {
    $published = null;
}
if ($status == 'archived') {
    if ($row['end_time']) {
        $archived = $this->FormatforDisplay($row['end_time']);
    } else {
        $archived = '?';
    }
} else {
    $archived = null;
}

$block = $this->GetPreference('timeblock', News::HOURBLOCK);
switch ($block) {
    case News::DAYBLOCK:
        $rounder = 3600*24;
        break;
    case News::HALFDAYBLOCK:
        $rounder = 3600*12;
        break;
    default:
        $rounder = 3600;
        break;
}
$withtime = ($block == News::DAYBLOCK) ? 0:1;

if (!empty($startdate)) { //Y-m-D H:i:s formatted string
    $dtst = strtotime($startdate);
    $st = strtotime('midnight', $dtst); // redundant?
    $fromdate = date('Y-m-j', $st);
    if ($withtime) {
        $stt = $dtst - $st - $toffs;
        $stt = (int)($stt / $rounder) * $rounder;
        $fromtime = date('g:ia', $stt);
    } else {
        $fromtime = null;
    }
} else {
    $fromdate = '';
    $fromtime = null;
}

if (!empty($enddate)) { //also a Y-m-D H:i:s formatted string
    $dtst = strtotime($enddate);
    $st = strtotime('midnight', $dtst);
    $todate = date('Y-m-j', $st);
    if ($withtime) {
        $stt = $dtst - $st - $toffs;
        $stt = (int)($stt / $rounder) * $rounder;
        $totime = date('g:ia', $stt);
    } else {
        $totime = null;
    }
} else {
    $todate = '';
    $totime = null;
}

$categorylist = [];
$query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news_categories ORDER BY hierarchy';
$rst = $db->execute($query);
if ($rst) {
    while (($row = $rst->FetchRow())) {
        $categorylist[$row['news_category_id']] = specialize($row['long_name']);
    }
    $rst->Close();
    specialize_array($categorylist);
}
$parms = $params + ['articleid'=>$articleid, 'author_id'=>$author_id];
unset($parms['action']); // ??
specialize_array($parms);

/*----------------------
 Pass everything to smarty
-----------------------*/

$tpl = $smarty->createTemplate($this->GetTemplateResource('editarticle.tpl')); //, null, null, $smarty);

$tpl->assign('formaction', 'editarticle')
    ->assign('formparms', $parms);

if ($author_id > 0) {
    $userops = SingleItem::UserOperations();
    $theuser = $userops->LoadUserById($author_id);
    if ($theuser) {
        $tpl->assign('inputauthor', $theuser->username);
    } else {
        $tpl->assign('inputauthor', $this->Lang('anonymous'));
    }
} else {
    $tpl->assign('inputauthor', $this->Lang('anonymous'));
}

if ($this->GetPreference('allow_summary_wysiwyg', 1)) {
    $tpl->assign('hide_summary_field', false)
     ->assign('inputsummary', FormUtils::create_textarea([
        'enablewysiwyg' => 1,
        'getid' => $id,
        'name' => 'summary',
        'class' => 'pageextrasmalltextarea',
        'value' => $summary,
        'addtext' => 'style="height:3em;"',
    ]));
} else {
     $tpl->assign('hide_summary_field', true);
}
$tpl->assign('inputcontent', FormUtils::create_textarea([
    'enablewysiwyg' => 1,
    'getid' => $id,
    'name' => 'content',
    'value' => $content,
]));

$tpl->assign([
 'archat' => $archived,
 'articleid' => $articleid,
 'category' => $usedcategory,
 'categorylist' => $categorylist,
 'createat' => $created,
 'extra' => $extra,
 'fromdate' => $fromdate,
 'fromtime' => $fromtime,
 'modat' => $modified,
 'news_url' => $news_url,
 'pubat' => $published,
 'searchable' => $searchable,
 'status' => $status,
 'title' => $title,
 'todate' => $todate,
 'totime' => $totime,
 'withtime' => $withtime,
// 'inputexp' => $this->CreateInputCheckbox($id, 'useexp', '1', $useexp, 'class="pagecheckbox"'),
// 'useexp' => $useexp,
]);

if ($this->CheckPermission('Approve News')) {
    $choices = [
        $this->Lang('draft')=>'draft',
        $this->Lang('final')=>'final',
        $this->Lang('archived')=>'archived',
    ];
//    $statusradio = $this->CreateInputRadioGroup($id, 'status', $choices, $status, '', '  ');
    $statusradio = FormUtils::create_select([ // DEBUG
        'type' => 'radio',
        'name'  => 'status',
        'htmlid' => 'status',
        'getid' => $id,
        'options'=> $choices,
        'selectedvalue' => $status,
        'delimiter' => '  ',
    ]);
    $tpl->assign('statuses', $statusradio);
//   ->assign('statustext', lang('status'));
}

// get the detail templates, if any
try {
    $type = TemplateType::load($me . '::detail');
    $templates = $type->get_template_list();
    $list = [];
    if ($templates) {
        foreach ($templates as $template) {
            $list[$template->get_id()] = $template->get_name();
        }
    }
    if ($list) {
        $str = AdminUtils::CreateHierarchyDropdown(0, (int)$this->GetPreference('detail_returnid', -1), 'preview_returnid');
        $tpl->assign('detail_templates', $list)
         ->assign('cur_detail_template', $this->GetPreference('current_detail_template'))
         ->assign('preview', true)
         ->assign('preview_returnid', $str);
    }
} catch( Throwable $t ) {
    cms_error('', $me.'::editarticle', 'No detail template available for preview');
    $this->ShowErrors($t->GetMessage());
}

// page resources
include __DIR__.DIRECTORY_SEPARATOR.'method.articlescript.php';

$tpl->display();
