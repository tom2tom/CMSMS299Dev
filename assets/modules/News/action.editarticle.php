<?php

use CMSMS\ContentOperations;
use CMSMS\Events;
use News\news_admin_ops;

if (!isset($gCms))  exit ;

if (!$this->CheckPermission('Modify News'))  return;
if (isset($params['cancel'])) $this->Redirect($id, 'defaultadmin', $returnid);

/*--------------------
 * Variables
 ---------------------*/
if ($this->CheckPermission('Approve News'))  $status = 'published';

$status       = 'draft';
$postdate     = time();
$startdate    = time();
$enddate      = strtotime('+6 months', time());
$articleid    = $params['articleid'] ?? '';
$content      = $params['content'] ?? '';
$summary      = $params['summary'] ?? '';
$news_url     = $params['news_url'] ?? '';
$usedcategory = $params['category'] ?? '';
$author_id    = $params['author_id'] ?? '-1';
$useexp       = isset($params['useexp']) ? 1: 0;
$extra        = isset($params['extra']) ? trim($params['extra']) : '';
$searchable   = isset($params['searchable']) ? (int)$params['searchable'] : 1;
$title        = $params['title'] ?? '';
$status       = $params['status'] ?? $status;

if (isset($params['postdate_Month'])) {
    $postdate = mktime($params['postdate_Hour'], $params['postdate_Minute'], $params['postdate_Second'], $params['postdate_Month'], $params['postdate_Day'], $params['postdate_Year']);
}

if (isset($params['startdate_Month'])) {
    $startdate = mktime($params['startdate_Hour'], $params['startdate_Minute'], $params['startdate_Second'], $params['startdate_Month'], $params['startdate_Day'], $params['startdate_Year']);
}

if (isset($params['enddate_Month'])) {
    $enddate = mktime($params['enddate_Hour'], $params['enddate_Minute'], $params['enddate_Second'], $params['enddate_Month'], $params['enddate_Day'], $params['enddate_Year']);
}

/*--------------------
 * Logic
 ---------------------*/

if (isset($params['submit']) || isset($params['apply'])) {
    $error = FALSE;
    if (empty($title)) {
        $error = $this->Lang('notitlegiven');
    } else if (empty($content)) {
        $error = $this->Lang('nocontentgiven');
    } else if ($useexp == 1) {
        if ($startdate >= $enddate)
            $error = $this->Lang('error_invaliddates');
    }

    $startdatestr = NULL;
    $enddatestr = NULL;
    if ($useexp != 0) {
        $startdate = trim($db->DbTimeStamp($startdate), "'");
        $enddate = trim($db->DbTimeStamp($enddate), "'");
    }

    if (empty($error) && $news_url != '') {
        // check for starting or ending slashes
        if (startswith($news_url, '/') || endswith($news_url, '/'))
            $error = $this->Lang('error_invalidurl');
        if ($error === FALSE) {
            // check for invalid chars.
            $translated = munge_string_to_url($news_url, false, true);
            if (strtolower($translated) != strtolower($news_url))
                $error = $this->Lang('error_invalidurl');
        }

        if ($error === FALSE) {
            // make sure this url isn't taken.
            cms_route_manager::load_routes();
            $route = cms_route_manager::find_match($news_url, TRUE);
            if ($route) {
                $dflts = $route->get_defaults();
                if ($route['key1'] != $this->GetName() || !isset($dflts['articleid']) || $dflts['articleid'] != $articleid) {
                    // we're adding an article, not editing... any matching route is bad.
                    $error = $this->Lang('error_invalidurl');
                }
            }
        }
    }

    if (!$error) {
        //
        // database work
        //
        $query = 'UPDATE ' . CMS_DB_PREFIX . 'module_news SET news_title=?, news_data=?, summary=?, status=?, news_date=?, news_category_id=?, start_time=?, end_time=?, modified_date=?, news_extra=?, news_url = ?, searchable = ? WHERE news_id = ?';
        if ($useexp == 1) {
            $db->Execute($query, [
                $title,
                $content,
                $summary,
                $status,
                trim($db->DbTimeStamp($postdate), "'"),
                $usedcategory,
                trim($db->DbTimeStamp($startdate), "'"),
                trim($db->DbTimeStamp($enddate), "'"),
                trim($db->DbTimeStamp(time()), "'"),
                $extra,
                $news_url,
                $searchable,
                $articleid
            ]);
        } else {
            $db->Execute($query, [
                $title,
                $content,
                $summary,
                $status,
                trim($db->DbTimeStamp($postdate), "'"),
                $usedcategory,
                $startdatestr,
                $enddatestr,
                trim($db->DbTimeStamp(time()), "'"),
                $extra,
                $news_url,
                $searchable,
                $articleid
            ]);
        }

        //
        //Update custom fields
        //

        // get the field types
        $qu = 'SELECT id,name,type FROM ' . CMS_DB_PREFIX . "module_news_fielddefs WHERE type='file'";
        $types = $db->GetArray($qu);

        $error = false;
        if (is_array($types)) {
            foreach ($types as $onetype) {
                $elem = $id . 'customfield_' . $onetype['id'];
                if (isset($_FILES[$elem]) && $_FILES[$elem]['name'] != '') {
                    if ($_FILES[$elem]['error'] != 0 || $_FILES[$elem]['tmp_name'] == '') {
                        $error = true;
                        $this->ShowErrors($this->Lang('error_upload'));
                    } else {
                        $error = false;
                        $value = news_admin_ops::handle_upload($articleid, $elem, $error);
                        $smarty->assign('checking', 'blah'); //TODO relevant template
                        if ($value !== false)
                            $params['customfield'][$onetype['id']] = $value;
                    }
                }
            } // foreach
        }// if

        if (isset($params['customfield']) && !$error) {
            $now = $db->DbTimeStamp(time());
            foreach ($params['customfield'] as $fldid => $value) {
                // first check if it's available
                $query = 'SELECT value FROM ' . CMS_DB_PREFIX . 'module_news_fieldvals WHERE news_id = ? AND fielddef_id = ?';
                $tmp = $db->GetOne($query, [
                    $articleid,
                    $fldid
                ]);
                $dbr = true;
                if ($tmp === false) {
                    if (!empty($value)) {
                        $query = 'INSERT INTO ' . CMS_DB_PREFIX . "module_news_fieldvals (news_id,fielddef_id,value,create_date,modified_date) VALUES (?,?,?,$now,$now)";
                        $dbr = $db->Execute($query, [
                            $articleid,
                            $fldid,
                            $value
                        ]);
                    }
                } else {
                    if (empty($value)) {
                        $query = 'DELETE FROM ' . CMS_DB_PREFIX . 'module_news_fieldvals WHERE news_id = ? AND fielddef_id = ?';
                        $dbr = $db->Execute($query, [
                            $articleid,
                            $fldid
                        ]);
                    } else {
                        $query = 'UPDATE ' . CMS_DB_PREFIX . "module_news_fieldvals
                      SET value = ?, modified_date = $now WHERE news_id = ? AND fielddef_id = ?";
                        $dbr = $db->Execute($query, [
                            $value,
                            $articleid,
                            $fldid
                        ]);
                    }
                }
                if (!$dbr)
                    die('FATAL SQL ERROR: ' . $db->ErrorMsg() . '<br />QUERY: ' . $db->sql);
            }
        }
    }

    if (isset($params['delete_customfield']) && is_array($params['delete_customfield']) && !$error) {
        foreach ($params['delete_customfield'] as $k => $v) {
            if ($v != 'delete')
                continue;
            $query = 'DELETE FROM ' . CMS_DB_PREFIX . 'module_news_fieldvals WHERE news_id = ? AND fielddef_id = ?';
            $db->Execute($query, [
                $articleid,
                $k
            ]);
        }
    }

    if (!$error && $status == 'published' && $news_url != '') {
        news_admin_ops::delete_static_route($articleid);
        news_admin_ops::register_static_route($news_url, $articleid);
    }

    //Update search index
    if (!$error) {
        $module = cms_utils::get_search_module();
        if (is_object($module)) {
            if ($status == 'draft' || !$searchable) {
                $module->DeleteWords($this->GetName(), $articleid, 'article');
            } else {
                if (!$useexp || ($enddate > time()) || $this->GetPreference('expired_searchable', 1) == 1) {
                    $text = '';
                }

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

        Events::SendEvent('News', 'NewsArticleEdited', [
            'news_id' => $articleid,
            'category_id' => $usedcategory,
            'title' => $title,
            'content' => $content,
            'summary' => $summary,
            'status' => $status,
            'start_time' => $startdate,
            'end_time' => $enddate,
            'post_time' => $postdate,
            'extra' => $extra,
            'useexp' => $useexp,
            'news_url' => $news_url
        ]);
        // put mention into the admin log
        audit($articleid, 'News: ' . $title, 'Article edited');
    }// if no error.

    if (isset($params['apply']) && isset($params['ajax'])) {
        $response = '<EditArticle>';
        if ($error != '') {
            $response .= '<Response>Error</Response>';
            $response .= '<Details><![CDATA[' . $error . ']]></Details>';
        } else {
            $response .= '<Response>Success</Response>';
            $response .= '<Details><![CDATA[' . $this->Lang('articleupdated') . ']]></Details>';
        }
        $response .= '</EditArticle>';
        echo $response;
        return;
    }

    if (!isset($params['apply']) && !$error) {
        // redirect out of here.
        $this->SetMessage($this->Lang('articlesubmitted'));
        $this->Redirect($id, 'defaultadmin', $returnid);
        return;
    }

// end submit or apply
} elseif (isset($params['preview'])) {
    // save data for preview.
    unset($params['apply']);
    unset($params['preview']);
    unset($params['submit']);
    unset($params['cancel']);
    unset($params['ajax']);

    $tmpfname = tempnam(TMP_CACHE_LOCATION, $this->GetName() . '_preview');
    file_put_contents($tmpfname, serialize($params));

    $detail_returnid = $this->GetPreference('detail_returnid', -1);
    if ($detail_returnid <= 0)
        $detail_returnid = ContentOperations::get_instance()->GetDefaultContent();
    if (isset($params['previewpage']) && (int)$params['previewpage'] > 0)
        $detail_returnid = (int)$params['previewpage'];

    $_SESSION['news_preview'] = [
        'fname' => basename($tmpfname),
        'checksum' => md5_file($tmpfname)
    ];
    $tparms = ['preview' => md5(serialize($_SESSION['news_preview']))];
    if (isset($params['detailtemplate']))
        $tparms['detailtemplate'] = trim($params['detailtemplate']);
    $url = $this->create_url('_preview_', 'detail', $detail_returnid, $tparms, TRUE);

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
    for ($cnt = 0; $cnt < sizeof($handlers); $cnt++) {
        ob_end_clean();
    }
    header('Content-Type: text/xml');
    echo $response;
    exit ;
} else {
    //
    // Load data from database
    //
    $query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news WHERE news_id = ?';
    $row = $db->GetRow($query, [$articleid]);

    if ($row) {
        $title        = $row['news_title'];
        $content      = $row['news_data'];
        $extra        = $row['news_extra'];
        $summary      = $row['summary'];
        $news_url     = $row['news_url'];
        $status       = $row['status'];
        $usedcategory = $row['news_category_id'];
        $postdate     = $db->UnixTimeStamp($row['news_date']);
        $startdate    = $db->UnixTimeStamp($row['start_time']);
        $author_id    = $row['author_id'];
        $searchable   = $row['searchable'];
        $useexp = 0;
        if (isset($row['end_time'])) {
            $useexp  = 1;
            $enddate = $db->UnixTimeStamp($row['end_time']);
        }
    }
}

$statusdropdown = [];
$statusdropdown[$this->Lang('draft')] = 'draft';
$statusdropdown[$this->Lang('published')] = 'published';

$categorylist = [];
$query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news_categories ORDER BY hierarchy';
$dbresult = $db->Execute($query);

while ($dbresult && $row = $dbresult->FetchRow()) {
    $categorylist[$row['long_name']] = $row['news_category_id'];
}

/*--------------------
 * Custom fields logic
 ---------------------*/

// Get the field values
$fieldvals = [];
$query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news_fieldvals WHERE news_id = ?';
$tmp = $db->GetArray($query, [$articleid]);
if (is_array($tmp)) {
    foreach ($tmp as $one) {
        $fieldvals[$one['fielddef_id']] = $one;
    }
}

$query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news_fielddefs ORDER BY item_order';
$dbr = $db->Execute($query);
$custom_flds = [];
while ($dbr && ($row = $dbr->FetchRow())) {
    if (isset($row['extra']) && $row['extra']) $row['extra'] = unserialize($row['extra']);

    $options = null;
    if (isset($row['extra']['options'])) $options = $row['extra']['options'];

    $value = '';
    if (isset($fieldvals[$row['id']])) $value = $fieldvals[$row['id']]['value'];
    $value = isset($params['customfield'][$row['id']]) && in_array($params['customfield'][$row['id']], $params['customfield']) ? $params['customfield'][$row['id']] : $value;

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
    $obj->size     = min(80, $row['max_length']);
    $obj->max_len  = max(1, (int)$row['max_length']);
    $obj->delete   = $id . 'delete_customfield[' . $row['id'] . ']';
    $obj->options  = $options;
    // FIXME - If we create inputs with hmtl markup in smarty template, whats the use of switch and form API here?
    /*
    switch( $row['type'] ) {
        case 'textbox' :
            $size = min(50, $row['max_length']);
            $obj->field = $this->CreateInputText($id, $name, $value, $size, $row['max_length']);
            break;
        case 'checkbox' :
            $obj->field = $this->CreateInputHidden($id, $name, 0) . $this->CreateInputCheckbox($id, $name, 1, (int)$value);
            break;
        case 'textarea' :
            $obj->field = CmsFormUtils::create_textarea(['enablewysiwyg'=>1, 'modid'=>$id, 'name'=>$name, 'value'=>$value]);
            break;
        case 'file' :
            $del = '';
            if ($value != '') {
                $deln = 'delete_customfield[' . $row['id'] . ']';
                $del = '&nbsp;' . $this->Lang('delete') . $this->CreateInputCheckbox($id, $deln, 'delete');
            }
            $obj->field = $value . '&nbsp;' . $this->CreateFileUploadInput($id, $name) . $del;
            break;
        case 'dropdown' :
            $obj->field = $this->CreateInputDropdown($id, $name, array_flip($options), -1, $value);
            break;
    }
    */

    $custom_flds[$row['name']] = $obj;
}

/*--------------------
 * Pass everything to smarty
 ---------------------*/

$tpl = $smarty->createTemplate($this->GetTemplateResource('editarticle.tpl'),null,null,$smarty);

if ($author_id > 0) {
    $userops = $gCms->GetUserOperations();
    $theuser = $userops->LoadUserById($author_id);
    $tpl->assign('inputauthor', $theuser->username);
} else if ($author_id == 0) {
    $tpl->assign('inputauthor', $this->Lang('anonymous'));
} else {
    $feu = $this->GetModuleInstance('FrontEndUsers');
    if ($feu) {
        $uinfo = $feu->GetUserInfo($author_id * -1);
        if ($uinfo[0])
            $tpl->assign('inputauthor', $uinfo[1]['username']);
    }
}

$tpl->assign('formid', $id)
 ->assign('startform', $this->CreateFormStart($id, 'editarticle', $returnid, 'POST', 'multipart/form-data'))
 ->assign('endform', $this->CreateFormEnd())
 ->assign('hide_summary_field', $this->GetPreference('hide_summary_field', '0'))
 ->assign('authortext', $this->Lang('author'))
 ->assign('articleid', $articleid)
 ->assign('titletext', $this->Lang('title'))
 ->assign('searchable', $searchable)
 ->assign('extratext', $this->Lang('extra'))
 ->assign('extra', $extra)
 ->assign('urltext', $this->Lang('url'))
 ->assign('news_url', $news_url)
 ->assign('title', $title);
$parms = [
    'modid' => $id,
    'name' => 'summary',
    'class' => 'pageextrasmalltextarea',
    'value' => $summary,
];
if ($this->GetPreference('allow_summary_wysiwyg', 1)) {
    $parms += [
        'enablewysiwyg' => 1,
        'addtext' => 'style="height:5em;"', //smaller again ...
    ];
}
$tpl->assign('inputsummary', CmsFormUtils::create_textarea($parms))
 ->assign('inputcontent', CmsFormUtils::create_textarea([
    'enablewysiwyg' => 1,
    'modid' => $id,
    'name' => 'content',
    'value' => $content,
]));
$tpl->assign('useexp', $useexp)
 ->assign('actionid', $id)
 ->assign('inputexp', $this->CreateInputCheckbox($id, 'useexp', '1', $useexp, 'class="pagecheckbox"'))
 ->assign('postdate', $postdate)
 ->assign('postdateprefix', $id . 'postdate_')
 ->assign('startdate', $startdate)
 ->assign('startdateprefix', $id . 'startdate_')
 ->assign('enddate', $enddate)
 ->assign('enddateprefix', $id . 'enddate_')
 ->assign('status', $status)
 ->assign('categorylist', array_flip($categorylist))
 ->assign('category', $usedcategory)
 ->assign('hidden', $this->CreateInputHidden($id, 'articleid', $articleid) . $this->CreateInputHidden($id, 'author_id', $author_id))
//see template  ->assign('submit', $this->CreateInputSubmit($id, 'submit', lang('submit')))
// ->assign('apply', $this->CreateInputSubmit($id, 'apply', lang('apply')))
// ->assign('cancel', $this->CreateInputSubmit($id, 'cancel', lang('cancel')))
 ->assign('delete_field_val', $this->Lang('delete'))
 ->assign('titletext', $this->Lang('title'))
 ->assign('extratext', $this->Lang('extra'))
 ->assign('categorytext', $this->Lang('category'))
 ->assign('summarytext', $this->Lang('summary'))
 ->assign('contenttext', $this->Lang('content'))
 ->assign('postdatetext', $this->Lang('postdate'))
 ->assign('useexpirationtext', $this->Lang('useexpiration'))
 ->assign('startdatetext', $this->Lang('startdate'))
 ->assign('enddatetext', $this->Lang('enddate'))
 ->assign('select_option', $this->Lang('select_option'))
// tab stuff.
 ->assign('start_tab_headers', $this->StartTabHeaders())
 ->assign('tabheader_article', $this->SetTabHeader('article', $this->Lang('article')))
 ->assign('tabheader_preview', $this->SetTabHeader('preview', $this->Lang('preview')))
 ->assign('end_tab_headers', $this->EndTabHeaders())
 ->assign('start_tab_content', $this->StartTabContent())
 ->assign('start_tab_article', $this->StartTab('article', $params))
 ->assign('end_tab_article', $this->EndTab())
 ->assign('end_tab_content', $this->EndTabContent())
 ->assign('warning_preview', $this->Lang('warning_preview'));

if ($this->CheckPermission('Approve News')) {
    $tpl->assign('statustext', lang('status'))
     ->assign('statuses', array_flip($statusdropdown));
}

if (count($custom_flds) > 0)
    $tpl->assign('custom_fields', $custom_flds);

$contentops = cmsms()->GetContentOperations();
$tpl->assign('preview_returnid', $contentops->CreateHierarchyDropdown('', $this->GetPreference('detail_returnid', -1), 'preview_returnid'));

// get the list of detail templates.
try {
    $type = CmsLayoutTemplateType::load($this->GetName() . '::detail');
    $templates = $type->get_template_list();
    $list = [];
    if (is_array($templates) && count($templates)) {
        foreach ($templates as $template) {
            $list[$template->get_id()] = $template->get_name();
        }
    }
    if ($list) {
        $tpl->assign('prompt_detail_template', $this->Lang('detail_template'))
         ->assign('prompt_detail_page', $this->Lang('detail_page'))
         ->assign('detail_templates', $list)
         ->assign('cur_detail_template', $this->GetPreference('current_detail_template'))
         ->assign('start_tab_preview', $this->StartTab('preview', $params))
         ->assign('end_tab_preview', $this->EndTab());
    }
    include __DIR__.DIRECTORY_SEPARATOR.'method.articlescript.php';
} catch( Exception $e ) {
    audit('', $this->GetName(), 'No detail templates available for preview');
}

// display the template
$tpl->display();
