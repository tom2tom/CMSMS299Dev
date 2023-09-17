<?php
/*
Edit category action for CMSMS News module.
Copyright (C) 2005-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Modify News Preferences') ) exit;

if( isset($params['cancel']) ) {
    $this->RedirectToAdminTab('groups');
}

if( isset($params['catid']) ) {
    $catid = (int)$params['catid']; //int() is sufficient san. here
    if( $catid > 0 ) {
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_id = ?';
        $row = $db->getRow($query,[$catid]);
    }
    else {
        $row = false;
    }
    if( !$row ) {
        $this->SetError($this->Lang('error_categorynotfound'));
        $this->RedirectToAdminTab('groups');
    }
    //current values
    $name = $row['news_category_name'];
    $parentid = (int)$row['parent_id'];
    $category_url = $row['category_url'];
    $image_url = $row['image_url'];
}
else {
    $catid = 0;
    $category_url = '';
    $row = [];
    $name = '';
    $parentid = -1;
    $image_url = '';
}

if( isset($params['submit']) ) {
    $parentid = (int)$params['parent'];
    $name = trim($params['name']);
    if( $name == '' ) {
        $this->ShowErrors($this->Lang('nonamegiven'));
    }
    else {
        // it's an update
        $name = de_specialize($name);
        $name = sanitzeVal($name, CMSSAN_PUNCTX, '<>'); // TODO what content allowed in cat names?
        $query = 'SELECT news_category_id FROM '.CMS_DB_PREFIX.
          'module_news_categories WHERE parent_id = ? AND news_category_name = ? AND news_category_id != ?';
        $tmp = $db->getOne($query,[$parentid,$name,$catid]);
        if( $tmp ) {
            $this->ShowErrors($this->Lang('error_duplicatename'));
        }
        else {
            if( $parentid == $catid ) {
                $this->ShowErrors($this->Lang('error_categoryparent'));
            }
            elseif( $row && $parentid != $row['parent_id'] ) {
                // parent changed
                // gotta figure out a new item order
                $query = 'SELECT max(item_order) FROM '.CMS_DB_PREFIX.
                  'module_news_categories WHERE parent_id = ?';
                $maxn = (int)$db->getOne($query,[$parentid]);
                $maxn++;

                $query = 'UPDATE '.CMS_DB_PREFIX.
                  'module_news_categories SET item_order = item_order - 1 WHERE parent_id = ? AND item_order > ?';
                $db->execute($query,[$row['parent_id'],$row['item_order']]);
                $row['item_order'] = $maxn;
            }

            $origname = $db->getOne('SELECT news_category_name FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_id = ?', [$catid]);

            if( isset($params['generate_url']) && cms_to_bool($params['generate_url']) ) {
                $str = ( $name ) ? $name : 'newscategory'.$catid.mt_rand(1000, 9999);
                $category_url = Utils::condense($str, true);
            }
            else {
                $category_url = (!empty($params['category_url'])) ? trim($params['category_url']) : null;
            }

            if( $category_url ) {
                if( $category_url[0] == '/' || substr_compare($category_url,'/',-1,1) == 0 ) {
                    $category_url = trim($category_url, '/ ');
                }
                //TODO other cleanup
            }

            $image_url = (!empty($params['image_url'])) ? trim($params['image_url']) : null;
            if( $image_url ) {
                // TODO validate, cleanup this
            }

            $query = 'UPDATE '.CMS_DB_PREFIX.'module_news_categories SET
news_category_name = ?,
parent_id = ?,
item_order = ?,
category_url = ?,
image_url = ?,
modified_date = ?
WHERE news_category_id = ?';
            $longnow = $db->DbTimeStamp(time(),false);
            $parms = [
            $name,
            $parentid,
            $row['item_order'],
            $category_url,
            $image_url,
            $longnow,
            $catid];
            $db->execute($query,$parms);
            if( $db->errorNo() > 0 ) {
                // TODO handle error
                $this->ShowErrors($db->errorMsg());
            }
            else {
                AdminOperations::UpdateHierarchyPositions();

                Events::SendEvent('News','NewsCategoryEdited',['category_id'=>$catid,'name'=>$name,'origname'=>$origname]);
                // put mention into the admin log
                log_info($catid,'News category: '.$name,'Edited');

                $this->SetMessage($this->Lang('categoryupdated'));
                $this->RedirectToAdminTab('groups');
            }
        }
    }
}

$tmp = Utils::get_category_list();
$tmp2 = array_flip($tmp);
$categories = [-1=>$this->Lang('none')];
foreach( $tmp2 as $k => $v ) {
    if( $k == $catid ) continue;
    $categories[$k] = $v;
}
$parms = ['catid'=>$catid];
//CHECKME secure params too?

$picker = Lone::get('ModuleOperations')->GetFilePickerModule();
$dir = $config['uploads_path'];
$userid = get_userid(false);
$tmp = $picker->get_default_profile($dir,$userid);
$profile = $tmp->overrideWith(['top'=>$dir,'type'=>FileType::IMAGE]);
$text = $picker->get_html($id.'image_url',$image_url,$profile);

// pass it all to template for display

$tpl = $smarty->createTemplate($this->GetTemplateResource('editcategory.tpl')); //,'','',$smarty);

$tpl->assign('formaction','editcategory')
 ->assign('formparms',$parms)
 ->assign('catid',$catid)
 ->assign('parent',$parentid)
 ->assign('name',$name)
 ->assign('category_url',$category_url)
 ->assign('categories',$categories)
 ->assign('filepicker',$text);
// associated image, if any
if( $image_url ) {
    $tpl->assign('image_url',CMS_UPLOADS_URL.'/'.trim($image_url,' /'));
}
else {
    $tpl->assign('image_url',$image_url);
}

// page resources
require_once __DIR__.DIRECTORY_SEPARATOR.'method.categoryscript.php';

$tpl->display();
