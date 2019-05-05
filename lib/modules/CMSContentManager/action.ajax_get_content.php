<?php
# CMSContentManger module action: ajax_get_content
# Copyright (C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSContentManager\bulkcontentoperations;
use CMSContentManager\ContentListBuilder;
use CMSContentManager\Utils;
use CMSMS\FormUtils;

if( !empty($firstlist) ) {
    $ajax = false;
    //and we'll use the template initiated upstream
}
else {
    // we're doing an ajax-refresh, not initial display via defaultadmin action
    if( !isset($gCms) ) exit;
    // no permissions checks here.

    $handlers = ob_list_handlers();
    for ($cnt = 0,$n = count($handlers); $cnt < $n; $cnt++) { ob_end_clean(); }

    $tpl = $smarty->createTemplate( $this->GetTemplateResource( 'ajax_get_content.tpl' ),null,null,$smarty );
    $ajax = true;
}

$pmanage = $this->CheckPermission('Manage All Content');
$tpl->assign('can_manage_content',$pmanage)
 ->assign('can_reorder_content',$pmanage)
 ->assign('can_add_content',$pmanage || $this->CheckPermission('Add Pages'));

$theme = cms_utils::get_theme_object();
$builder = new ContentListBuilder($this);

try {
    // load all the content that this user can display...
    // organize it into a tree
    $modname = $this->GetName();
    $curpage = (isset($_SESSION[$modname.'_curpage']) && !isset($params['seek'])) ? (int)$_SESSION[$modname.'_curpage'] : 1;
    if( isset($params['curpage']) ) {
        $curpage = (int)$params['curpage'];
    }
    $filter = cms_userprefs::get($modname.'_userfilter');
    if( $filter ) {
        $filter = unserialize($filter);
        $builder->set_filter($filter);
    }
    $tpl->assign('have_filter',is_object($filter))
     ->assign('filter',$filter)
     ->assign('filterimage',cms_join_path(__DIR__,'images','filter')); //TODO use new admin-theme icon

    //
    // build the display
    //
    $tpl->assign('prettyurls_ok',$builder->pretty_urls_configured());

    if( isset($params['setoptions']) ) {
        cms_userprefs::set($modname.'_pagelimit',(int)$params['pagelimit']);
    }
    $pagelimit = cms_userprefs::get($modname.'_pagelimit',100);

    $builder->set_pagelimit($pagelimit);
    if( isset($params['seek']) && $params['seek'] != '' ) {
        $builder->seek_to((int)$params['seek']);
    }
    else {
        $builder->set_page($curpage);
    }

    $editinfo = $builder->get_content_list();
    $npages = $builder->get_numpages();
    $pagelist = [];
    for( $i = 0; $i < $npages; $i++ ) {
        $pagelist[$i+1] = $i+1;
    }

    $tpl->assign('indent',!$filter && cms_userprefs::get('indent',1));
    $locks = $builder->get_locks();
    $have_locks = ($locks) ? 1 : 0;

    $tpl->assign('locking',Utils::locking_enabled())
     ->assign('have_locks',$have_locks)
     ->assign('pagelimit',$pagelimit)
     ->assign('pagelimits',[10=>10,25=>25,100=>100,250=>250,500=>500])
     ->assign('pagelist',$pagelist)
     ->assign('curpage',$builder->get_page())
     ->assign('npages',$npages)
     ->assign('multiselect',$builder->supports_multiselect())
     ->assign('columns',$builder->get_display_columns());
/*
    $url = $this->create_url($id,'ajax_get_content',$returnid);
    $tpl->assign('ajax_get_content_url',str_replace('amp;','',$url))
      ->assign('settingsicon',cms_join_path(__DIR__,'images','settings'));
*/
    if( Utils::get_pagenav_display() == 'title' ) {
        $tpl->assign('colhdr_page',$this->Lang('colhdr_pagetitle'))
         ->assign('coltitle_page',$this->Lang('coltitle_name'));
    }
    else {
        $tpl->assign('colhdr_page',$this->Lang('colhdr_menutext'))
         ->assign('coltitle_page',$this->Lang('coltitle_menutext'));
    }

    if( $editinfo ) {
        $u = $this->create_url($id,'defaultadmin',$returnid,['moveup'=>'XXX']);
        $t = $this->Lang('prompt_page_sortup');
        $icon = $theme->DisplayImage('icons/system/arrow-u',$t,'','','systemicon');
        $linkup = '<a href="'.$u.'" class="page_sortup" accesskey="m">'.$icon.'</a>'."\n";

        $u = $this->create_url($id,'defaultadmin',$returnid,['movedown'=>'XXX']);
        $t = $this->Lang('prompt_page_sortdown');
        $icon = $theme->DisplayImage('icons/system/arrow-d',$t,'','','systemicon');
        $linkdown = '<a href="'.$u.'" class="page_sortdown" accesskey="m">'.$icon.'</a>'."\n";

        $t = $this->Lang('prompt_page_view');
        $icon = $theme->DisplayImage('icons/system/view',$t,'','','systemicon');
        $linkview = '<a target="_blank" href="XXX" class="page_view" accesskey="v">'.$icon.'</a>'."\n";

        $u = $this->create_url($id,'admin_copycontent',$returnid,['page'=>'XXX']);
        $t = $this->Lang('prompt_page_copy');
        $icon = $theme->DisplayImage('icons/system/copy',$t,'','','systemicon page_copy');
        $linkcopy = '<a href="'.$u.'" accesskey="o">'.$icon.'</a>'."\n";

        $u = $this->create_url($id,'admin_editcontent',$returnid,['content_id'=>'XXX']);
        $t = $this->Lang('prompt_page_edit');
        $icon = $theme->DisplayImage('icons/system/edit',$t,'','','systemicon page_edit');
        $linkedit = '<a href="'.$u.'" class="page_edit" accesskey="e" data-cms-content="XXX">'.$icon.'</a>'."\n";

        $u = str_replace('XXX','%s',$u).'&m1_steal=1'; //sprintf template
        $tpl->assign('stealurl',$u);

        $u = $this->create_url($id,'admin_editcontent',$returnid,['parent_id'=>'XXX']);
        $t = $this->Lang('prompt_page_addchild');
        $icon = $theme->DisplayImage('icons/system/newobject',$t,'','','systemicon page_addchild');
        $linkchild = '<a href="'.$u.'" class="page_edit" accesskey="a">'.$icon.'</a>'."\n";

        $u = $this->create_url($id,'defaultadmin',$returnid,['delete'=>'XXX']);
        $t = $this->Lang('prompt_page_delete');
        $icon = $theme->DisplayImage('icons/system/delete',$t,'','','systemicon page_delete');
        $linkdel = '<a href="'.$u.'" class="page_delete" accesskey="r">'.$icon.'</a>'."\n";

        $menus = [];
        foreach( $editinfo as $row ) {
            $acts = [];
            $rid = $row['id'];
            if( isset($row['move']) ) {
                if( $row['move'] == 'up' ) {
                    $acts[] = ['content'=>str_replace('XXX',$rid,$linkup)];
                }
                elseif( $row['move'] == 'down' ) {
                    $acts[] = ['content'=>str_replace('XXX',$rid,$linkdown)];
                }
                elseif( $row['move'] == 'both' ) {
                    $acts[] = ['content'=>str_replace('XXX',$rid,$linkup)];
                    $acts[] = ['content'=>str_replace('XXX',$rid,$linkdown)];
                }
            }
            if( $row['viewable'] ) {
                $acts[] = ['content'=>str_replace('XXX',$row['view'],$linkview)];
            }
            if( $row['copy'] ) {
                $acts[] = ['content'=>str_replace('XXX',$rid,$linkcopy)];
            }
            if( $row['can_edit'] ) {
                $acts[] = ['content'=>str_replace('XXX',$rid,$linkedit)];
            }
            //always add child
            $acts[] = ['content'=>str_replace('XXX',$rid,$linkchild)];

            if( $row['can_delete'] && $row['delete'] ) {
                $acts[] = ['content'=>str_replace('XXX',$rid,$linkdel)];
            }
            $menus[] = FormUtils::create_menu($acts,['id'=>'Page'.$rid,'class'=>CMS_POPUPCLASS]);
        }

        $tpl->assign('content_list',$editinfo)
         ->assign('menus',$menus);
    }

    if( $filter && !$editinfo ) {
        $tpl->assign('error',$this->Lang('err_nomatchingcontent'));
    }

    if( $pmanage ) {
        bulkcontentoperations::register_function($this->Lang('bulk_active'),'active');
        bulkcontentoperations::register_function($this->Lang('bulk_inactive'),'inactive');
        bulkcontentoperations::register_function($this->Lang('bulk_showinmenu'),'showinmenu');
        bulkcontentoperations::register_function($this->Lang('bulk_hidefrommenu'),'hidefrommenu');
        bulkcontentoperations::register_function($this->Lang('bulk_cachable'),'setcachable');
        bulkcontentoperations::register_function($this->Lang('bulk_noncachable'),'setnoncachable');
        bulkcontentoperations::register_function($this->Lang('bulk_changeowner'),'changeowner');
        bulkcontentoperations::register_function($this->Lang('bulk_setstyles'),'setstyles');
        bulkcontentoperations::register_function($this->Lang('bulk_settemplate'),'settemplate');
    }

    if( $pmanage || ($this->CheckPermission('Remove Pages') && $this->CheckPermission('Modify Any Page')) ) {
        bulkcontentoperations::register_function($this->Lang('bulk_delete'),'delete');
    }

    $bulks = bulkcontentoperations::get_operation_list();
    if( $bulks ) {
        $tpl->assign('bulk_options',$bulks);
    }

    if( $ajax ) {
        $tpl->display();
        exit;
    }
}
catch( Throwable $t ) {
    debug_to_log($e);
    echo '<div class="error">'.$t->getMessage().'</div>';
    if( $ajax ) {
        exit;
    }
}
