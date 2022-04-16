<?php
/*
CMSMS News module edit-item action.
Copyright (C) 2005-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use News\Utils;
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

//TODO allow current owner or any super-group member to change item owner

$me = $this->GetName();
//$cz = $config['timezone'];
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

    if (isset($params['generate_url']) && cms_to_bool($params['generate_url'])) {
        if ($title) {
            $str = $title;
        } elseif ($summary) {
            $str = $summary;
        } else {
            $str = 'newsitem'.$articleid.mt_rand(1000, 9999);
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
        $now = time();
        if ($pmod) {
            $stsave = ($status == 'final' && $fst > 0 && $fst < $now && ($tst == 0 || $tst > $now)) ? 'published' : $status;
        } else { // $pprop
            $stsave = $status;
        }
        $longnow = $db->DbTimeStamp($now, false);
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
            //Update search index
            $search = AppUtils::get_search_module();
            if (is_object($search)) {
                if ($status == 'draft' || $status == 'archived' || !$searchable) {
                    $search->DeleteWords($me, $articleid, 'article');
                } else {
                    if ($tst == 0 || $tst > $now || $this->GetPreference('expired_searchable', 1) == 1) {
                        $text = '';
                    } else {
                        $text = ''; //CHECKME TODO
                    }
                    $text .= $content . ' ' . $summary . ' ' . $title . ' ' . $title;
//                  $search->AddWords($me, $articleid, 'article', $text, ($useexp == 1 && $this->GetPreference('expired_searchable', 0) == 0) ? $longend : null);
                    $search->AddWords($me, $articleid, 'article', $text, ($longend && $this->GetPreference('expired_searchable', 0) == 0) ? $longend : null);
                }
            }

            if ($pmod) {
                Events::SendEvent($me, 'NewsArticleEdited', [
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
            log_info($articleid, $me.': ' . $title, 'Article edited');
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
        $extra        = $row['news_extra'];
        $image_url    = $row['image_url'];
        $longend      = $row['end_time'];
        $longstart    = $row['start_time'];
        $news_url     = $row['news_url'];
        $searchable   = $row['searchable'];
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
        $rounder = 86400; //3600*24
        break;
    case News::HALFDAYBLOCK:
        $rounder = 43200; //3600*12
        break;
    default:
        $rounder = 3600;
        break;
}
$withtime = ($block !== News::DAYBLOCK);

if (!empty($longstart)) { //Y-m-d H:i:s formatted string
    $dtst = strtotime($longstart);
    $st = strtotime('midnight', $dtst); // redundant?
    $fromdate = date('Y-n-j', $st); // OR gmdate() ?
    if ($withtime) {
        $stt = $dtst - $st;
        $stt = (int)($stt / $rounder) * $rounder;
        $fromtime = gmdate('g:ia', $stt); // NOTE UTC-zone-relative
    } else {
        $fromtime = null;
    }
} else {
    $fromdate = '';
    $fromtime = null;
}

if (!empty($longend)) { //also a Y-m-d H:i:s formatted string
    $dtst = strtotime($longend);
    $st = strtotime('midnight', $dtst);
    $todate = date('Y-n-j', $st);
    if ($withtime) {
        $stt = $dtst - $st;
        $stt = (int)($stt / $rounder) * $rounder;
        $totime = gmdate('g:ia', $stt);
    } else {
        $totime = null;
    }
} else {
    $todate = '';
    $totime = null;
}

specialize_array($categorylist);
$parms = $params + ['articleid'=>$articleid, 'author_id'=>$author_id];
unset($parms['action']); // ??
specialize_array($parms);

/*-----------------------
 Pass everything to smarty
------------------------*/

$tpl = $smarty->createTemplate($this->GetTemplateResource('editarticle.tpl')); //, null, null, $smarty);

$tpl->assign('formaction', 'editarticle')
  ->assign('formparms', $parms);

// TODO allow author change if current user is owner, or a member of supergroup 1
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

$picker = SingleItem::ModuleOperations()->GetFilePickerModule();
$dir = $config['uploads_path'];
$userid = get_userid(false);
$tmp = $picker->get_default_profile($dir, $userid);
$profile = $tmp->overrideWith(['top'=>$dir, 'type'=>FileType::IMAGE]);
$text = $picker->get_html($id.'image_url', $image_url, $profile);
$tpl->assign('filepicker', $text);

// get the detail templates, if any, for use in a preview
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
} catch (Throwable $t) {
    log_error('No detail-template available for preview', $me.'::editarticle');
    $this->ShowErrors($t->GetMessage());
}

// page resources
require_once __DIR__.DIRECTORY_SEPARATOR.'method.articlescript.php';

$tpl->display();
