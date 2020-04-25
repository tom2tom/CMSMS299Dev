<?php
/*
CMSMS News module action: display a news item in detail mode.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\TemplateOperations;
use News\Article;
use News\Utils;

if( !isset($gCms) ) exit;

//
// initialization
//
$query = null;
$article = null;
$preview = false;
$articleid = $params['articleid'] ?? -1;

if( isset($params['detailtemplate']) ) {
    $template = trim($params['detailtemplate']);
}
else {
    $me = $this->GetName();
    $tpl = TemplateOperations::get_default_template_by_type($me.'::detail');
    if( !is_object($tpl) ) {
        audit('',$me,'No usable detail template found');
        return '';
    }
    $template = $tpl->get_name();
}

if( $id == '_preview_' && isset($_SESSION['news_preview']) && isset($params['preview']) ) {
    // see if our data matches.
    if( md5(serialize($_SESSION['news_preview'])) == $params['preview'] ) {
        $fname = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.$_SESSION['news_preview']['fname'];
        if( is_file($fname) && (md5_file($fname) == $_SESSION['news_preview']['checksum']) ) {
            $data = unserialize(file_get_contents($fname), ['allowed_classes'=>false]);
            if( is_array($data) ) {
                // get passed data into a standard format.
                $article = new Article();
                $article->set_linkdata($id,$params);
                Utils::fill_article_from_formparams($article,$data,false,false);
                $preview = true;
            }
        }
    }
}

if( isset($params['articleid']) && $params['articleid'] == -1 ) {
    $article = Utils::get_latest_article();
}
elseif( isset($params['articleid']) && (int)$params['articleid'] > 0 ) {
    if( isset($params['showall']) ) {
        $show_expired = 1;
    }
    else {
        $show_expired = $this->GetPreference('expired_viewable',1);
    }
    $article = Utils::get_article_by_id((int)$params['articleid'],true,$show_expired);
}
if( !$article ) {
    throw new CmsError404Exception('Article '.(int)$params['articleid'].' not found, or otherwise unavailable');
}
$article->set_linkdata($id,$params);

$return_url = $this->CreateReturnLink($id, isset($params['origid'])?$params['origid']:$returnid, $this->lang('news_return'));

$tpl = $smarty->createTemplate($this->GetTemplateResource($template),null,null,$smarty);
$tpl->assign('return_url', $return_url)
 ->assign('entry', $article);

if (isset($params['category_id'])) {
    $catName = $db->GetOne('SELECT news_category_name FROM '.CMS_DB_PREFIX . 'module_news_categories where news_category_id=?',[(int)$params['category_id']]);
}
else {
    $catName = '';
}
$tpl->assign('category_name',$catName);

unset($params['article_id']);

$tpl->assign('category_link',$this->CreateLink($id, 'default', $returnid, $catName, $params))
 ->assign('category_label', $this->Lang('category_label'))
 ->assign('author_label', $this->Lang('author_label'))
 ->assign('extra_label', $this->Lang('extra_label'));

$tpl->display();
return '';
