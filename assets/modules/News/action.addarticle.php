<?php
/*
CMSMS News module add-item action.
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AdminUtils;
use CMSMS\Events;
use CMSMS\FileType;
use CMSMS\FormUtils;
use CMSMS\RouteOperations;
use CMSMS\SingleItem;
use CMSMS\TemplateType;
use CMSMS\Url;
use CMSMS\Utils as AppUtils;
use News\AdminOperations;
use function CMSMS\de_specialize_array;
use function CMSMS\log_error;
use function CMSMS\log_info;
use function CMSMS\specialize_array;
//use function CMSMS\sanitizeVal;

//if (some worthy test fails) exit;

$pmod = $this->CheckPermission('Modify News');
$pprop = $this->CheckPermission('Propose News'); // pre-publication approval needed
if (!($pmod || $pprop)) exit;
if (isset($params['cancel'])) {
    $this->Redirect($id, 'defaultadmin', $returnid);
}

$me = $this->GetName();
$dt = new DateTime(null, new DateTimeZone('UTC'));

$query = 'SELECT news_category_id,long_name FROM ' . CMS_DB_PREFIX . 'module_news_categories ORDER BY hierarchy';
$categorylist = $db->getAssoc($query);

if (isset($params['submit']) || isset($params['apply'])) {
    de_specialize_array($params); // TODO sanitizeVal() some of these
    $articleid    = (int)$params['articleid']; //-1 before save, >0 after 'apply'
    $author_id    = $params['author_id'] ?? 0;
    $content      = $params['content'];
    $extra        = (!empty($params['extra'])) ? trim($params['extra']) : null;
    $image_url    = $params['image_url'];
    $news_url     = (!empty($params['news_url'])) ? trim($params['news_url']) : null;
    $searchable   = $params['searchable'] ?? 0;
    $status       = $params['status'];
    $summary      = (!empty($params['summary'])) ? trim($params['summary']) : null;
    $title        = (!empty($params['title'])) ? trim($params['title']) : null;
    $usedcategory = (int)$params['category'];

    if ($params['fromdate']) {
        $tmp = trim($params['fromdate'].' '.$params['fromtime']);
        $dt->modify($tmp);
        $longstart = $dt->format('Y-m-d H:i:s');
        $fst = (int)$dt->format('U');
    }
    else {
        $longstart = null;
        $fst = 0;
    }

    if ($params['todate']) {
        $tmp = trim($params['todate'].' '.$params['totime']);
        $dt->modify($tmp);
        $longend = $dt->format('Y-m-d H:i:s');
        $tst = (int)$dt->format('U');
    }
    else {
        $longend = null;
        $tst = 0;
    }

    // validation
    $error = false;

    if ($longstart && $longend && $tst <= $fst) {
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

    if (!empty($params['generate_url'])) {
        if ($title) {
            $str = $title;
        } elseif ($summary) {
            $str = $summary;
        } else {
            $str = 'newsitem'.mt_rand(10000, 99999);
        }
        $news_url = Utils::condense($str, true);
    }

    if ($news_url) {
        // remove any adjacent slash
        if ($news_url[0] == '/' || substr_compare($news_url, '/', -1, 1) == 0) {
            $news_url = trim($news_url, '/ ');
        }
        //TODO etc other cleanup

        // check for invalid chars
        $tmp = (new Url())->sanitize($news_url);
        if ($tmp != $news_url) {
            $this->ShowErrors($this->Lang('error_invalidurl'));
            $error = true;
        }

        // check this URL isn't a duplicate
        RouteOperations::load_routes();
        $route = RouteOperations::find_match($news_url);
        if ($route) {
            $dest = $route->get_dest();
            $dflts = $route->get_defaults();
            if ($dest != $me || !isset($dflts['articleid']) || $dflts['articleid'] != $articleid) {
                $this->ShowErrors($this->Lang('error_invalidurl'));
                $error = true;
            }
        }
    }

    if ($image_url) {
        // TODO validate, cleanup this
    } else {
        $image_url = null;
    }

    if (!$error) {
        //
        // database work
        //
        $now = time();
        $longnow = $db->DbTimeStamp($now, false);
        if ($articleid < 0) {
            $query = 'INSERT INTO ' . CMS_DB_PREFIX . 'module_news
(news_id,
news_category_id,
news_title,
status,
news_data,
news_extra,
summary,
news_url,
image_url,
start_time,
end_time,
create_date,
author_id,
searchable) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $articleid = $db->genID(CMS_DB_PREFIX . 'module_news_seq');
            if ($pmod) {
                $stsave = ($status == 'final' && $startdate > 0 && $startdate < $now && ($enddate == 0 || $enddate > $now)) ? 'published' : $status;
            } else { // $pprop
                $stsave = $status;
            }
            $args = [
             $articleid,
             $usedcategory,
             $title,
             $stsave,
             $content,
             $extra,
             $summary,
             $news_url,
             $image_url,
             $longstart,
             $longend,
             $longnow,
             $author_id,
             $searchable,
            ];
            $dbr = $db->execute($query, $args);
            if (!$dbr) {
                // TODO handle error
                $this->ShowErrors($db->errorMsg());
               $error = true;
            }

            if (!$error) {
                if (($status == 'published' || $status == 'final') && $news_url) {
                    // TODO: && not expired
                    // [re]register the route
                    AdminOperations::delete_static_route($articleid);
                    try {
                        AdminOperations::register_static_route($news_url, $articleid);
                    } catch(Throwable $t) {
                        $this->ShowErrors($t->getMessage());
                        $error = true;
                    }
                }
            }

            if (!$error) {
                if (($status == 'published' || $status == 'final') && $searchable) {
                    // update search index
                    $search = AppUtils::get_search_module();
                    if (is_object($search)) {
                        $text = $content . ' ' . $summary . ' ' . $title . ' ' . $title;
//                        $until = ($useexp && $this->GetPreference('expired_searchable', 0) == 0) ? $enddate : NULL;
                        $until = ($enddate && $this->GetPreference('expired_searchable', 0) == 0) ? $enddate : NULL;
                        $search->AddWords($me, $articleid, 'article', $text, $until);
                    }
                }

                if ($pmod) {
                    Events::SendEvent($me, 'NewsArticleAdded', [
                     'news_id' => $articleid,
                     'category_id' => $usedcategory,
                     'title' => $title,
                     'summary' => $summary,
                     'content' => $content,
                     'status' => $status,
                     'news_url' => $news_url,
                     'post_time' => $longstart, //deprecated
                     'start_time' => $longstart,
                     'end_time' => $longend,
                     'extra' => $extra,
                    ]);
                } elseif ($status == 'final') { // $pprop
                    require_once __DIR__.DIRECTORY_SEPARATOR.'function.requestapproval.php'; // send notices
                }
                // put mention into the admin log
                log_info($articleid,  $me.': ' . $title, 'Article added');
                $this->SetMessage($this->Lang('articleadded'));
                if (!isset($params['apply'])) {
                    $this->Redirect($id, 'defaultadmin', $returnid);
                }
            } // !$error
        } else { // articleid >= 0 after apply
            $query = 'UPDATE ' . CMS_DB_PREFIX . 'module_news SET
news_category_id=?,
news_title=?,
status=?,
news_data=?,
news_extra=?,
summary=?,
news_url=?,
image_url=?,
start_time=?,
end_time=?,
modified_date=?,
searchable=?
WHERE news_id=?';
            $stsave = ($status == 'final' && $startdate > 0 && $startdate < $now && ($enddate == 0 || $enddate > $now)) ? 'published' : $status;
            $args = [
             $usedcategory,
             $title,
             $stsave,
             $content,
             $extra,
             $summary,
             $news_url,
             $image_url,
             $longstart,
             $longend,
             $longnow,
             $searchable,
             $articleid
            ];
            $db->execute($query, $args);
            if ($db->errorNo() > 0) {
                // TODO handle error
                $this->ShowErrors($db->errorMsg());
                $error = true;
            }
        }
    } // outer !$error

    $fromdate = $params['fromdate'];
    $fromtime = $params['fromtime'] ?? '';
    $todate = $params['todate'];
    $totime = $params['totime'] ?? '';
// end submit
} elseif (!isset($params['preview'])) {
    // new item
    $articleid    = -1;
    $author_id    = get_userid(false);
    $content      = '';
    $enddate      = '';
    $extra        = '';
    $image_url    = '';
    $news_url     = '';
    $searchable   = 1;
    $startdate    = '';
    $status       = 'draft';
    $summary      = '';
    $title        = '';
    $usedcategory = '';

    $fromdate = '';
    $fromtime = '';
    $todate = '';
    $totime = '';
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
        // get the default content id
        $detail_returnid = SingleItem::ContentOperations()->GetDefaultContent();
    }
    if (isset($params['previewpage']) && (int)$params['previewpage'] > 0) {
        $detail_returnid = (int)$params['previewpage'];
    }
    $_SESSION['news_preview'] = [
        'fname' => basename($tmpfname),
        'checksum' => md5_file($tmpfname)
    ];
    $tparms = ['preview' => md5(serialize($_SESSION['news_preview']))];
    if (!empty($params['detailtemplate'])) {
        $tparms['detailtemplate'] = trim($params['detailtemplate']);
    }
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
    for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) {
        ob_end_clean();
    }
    header('Content-Type: text/xml');
    echo $response;
    exit;
}

$block = $this->GetPreference('timeblock', News::HOURBLOCK);
$withtime = ($block == News::DAYBLOCK) ? 0:1;

specialize_array($categorylist);
$parms = $params + ['articleid'=>$articleid, 'author_id'=>$author_id];
unset($parms['action']); // ??
specialize_array($parms);

/*-----------------------
 Pass everything to smarty
 ------------------------*/

$tpl = $smarty->createTemplate($this->GetTemplateResource('editarticle.tpl')); //,null,null,$smarty);

$tpl->assign('formaction', 'addarticle')
  ->assign('formparms', $parms);

if ($author_id > 0) {
    $theuser = SingleItem::UserOperations()->LoadUserById($author_id);
    if ($theuser) {
        $tpl->assign('inputauthor', $theuser->username);
    } else {
        $tpl->assign('inputauthor', $this->Lang('anonymous'));
    }
} else {
    $tpl->assign('inputauthor', $this->Lang('anonymous'));
}

if ($this->GetPreference('allow_summary_wysiwyg', 1)) {
    $tpl->assign('inputsummary', FormUtils::create_textarea([
        'enablewysiwyg' => 1,
        'getid' => $id,
        'name' => 'summary',
        'class' => 'pageextrasmalltextarea newssummary',
        'value' => $summary,
    ]));
} else {
    // TODO element size etc
    $tpl->assign('inputsummary', FormUtils::create_input([
        'getid' => $id,
        'name' => 'summary',
        'class' => 'newssummary',
        'value' => $summary,
    ]));
}

$tpl->assign('inputcontent', FormUtils::create_textarea([
    'enablewysiwyg' => 1,
    'getid' => $id,
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
]);

// related image, if any
if ($image_url) {
    $tpl->assign('image_url', CMS_UPLOADS_URL.'/'.trim($image_url, ' /'));
}
else {
   $tpl->assign('image_url', $image_url);
}

if ($this->CheckPermission('Approve News')) {
    $choices = [
        $this->Lang('draft') => 'draft',
        $this->Lang('final') => 'final',
    ];
//  $statusradio = $this->CreateInputRadioGroup($id,'status',$choices,$status,'','  ');
    $statusradio = FormUtils::create_select([ // DEBUG
        'type' => 'radio',
        'name' => 'status',
        'htmlid' => 'status',
        'getid' => $id,
        'options' => $choices,
        'selectedvalue' => $status,
        'delimiter' => '  ',
    ]);
    $tpl->assign('statuses',$statusradio);
//   ->assign('statustext', lang('status'));
}

$picker = SingleItem::ModuleOperations()->GetFilePickerModule();
$dir = $config['uploads_path'];
$userid = get_userid(false);
$tmp = $picker->get_default_profile($dir, $userid);
$profile = $tmp->overrideWith(['top'=>$dir, 'type'=>FileType::IMAGE]);
$text = $picker->get_html($id.'image_url', $image_url, $profile);
$tpl->assign('filepicker', $text);

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
        $str = AdminUtils::CreateHierarchyDropdown(0, (int)$this->GetPreference('detail_returnid',-1), 'preview_returnid');
        $tpl->assign('detail_templates', $list)
         ->assign('cur_detail_template', $this->GetPreference('current_detail_template'))
         ->assign('preview', true)
         ->assign('preview_returnid', $str);
    }
} catch (Throwable $t) {
    log_error('No detail-template available for preview', $me.'::addarticle');
    $this->ShowErrors($t->GetMessage());
}

// page resources
require_once __DIR__.DIRECTORY_SEPARATOR.'method.articlescript.php';

$tpl->display();
