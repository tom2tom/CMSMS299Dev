<?php
/*
Add item action for CMSMS News module.
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
use CMSMS\AppSingle;
use CMSMS\Events;
use CMSMS\FormUtils;
use CMSMS\RouteOperations;
use CMSMS\TemplateType;
use CMSMS\Url;
use CMSMS\Utils;
use News\AdminOperations;
use function CMSMS\de_specialize_array;
use function CMSMS\specialize;
//use function CMSMS\de_specialize;
//use function CMSMS\sanitizeVal;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify News')) exit;
if (isset($params['cancel'])) { $this->Redirect($id, 'defaultadmin', $returnid); }

$cz = $config['timezone'];
$tz = new DateTimeZone($cz);
$dt = new DateTime(null, $tz);
$toffs = $tz->getOffset($dt);

$useexp = $params['inputexp'] ?? 1;

if (isset($params['submit']) || isset($params['apply'])) {
    de_specialize_array($params); // TODO sanitizeVal() some of these
    $articleid    = $params['articleid']; //-1 before save, >0 after 'apply'
    $author_id    = $params['author_id'] ?? 0;
    $content      = $params['content'];
    $extra        = trim($params['extra']);
    $news_url     = $params['news_url'];
    $searchable   = $params['searchable'] ?? 0;
    $status       = $params['status'];
    $summary      = $params['summary'];
    $title        = $params['title'];
    $usedcategory = $params['category'];

    $error = false;

    $st = strtotime($params['fromdate']);
    if ($st !== false) {
        if (isset($params['fromtime'])) {
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
            if (isset($params['totime'])) {
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

    if (empty($title)) {
        $this->ShowErrors($this->Lang('notitlegiven'));
        $error = true;
    } elseif (empty($content)) {
        $this->ShowErrors($this->Lang('nocontentgiven'));
        $error = true;
    }

    if ($news_url) {
        // check for starting or ending slashes
        if (($news_url[0] == '/') || substr_compare($news_url, '/', -1, 1) === 0) {
            $this->ShowErrors($this->Lang('error_invalidurl'));
            $error = true;
        }

        // check for invalid chars
        $tmp = (new Url())->sanitize($news_url);
        if ($tmp != $news_url) {
            $this->ShowErrors($this->Lang('error_invalidurl'));
            $error = true;
        }

        // check this url isn't a duplicate.
        // we're adding an article, not editing... any matching route is bad
        RouteOperations::load_routes(); // populate module's intra-request routes
        $route = RouteOperations::find_match($news_url);
        if ($route) {
            $this->ShowErrors($this->Lang('error_invalidurl'));
            $error = true;
        }
    }

    if (!$error) {
        //
        // database work
        //
        if ($articleid < 0) {
            $query = 'INSERT INTO ' . CMS_DB_PREFIX . 'module_news (
news_id,
news_title,
news_data,
summary,
news_category_id,
status,
searchable,
start_time,
end_time,
create_date,
author_id,
news_extra,
news_url) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $articleid = $db->genID(CMS_DB_PREFIX . 'module_news_seq');
            $now = time();
            $stsave = ($status == 'final' && $startdate > 0 && $startdate < $now && ($enddate == 0 || $enddate > $now)) ? 'published' : $status;
            $args = [
             $articleid,
             $title,
             $content,
             $summary,
             $usedcategory,
             $stsave,
             $searchable,
             $startdate,
             (($useexp == 1)?$enddate:0),
             $now,
             $author_id,
             $extra,
             $news_url,
            ];
            $dbr = $db->Execute($query, $args);
            if (!$dbr) {
                echo 'DEBUG: SQL = ' . $db->sql . '<br />';
                die($db->ErrorMsg());
            }

            if (!$error) {
                if (($status == 'publishedfinal' || $status == 'final') && $news_url) {
                    // TODO: && not expired
                    // register the route
                    AdminOperations::delete_static_route($articleid);
                    AdminOperations::register_static_route($news_url, $articleid);
                }

                if (($status == 'published' || $status == 'final') && $searchable) {
                    //Update search index
                    $module = Utils::get_search_module();
                    if (is_object($module)) {
                        $text = $content . ' ' . $summary . ' ' . $title . ' ' . $title;
                        $module->AddWords($this->GetName(), $articleid, 'article', $text, ($useexp == 1 && $this->GetPreference('expired_searchable', 0) == 0) ? $enddate : NULL);
                    }
                }

                Events::SendEvent('News', 'NewsArticleAdded', [
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
                audit($articleid, 'News: ' . $title, 'Article added');
                $this->SetMessage($this->Lang('articleadded'));
                if (!isset($params['apply'])) {
                    $this->Redirect($id, 'defaultadmin', $returnid);
                }
            } // !$error
        } else { // articleid >= 0 after apply
            $query = 'UPDATE ' . CMS_DB_PREFIX . 'module_news SET
news_title=?,
news_data=?,
summary=?,
news_category_id=?,
status=?,
searchable=?,
start_time=?,
end_time=?,
modified_date=?,
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
            $db->Execute($query, $args);
        }
    } // outer !$error

    $fromdate = $params['fromdate'];
    $fromtime = $params['fromtime'] ?? '';
    $todate = $params['todate'];
    $totime = $params['totime'] ?? '';
// end submit
} elseif (!isset($params['preview'])) {
    $articleid    = -1;
    $title        = '';
    $content      = '';
    $summary      = '';
    $status       = 'draft';
    $searchable   = 1;
    $startdate    = '';
    $enddate      = '';
    $usedcategory = '';
    $author_id    = get_userid(false);
    $extra        = '';
    $news_url     = '';

    $fromdate = '';
    $fromtime = '';
    $todate = '';
    $totime = '';
} else {
    // save data for preview.
    unset($params['apply']);
    unset($params['preview']);
    unset($params['submit']);
    unset($params['cancel']);
    unset($params['ajax']);

    $tmpfname = tempnam(PUBLIC_CACHE_LOCATION, $this->GetName() . '_preview');
    file_put_contents($tmpfname, serialize($params));

    $detail_returnid = $this->GetPreference('detail_returnid', -1);
    if ($detail_returnid <= 0) {
        // now get the default content id.
        $detail_returnid = AppSingle::ContentOperations()->GetDefaultContent();
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
    $url = $this->create_url('_preview_', 'detail', $detail_returnid, $tparms, true);

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
    for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) {
        ob_end_clean();
    }
    header('Content-Type: text/xml');
    echo $response;
    exit;
}

$block = $this->GetPreference('timeblock', News::HOURBLOCK);
$withtime = ($block == News::DAYBLOCK) ? 0:1;

$categorylist = [];
$query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news_categories ORDER BY hierarchy';
$rst = $db->Execute($query);
if ($rst) {
    while (($row = $rst->FetchRow())) {
        $categorylist[$row['news_category_id']] = specialize($row['long_name']);
    }
    $rst->Close();
}

/*--------------------
 Pass everything to smarty
 ---------------------*/

$parms = array_merge($params, ['articleid'=>$articleid, 'author_id'=>$author_id]);
unset($parms['action']);

$tpl = $smarty->createTemplate($this->GetTemplateResource('editarticle.tpl'),null,null,$smarty);

$tpl->assign('formaction','addarticle')
    ->assign('formparms', $parms);

if ($author_id > 0) {
    $theuser = AppSingle::UserOperations()->LoadUserById($author_id);
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
        'modid' => $id,
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
    'modid' => $id,
    'name' => 'content',
    'value' => $content,
]));

$tpl->assign([
 'articleid' => $articleid,
 'category' => $usedcategory,
 'categorylist' => $categorylist,
 'extra' => $extra,
 'fromdate' => $fromdate,
 'fromtime' => $fromtime,
 'news_url' => $news_url,
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
    ];
//    $statusradio = $this->CreateInputRadioGroup($id,'status',$choices,$status,'','  ');
    $statusradio = FormUtils::create_select([ // DEBUG
        'type' => 'radio',
        'name' => 'status',
        'htmlid' => 'status',
        'modid' => $id,
        'options' => $choices,
        'selectedvalue' => $status,
        'delimiter' => '  ',
    ]);
    $tpl->assign('statuses',$statusradio);
//   ->assign('statustext', lang('status'));
}

// get the detail templates, if any
try {
    $type = TemplateType::load($this->GetName() . '::detail');
    $templates = $type->get_template_list();
    $list = [];
    if ($templates) {
        foreach ($templates as $template) {
            $list[$template->get_id()] = $template->get_name();
        }
    }
    if ($list) {
        $str = AdminUtils::CreateHierarchyDropdown(0, (int)$this->GetPreference('detail_returnid',-1), 'preview_returnid');
        $tpl->assign('detail_templates', $list)
         ->assign('cur_detail_template', $this->GetPreference('current_detail_template'))
         ->assign('preview', true)
         ->assign('preview_returnid', $str);
    }
} catch( Exception $e ) {
    audit('', $this->GetName(), 'No detail templates available for preview');
}

// page resources
include __DIR__.DIRECTORY_SEPARATOR.'method.articlescript.php';

$tpl->display();
return '';
