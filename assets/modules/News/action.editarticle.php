<?php
/*
Edit item action for CMSMS News module.
Copyright (C) 2005-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AdminUtils;
use CMSMS\ContentOperations;
use CMSMS\Events;
use CMSMS\FormUtils;
use News\AdminOperations;

if (!isset($gCms))  exit ;
if (!$this->CheckPermission('Modify News'))  return;
if (isset($params['cancel'])) {
    $this->Redirect($id, 'defaultadmin', $returnid);
}

$me = $this->GetName();
$cz = $config['timezone'];
$dt = new DateTime(null, new DateTimeZone($cz));
$toffs = $dt->getOffset();
$useexp = $params['inputexp'] ?? 1;

if (isset($params['submit']) || isset($params['apply'])) {

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
        cms_route_manager::load_routes();
        $route = cms_route_manager::find_match($news_url, true);
        if ($route) {
            $dflts = $route->get_defaults();
            if ($route['key1'] != $me || !isset($dflts['articleid']) || $dflts['articleid'] != $articleid) {
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

        //
        //Update custom fields
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
                        $value = AdminOperations::handle_upload($articleid, $elem, $error);
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
/*
            if (isset($params['customfield'])) {
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
                            $query = 'INSERT INTO ' . CMS_DB_PREFIX . "module_news_fieldvals (news_id,fielddef_id,value,create_date) VALUES (?,?,?,$now)";
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

            if (isset($params['delete_customfield']) && is_array($params['delete_customfield'])) {
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
*/
            if (($status == 'published' || $status =='final') && $news_url != '') {
                AdminOperations::delete_static_route($articleid);
                AdminOperations::register_static_route($news_url, $articleid);
            }

            //Update search index
            $module = cms_utils::get_search_module();
            if (is_object($module)) {
                if ($status == 'draft' || $status == 'archived' || !$searchable) {
                    $module->DeleteWords($me, $articleid, 'article');
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
                    $module->AddWords($me, $articleid, 'article', $text, ($useexp == 1 && $this->GetPreference('expired_searchable', 0) == 0) ? $enddate : NULL);
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
            return;
        }

        if (!$error && !isset($params['apply'])) {
            // redirect out of here.
            $this->SetMessage($this->Lang('articlesubmitted'));
            $this->Redirect($id, 'defaultadmin', $returnid);
            return;
        }

    }

    $query = 'SELECT create_date,modified_date,start_time,end_time FROM '. CMS_DB_PREFIX . 'module_news WHERE news_id=?';
    $row = $db->GetRow($query, [$articleid]);
} elseif (!isset($params['preview'])) {
    //
    // Load data from database
    //
    $query = 'SELECT * FROM ' . CMS_DB_PREFIX . 'module_news WHERE news_id = ?';
    $row = $db->GetRow($query, [$params['articleid']]);

    if ($row) {
        $articleid    = $row['news_id'];
        $title        = $row['news_title'];
        $content      = $row['news_data'];
        $summary      = $row['summary'];
        $status       = $row['status'];
        if ($status == 'published') $status = 'final';
        $searchable   = $row['searchable'];
        $startdate    = $row['start_time'];
        $enddate      = $row['end_time'];
        $usedcategory = $row['news_category_id'];
        $author_id    = $row['author_id'];
        $extra        = $row['news_extra'];
        $news_url     = $row['news_url'];
    } else {
        //TODO handle error
    }
} else {
    // save data for preview
    unset($params['apply']);
    unset($params['preview']);
    unset($params['submit']);
    unset($params['cancel']);
    unset($params['ajax']);

    $tmpfname = tempnam(TMP_CACHE_LOCATION, $me . '_preview');
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
    for ($cnt = 0; $cnt < count($handlers); $cnt++) {
        ob_end_clean();
    }
    header('Content-Type: text/xml');
    echo $response;
    exit;
}

$fmt = $this->GetDateFormat();
$created = strftime($fmt, $row['create_date']);
if ($row['modified_date'] > $row['create_date']) {
    $modified = strftime($fmt, $row['modified_date']);
} else {
    $modified = NULL;
}
if ($status == 'final') {
    if ($row['start_time']) {
        $published = strftime($fmt, $row['start_time']);
    } else {
        $published = '?';
    }
} else {
    $published = NULL;
}
if ($status == 'archived') {
    if ($row['end_time']) {
        $archived = strftime($fmt, $row['end_time']);
    } else {
        $archived = '?';
    }
} else {
    $archived = NULL;
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

if ($startdate > 0) {
    $st = strtotime('midnight', $startdate);
    $fromdate = date('Y-m-j', $st);
    if ($withtime) {
        $stt = $startdate - $st - $toffs;
        $stt = (int)($stt / $rounder) * $rounder;
        $fromtime = date('g:ia', $stt);
    } else {
        $fromtime = null;
    }
} else {
    $fromdate = '';
    $fromtime = null;
}

if ($enddate > 0) {
    $st = strtotime('midnight', $enddate);
    $todate = date('Y-m-j', $st);
    if ($withtime) {
        $stt = $enddate - $st - $toffs;
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
$dbr = $db->Execute($query);
while ($dbr && $row = $dbr->FetchRow()) {
    $categorylist[$row['long_name']] = $row['news_category_id'];
}

/*
/ *-------------------
 Custom fields logic
--------------------* /

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
    if (!empty($row['extra']))
        $row['extra'] = unserialize($row['extra']);

    if (isset($row['extra']['options'])) $options = $row['extra']['options'];
    else $options = null;

    if (isset($fieldvals[$row['id']])) $value = $fieldvals[$row['id']]['value'];
    else $value = '';
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
    $obj->size     = min(80, (int)$row['max_length']);
    $obj->max_len  = max(1, (int)$row['max_length']);
    $obj->delete   = $id . 'delete_customfield[' . $row['id'] . ']';
    $obj->options  = $options;
    $custom_flds[$row['name']] = $obj;
}
*/

$parms = array_merge($params, ['articleid'=>$articleid, 'author_id'=>$author_id]);
unset($parms['action']);

/*--------------------
 Pass everything to smarty
---------------------*/

$tpl = $smarty->createTemplate($this->GetTemplateResource('editarticle.tpl'),null,null,$smarty);

$tpl->assign('formaction','editarticle')
    ->assign('formparms', $parms);

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
 ->assign('createat', $created)
 ->assign('modat', $modified)
 ->assign('pubat', $published)
 ->assign('archat', $archived)
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
/*
// tab stuff
 ->assign('start_tab_headers', $this->StartTabHeaders())
 ->assign('tabheader_article', $this->SetTabHeader('article', $this->Lang('article')))
 ->assign('tabheader_preview', $this->SetTabHeader('preview', $this->Lang('preview')))
 ->assign('end_tab_headers', $this->EndTabHeaders())
 ->assign('start_tab_content', $this->StartTabContent())
 ->assign('start_tab_article', $this->StartTab('article', $params))
 ->assign('start_tab_preview', $this->StartTab('preview', $params))
 ->assign('end_tab', $this->EndTab())
 ->assign('end_tab_content', $this->EndTabContent());
*/
if ($this->CheckPermission('Approve News')) {
	$choices = [
		$this->Lang('draft')=>'draft',
		$this->Lang('final')=>'final',
		$this->Lang('archived')=>'archived',
	];
	$statusradio = $this->CreateInputRadioGroup($id,'status',$choices,$status,'','  ');
    $tpl->assign('statuses',$statusradio);
    //->assign('statustext', lang('status'));
}
/*
if ($custom_flds) {
    $tpl->assign('custom_fields', $custom_flds);
}
*/

// get the detail templates, if any
try {
    $type = CmsLayoutTemplateType::load($me . '::detail');
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
    audit('', $me, 'No detail template available for preview');
}

// page resources
include __DIR__.DIRECTORY_SEPARATOR.'method.articlescript.php';

$tpl->display();
