<?php

use CMSContentManager\Utils;
use CMSMS\ContentOperations;
use CMSMS\Events;
use CMSMS\FormUtils;
use News\Adminops;

if (!isset($gCms))  exit ;
if (!$this->CheckPermission('Modify News'))  return;
if (isset($params['cancel'])) $this->Redirect($id, 'defaultadmin', $returnid);

$cz = $config['timezone'];
$tz = new DateTimeZone($cz);
$dt = new DateTime(null, $tz);
$toffs = $tz->getOffset($dt);

$useexp = $params['inputexp'] ?? 1;

if (isset($params['submit']) || isset($params['apply'])) {

    $articleid    = $params['articleid']; //-1 before save, >0 after 'apply'
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
        $$this->ShowErrors($this->Lang('nocontentgiven'));
        $error = true;
    }

    if ($news_url) {
        // check for starting or ending slashes
        if (startswith($news_url, '/') || endswith($news_url, '/')) {
            $this->ShowErrors($this->Lang('error_invalidurl'));
            $error = true;
        }

        // check for invalid chars.
        $translated = munge_string_to_url($news_url, false, true);
        if (strtolower($translated) != strtolower($news_url)) {
            $this->ShowErrors($this->Lang('error_invalidurl'));
            $error = true;
        }

        // check this url isn't a duplicate.
        // we're adding an article, not editing... any matching route is bad.
        cms_route_manager::load_routes();
        $route = cms_route_manager::find_match($news_url);
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
            $articleid = $db->GenID(CMS_DB_PREFIX . 'module_news_seq');
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

            //
            //Set custom fields
            //

            // get the field types
            $query = 'SELECT id,name,type FROM ' . CMS_DB_PREFIX . "module_news_fielddefs WHERE type='file'";
            $types = $db->GetArray($query);
            if (is_array($types)) {
                foreach ($types as $onetype) {
                    $elem = $id . 'customfield_' . $onetype['id'];
                    if (isset($_FILES[$elem]) && $_FILES[$elem]['name'] != '') {
                        if ($_FILES[$elem]['error'] != 0 || $_FILES[$elem]['tmp_name'] == '') {
                            $this->ShowErrors($this->Lang('error_upload'));
                            $error = true;
                        } else {
                            $value = Adminops::handle_upload($articleid, $elem, $error);
                            if ($value === false) {
                                $this->ShowErrors($error);
                                $error = true;
                            } else {
                                $params['customfield'][$onetype['id']] = $value;
                            }
                        }
                    }
                }
            }

            if (!$error) {
		        if (isset($params['customfield'])) {
		            foreach ($params['customfield'] as $fldid => $value) {
		                if ($value == '')
		                    continue;

		                $query = 'INSERT INTO ' . CMS_DB_PREFIX . 'module_news_fieldvals (news_id,fielddef_id,value,create_date) VALUES (?,?,?,?)';
		                $dbr = $db->Execute($query, [
		                    $articleid,
		                    $fldid,
		                    $value,
		                    $now
		                ]);
		                if (!$dbr)
		                    die('FATAL SQL ERROR: ' . $db->ErrorMsg() . '<br />QUERY: ' . $db->sql);
		            }
		        }

                if (($status == 'publishedfinal' || $status == 'final') && $news_url) {
                    // TODO: && not expired
                    // register the route
                    Adminops::delete_static_route($articleid);
                    Adminops::register_static_route($news_url, $articleid);
                }

                if (($status == 'published' || $status == 'final') && $searchable) {
                    //Update search index
                    $module = cms_utils::get_search_module();
                    if (is_object($module)) {
                        $text = '';
                        if (isset($params['customfield'])) {
                            foreach ($params['customfield'] as $fldid => $value) {
                                if (strlen($value) > 1)
                                    $text .= $value . ' ';
                            }
                        }
                        $text .= $content . ' ' . $summary . ' ' . $title . ' ' . $title;
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
        //TODO custom fields too??
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

    $tmpfname = tempnam(TMP_CACHE_LOCATION, $this->GetName() . '_preview');
    file_put_contents($tmpfname, serialize($params));

    $detail_returnid = $this->GetPreference('detail_returnid', -1);
    if ($detail_returnid <= 0) {
        // now get the default content id.
        $detail_returnid = ContentOperations::get_instance()->GetDefaultContent();
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
    for ($cnt = 0, $n = count($handlers); $cnt < $n; $cnt++) {
        ob_end_clean();
    }
    header('Content-Type: text/xml');
    echo $response;
    exit;
}

$choices = [
    $this->Lang('draft')=>'draft',
    $this->Lang('final')=>'final',
];
$statusradio = $this->CreateInputRadioGroup($id,'status',$choices,$status,'','  ');

$block = $this->GetPreference('timeblock', News::HOURBLOCK);
$withtime = ($block == News::DAYBLOCK) ? 0:1;

$categorylist = [];
$query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news_categories ORDER BY hierarchy';
$dbr = $db->Execute($query);
while ($dbr && $row = $dbr->FetchRow()) {
    $categorylist[$row['long_name']] = $row['news_category_id'];
}

// Display custom fields
$query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news_fielddefs ORDER BY item_order';
$dbr = $db->Execute($query);
$custom_flds = [];
while ($dbr && ($row = $dbr->FetchRow())) {
    if (!empty($row['extra']))
        $row['extra'] = unserialize($row['extra']);

    if (isset($row['extra']['options'])) $options = $row['extra']['options'];
    else $options = null;

    $value = isset($params['customfield'][$row['id']]) && in_array($params['customfield'][$row['id']], $params['customfield']) ? $params['customfield'][$row['id']] : '';

    if ($row['type'] == 'file') {
        $name = 'customfield_' . $row['id'];
    } else {
        $name = 'customfield[' . $row['id'] . ']';
    }

    $obj = new StdClass();

    $obj->value    = $value;
    $obj->nameattr = $id . $name;
    $obj->type     = $row['type'];
    $obj->idattr   = 'customfield_' . $row['id'];
    $obj->prompt   = $row['name'];
    $obj->size     = min(80, (int)$row['max_length']);
    $obj->max_len  = max(1, (int)$row['max_length']);
    $obj->options  = $options;
    $custom_flds[$row['name']] = $obj;
}

/*--------------------
 Pass everything to smarty
 ---------------------*/

$tpl = $smarty->createTemplate($this->GetTemplateResource('editarticle.tpl'),null,null,$smarty);

$tpl->assign('startform', $this->CreateFormStart($id, 'addarticle', $returnid, 'post', '', false, '', ['articleid'=>$articleid, 'author_id'=>$author_id]));

if ($author_id > 0) {
    $userops = $gCms->GetUserOperations();
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

$tpl->assign('title', $title)
 ->assign('articleid',$articleid)
// ->assign('useexp', $useexp)
// ->assign('inputexp', $this->CreateInputCheckbox($id, 'useexp', '1', $useexp, 'class="pagecheckbox"'))
 ->assign('fromdate', $fromdate)
 ->assign('todate', $todate)
 ->assign('fromtime', $fromtime)
 ->assign('totime', $totime)
 ->assign('withtime', $withtime)
 ->assign('status', $status)
 ->assign('categorylist', array_flip($categorylist))
 ->assign('category', $usedcategory)
 ->assign('searchable', $searchable)
 ->assign('extra', $extra)
 ->assign('news_url', $news_url);

if ($this->CheckPermission('Approve News')) {
    $tpl->assign('statuses',$statusradio);
    //->assign('statustext', lang('status'));
}

if ($custom_flds) {
    $tpl->assign('custom_fields', $custom_flds);
}

// get the detail templates, if any
try {
    $type = CmsLayoutTemplateType::load($this->GetName() . '::detail');
    $templates = $type->get_template_list();
    $list = [];
    if ($templates) {
        foreach ($templates as $template) {
            $list[$template->get_id()] = $template->get_name();
        }
    }
    if ($list) {
		$str = Utils::CreateHierarchyDropdown(0, $this->GetPreference('detail_returnid', -1), 'preview_returnid');

        $tpl->assign('detail_templates', $list)
         ->assign('cur_detail_template', $this->GetPreference('current_detail_template'))
        // tab stuff
         ->assign('start_tab_headers', $this->StartTabHeaders())
         ->assign('tabheader_article', $this->SetTabHeader('article', $this->Lang('article')))
         ->assign('tabheader_preview', $this->SetTabHeader('preview', $this->Lang('preview')))
         ->assign('end_tab_headers', $this->EndTabHeaders())
         ->assign('start_tab_content', $this->StartTabContent())
         ->assign('start_tab_article', $this->StartTab('article', $params))
         ->assign('start_tab_preview', $this->StartTab('preview', $params))
         ->assign('end_tab', $this->EndTab())
         ->assign('end_tab_content', $this->EndTabContent())
         ->assign('preview_returnid', $str);
    }
} catch( Exception $e ) {
    audit('', $this->GetName(), 'No detail templates available for preview');
}

// page resources
include __DIR__.DIRECTORY_SEPARATOR.'method.articlescript.php';

$tpl->display();
