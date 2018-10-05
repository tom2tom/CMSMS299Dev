<?php

use CMSMS\ContentOperations;
use CMSMS\Events;
use CMSMS\FormUtils;
use News\Adminops;

if (!isset($gCms))  exit ;

if (!$this->CheckPermission('Modify News'))  return;
if (isset($params['cancel'])) $this->Redirect($id, 'defaultadmin', $returnid);
// default status
$status = ($this->CheckPermission('Approve News')) ? 'published' : 'draft';

$cz = $config['timezone'];
$tz = new DateTimeZone($cz);
$dt = new DateTime(null, $tz);
$toffs = $tz->getOffset($dt);

$useexp = $params['inputexp'] ?? 1;

if (isset($params['submit']) || isset($params['apply'])) {

    $title        = $params['title'];
    $summary      = $params['summary'];
    $content      = $params['content'];
    $status       = $params['status'] ?? $status;
    $searchable   = $params['searchable'] ?? 0;
    $news_url     = $params['news_url'];
    $usedcategory = $params['category'];
    $author_id    = $params['author_id'] ?? '-1';
    $extra        = trim($params['extra']);

    $st = strtotime($params['fromdate']);
    if ($st !== false) {
        if (isset($params['fromtime'])) {
            $stt = strtotime($params['fromtime'], 0);
            if ($stt !== false) {
                $st += $stt + $toffs;
            }
        }
        $startdate = $st;
    } else {
        //TODO process non-date input or bad-date error
        $startdate = NULL;
    }

    if ($useexp == 1) {
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
        } else {
            //TODO process non-date input or bad-date error
            $enddate = NULL;
        }
    }

    // Validation
    $error = false;
    if (empty($title)) {
        $this->ShowErrors($this->Lang('notitlegiven'));
        $error = true;
    } elseif (empty($content)) {
        $$this->ShowErrors($this->Lang('nocontentgiven'));
        $error = true;
    }

    if ($useexp == 1 && $startdate <= $enddate) {
        $this->ShowErrors($this->Lang('error_invaliddates'));
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
        $args = [
            $articleid,
            $title,
            $content,
            $summary,
            $usedcategory,
            $status,
            $searchable,
            $startdate,
            (($useexp == 1)?$enddate:NULL),
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
        //Handle submitting the 'custom' fields
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

        if (!$error && isset($params['customfield'])) {
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

        if (!$error) {
		    if ($status == 'published' && $news_url != '') {
		        // todo: if not expired
		        // register the route.
		        Adminops::delete_static_route($articleid);
		        Adminops::register_static_route($news_url, $articleid);
		    }

		    if ($status == 'published' && $searchable) {
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
		        'start_time' => $startdate,
		        'end_time' => $enddate,
                'post_time' => $startdate,
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
        } // if !$error
    } // outer if !$error
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
    for ($cnt = 0; $cnt < sizeof($handlers); $cnt++) {
        ob_end_clean();
    }
    header('Content-Type: text/xml');
    echo $response;
    exit ;
}

$choices = [
    $this->Lang('draft')=>'draft',
    $this->Lang('final')=>'final',
];
$statusradio = $this->CreateInputRadioGroup($id,'status',$choices,$status,'','  ');

//TODO
$block = $this->GetPreference('timeblock', News::HOURBLOCK);
$withtime = ($block == News::DAYBLOCK) ? 0:1;
$fromdate = '';
$fromtime = '';
$todate = '';
$totime = '';

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
/*
    FIXME - If we create inputs with hmtl markup in smarty template, whats the use of switch and form API here?
    switch( $row['type'] ) {
        case 'textbox' :
            $size = min(50, $row['max_length']);
            $obj->field = $this->CreateInputText($id, $name, $value, $size, $row['max_length']); DEPRECATED API
            break;
        case 'checkbox' :
            $obj->field = $this->CreateInputHidden($id, $name, $value != '' ? $value : '0') . $this->CreateInputCheckbox($id, $name, '1', $value != '' ? $value : '0');
            break;
        case 'textarea' :
            $obj->field = FormUtils::create_textarea(['enablewysiwyg'=>1, 'modid'=>$id, 'name'=>$name, 'value'=>$value]); DEPRECATED API
            break;
        case 'file' :
            $name = "customfield_" . $row['id'];
            $obj->field = $this->CreateFileUploadInput($id, $name); DEPRECATED API
            break;
        case 'dropdown' :
            $obj->field = $this->CreateInputDropdown($id, $name, array_flip($options)); DEPRECATED API
            break;
    }
*/
    $custom_flds[$row['name']] = $obj;
}

/*--------------------
 * Pass everything to smarty
 ---------------------*/

$tpl = $smarty->createTemplate($this->GetTemplateResource('editarticle.tpl'),null,null,$smarty);

$tpl->assign('formid', $id)
 ->assign('startform', $this->CreateFormStart($id, 'addarticle', $returnid))
 ->assign('hidden', $this->CreateInputHidden($id, 'articleid', $articleid) . $this->CreateInputHidden($id, 'author_id', $author_id))
 ->assign('title', $title);

if ($author_id > 0) {
    $userops = $gCms->GetUserOperations();
    $theuser = $userops->LoadUserById($author_id);
    if ($theuser) {
    $tpl->assign('inputauthor', $theuser->username);
    } else {
        $tpl->assign('inputauthor', $this->Lang('anonymous'));
    }
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

if ($this->GetPreference('allow_summary_wysiwyg',1)) {
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

$tpl->assign('useexp', $useexp)
 ->assign('inputexp', $this->CreateInputCheckbox($id, 'useexp', '1', $useexp, 'class="pagecheckbox"'))
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
 ->assign('news_url', $news_url)
 ->assign('delete_field_val', $this->Lang('delete'))
 ->assign('warning_preview', $this->Lang('warning_preview'))
 ->assign('select_option', $this->Lang('select_option'))
// tab stuff
 ->assign('start_tab_headers', $this->StartTabHeaders())
 ->assign('tabheader_article', $this->SetTabHeader('article', $this->Lang('article')))
 ->assign('tabheader_preview', $this->SetTabHeader('preview', $this->Lang('preview'))) //TODO
 ->assign('end_tab_headers', $this->EndTabHeaders())
 ->assign('start_tab_content', $this->StartTabContent())
 ->assign('start_tab_article', $this->StartTab('article', $params))
 ->assign('end_tab_article', $this->EndTab())
 ->assign('end_tab_content', $this->EndTabContent());

if ($this->CheckPermission('Approve News')) {
    $tpl->assign('statuses',$statusradio);
    //->assign('statustext', lang('status'));
}

if ($custom_flds) {
    $tpl->assign('custom_fields', $custom_flds);
}
$contentops = cmsms()->GetContentOperations();
$tpl->assign('preview_returnid', $contentops->CreateHierarchyDropdown('', $this->GetPreference('detail_returnid', -1), 'preview_returnid'));

// get the list of detail templates.
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
        $tpl->assign('prompt_detail_template', $this->Lang('detail_template'))
          ->assign('prompt_detail_page', $this->Lang('detail_page'))
          ->assign('detail_templates', $list)
          ->assign('cur_detail_template', $this->GetPreference('current_detail_template'))
          ->assign('start_tab_preview', $this->StartTab('preview', $params))
          ->assign('end_tab_preview', $this->EndTab());
    }
} catch( Exception $e ) {
    audit('', $this->GetName(), 'No detail templates available for preview');
}

// page resources
$baseurl = $this->GetModuleURLPath();
$css = <<<EOS
 <link rel="stylesheet" href="{$baseurl}/css/jquery.datepicker.css">
 <link rel="stylesheet" href="{$baseurl}/css/jquery.timepicker.css">

EOS;
$this->AdminHeaderContent($css);
include __DIR__.DIRECTORY_SEPARATOR.'method.articlescript.php';

$tpl->display();
