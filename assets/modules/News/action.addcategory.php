<?php
/*
CMSMS News module add-category action.
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

use CMSMS\Events;
use CMSMS\FileType;
use CMSMS\Lone;
use News\AdminOperations;
use News\Utils;
use function CMSMS\de_specialize;
use function CMSMS\log_info;
use function CMSMS\specialize;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Modify News Preferences') ) exit;

if( isset($params['cancel']) ) {
    $this->RedirectToAdminTab('groups');
}

if( isset($params['parent']) ) {
    $parent = (int)$params['parent'];
}
else {
    $parent = -1;
}

if( isset($params['name']) ) {
    //if( $parent == 0 ) $parent = -1;
    $tmp = de_specialize(trim($params['name']));
    // valid URL chars allowed (tho' some must be encoded in URL's)
    $name = preg_replace('<[^A-Za-z0-9\-._~:/?#[]@!$&\'()*+,;=%\x00-\xff]>', '', $tmp); //3.0+
    if( $name ) { // TODO if (!$error)
        //small race-risk here
        $query = 'SELECT news_category_id FROM '.CMS_DB_PREFIX.'module_news_categories WHERE parent_id = ? AND news_category_name = ?';
        $tmp = $db->getOne($query, [$parent, $name]);
        if( $tmp ) {
            //$error = true;
            $this->ShowErrors($this->Lang('error_duplicatename'));
        }
        else {
            if( !empty($params['generate_url']) ) {
                $str = ( $name ) ? $name : 'newscategory'.mt_rand(10000, 99999);
                $category_url = Utils::condense($str, true);
            }
            else {
                $category_url = (!empty($params['category_url'])) ? trim($params['category_url']) : null;
            }

            if ($category_url) {
                if ($category_url[0] == '/' || substr_compare($category_url, '/', -1, 1) == 0) {
                    $category_url = trim($category_url, '/ ');
                }
                //TODO other cleanup
            }

            $image_url = (!empty($params['image_url'])) ? trim($params['image_url']) : null;
            if ($image_url) {
                // TODO validate, cleanup this
            }

            $query = 'SELECT MAX(item_order) FROM '.CMS_DB_PREFIX.'module_news_categories WHERE parent_id = ?';
            $dbr = $db->getOne($query, [$parent]);
            $item_order = (int)$dbr + 1;
            $longnow = $db->DbTimeStamp(time(), false);
            $query = 'INSERT INTO '.CMS_DB_PREFIX.'module_news_categories
(news_category_name,
parent_id,
item_order,
category_url,
image_url,
create_date) VALUES(?,?,?,?,?,?)';
            $dbr = $db->execute($query, [
            $name,
            $parent,
            $item_order,
            $category_url,
            $image_url,
            $longnow]);
            if( $dbr ) {
                $catid = $db->Insert_ID();

                AdminOperations::UpdateHierarchyPositions();

                Events::SendEvent('News', 'NewsCategoryAdded', ['category_id'=>$catid, 'name'=>$name]);
                // put mention into the admin log
                log_info($catid, 'News category: '.$name, 'Added');

                $this->SetMessage($this->Lang('categoryadded'));
                $this->RedirectToAdminTab('groups');
            }
            else {
                $this->ShowErrors($this->Lang('error_detailed', $db->errorMsg()));
            }
        }
    }
    else {
        $this->ShowErrors($this->Lang('nonamegiven'));
    }
}
else {
    $name = '';
}

$categories = [-1 => $this->Lang('none')];
$tmp = Utils::get_category_list();
foreach( $tmp as $nm => $cid ) {
    $categories[(int)$cid] = specialize($nm);
}

$picker = Lone::get('ModuleOperations')->GetFilePickerModule();
$dir = $config['uploads_path'];
$userid = get_userid(false);
$tmp = $picker->get_default_profile($dir, $userid);
$profile = $tmp->overrideWith(['top'=>$dir, 'type'=>FileType::IMAGE]);
$text = $picker->get_html($id.'image_url', $image_url, $profile);

$parms = $params; //TODO any extras, any specialize()'s
unset($parms['action'], $parms['name']);

// pass it all to template for display

$tpl = $smarty->createTemplate($this->GetTemplateResource('editcategory.tpl')); //, null, null, $smarty);

$tpl->assign('formaction', 'addcategory')
 ->assign('formparms', $parms)
 ->assign('catid', $catid)
 ->assign('parent', $parentid)
 ->assign('name', $name)
 ->assign('category_url', $category_url)
 ->assign('categories', $categories)
 ->assign('filepicker', $text);
// associated image, if any
if( $image_url ) {
    $tpl->assign('image_url', CMS_UPLOADS_URL.'/'.trim($image_url, ' /'));
}
else {
   $tpl->assign('image_url', $image_url);
}

// page resources
require_once __DIR__.DIRECTORY_SEPARATOR.'method.categoryscript.php';

$tpl->display();
