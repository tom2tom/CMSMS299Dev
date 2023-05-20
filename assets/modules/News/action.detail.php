<?php
/*
CMSMS News module action: display a news item in detail mode.
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

use CMSMS\Error404Exception;
use CMSMS\TemplateOperations;
use News\Article;
use News\Utils;
use function CMSMS\log_error;

//if( some worthy test fails ) exit;

// initialization

$article = null; // no object yet
$preview = false;
$articleid = $params['articleid'] ?? -1;

if( !empty($params['detailtemplate']) ) {
    $template = trim($params['detailtemplate']);
}
else {
    $me = $this->GetName();
    $tpl = TemplateOperations::get_default_template_by_type($me.'::detail');
    if( !is_object($tpl) ) {
        log_error('No usable detail template found', $me.'::detail');
        $this->ShowErrorPage('No usable detail template found');
        return;
    }
    $template = $tpl->get_name();
}

if( $id == '_preview_' && isset($_SESSION['news_preview']) && isset($params['preview']) ) {
    // check whether our data matches
    if( md5(serialize($_SESSION['news_preview'])) == $params['preview'] ) {
        $fname = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.$_SESSION['news_preview']['fname'];
        if( is_file($fname) && (md5_file($fname) == $_SESSION['news_preview']['checksum']) ) {
            $data = unserialize(file_get_contents($fname), ['allowed_classes'=>false]);
            if( is_array($data) ) {
                // get passed data into a standard format
                $article = new Article();
                $article->set_linkdata($id,$params);
                Utils::fill_article_from_formparams($article, $data, false, false);
                $preview = true;
            }
        }
    }
}

if( isset($params['articleid']) ) {
    if( $params['articleid'] == -1 ) {
        $article = Utils::get_latest_article();
    }
    elseif( (int)$params['articleid'] > 0 ) {
        if( isset($params['showall']) ) {
            $show_expired = 1;
        }
        else {
            $show_expired = $this->GetPreference('expired_viewable', 1);
        }
        $article = Utils::get_article_by_id((int)$params['articleid'], true, $show_expired);
    }
    unset($params['articleid']);
}

if( !$article ) {
    throw new Error404Exception('Article '. (($articleid != -1) ? trim($articleid).' ' : '') . 'not found, or otherwise unavailable');
}
$article->set_linkdata($id, $params);

$return_url = $this->CreateReturnLink($id, ($params['origid'] ?? $returnid), $this->lang('news_return'));

if (isset($params['category_id'])) {
    $catName = $db->getOne('SELECT news_category_name FROM '.CMS_DB_PREFIX.'module_news_categories WHERE news_category_id=?', [(int)$params['category_id']]);
}
else {
    $catName = '';
}

$tpl = $smarty->createTemplate($this->GetTemplateResource($template)); //,'','',$smarty);

$tpl->assign('entry', $article)
 ->assign('return_url', $return_url)
 ->assign('category_name', $catName)
 ->assign('category_link', $this->CreateLink($id, 'default', $returnid, $catName, $params))
 ->assign('category_label', $this->Lang('category_label'))
 ->assign('author_label', $this->Lang('author_label'))
 ->assign('extra_label', $this->Lang('extra_label'));

// TODO other useful vars e.g. category longname, url, image ...

$tpl->display();
