<?php
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

use CMSContentManager\ContentListBuilder;
use CMSContentManager\Utils;
use CMSMS\internal\bulkcontentoperations;

if( !isset($gCms) ) exit;
// no permissions checks here.

$handlers = ob_list_handlers();
for ($cnt = 0, $n = sizeof($handlers); $cnt < $n; $cnt++) { ob_end_clean(); }

try {
    $tpl = $smarty->createTemplate( $this->GetTemplateResource( 'ajax_get_content.tpl' ),null,null,$smarty );

    $tpl->assign('can_add_content',$this->CheckPermission('Add Pages') || $this->CheckPermission('Manage All Content'))
     ->assign('can_reorder_content',$this->CheckPermission('Manage All Content'))
     ->assign('template_list',CmsLayoutTemplate::template_query(['as_list'=>1])); // this is just to aide loading.

    // load all the content that this user can display...
    // organize it into a tree
    $builder = new ContentListBuilder($this);
    $curpage = (isset($_SESSION[$this->GetName().'_curpage']) && !isset($params['seek'])) ? (int) $_SESSION[$this->GetName().'_curpage'] : 1;
    if( isset($params['curpage']) ) $curpage = (int)$params['curpage'];
    $filter = cms_userprefs::get($this->GetName().'_userfilter');
    if( $filter ) {
        $filter = unserialize($filter);
        $builder->set_filter($filter);
    }
    $tpl->assign('have_filter',is_object($filter));

    //
    // handle all of the possible ajaxy/sub actions.
    //

    //
    // build the display
    //
    $tpl->assign('prettyurls_ok',$builder->pretty_urls_configured());

    if( isset($params['setoptions']) ) cms_userprefs::set($this->GetName().'_pagelimit',(int)$params['pagelimit']);
    $pagelimit = cms_userprefs::get($this->GetName().'_pagelimit',100);

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
    $have_locks = (is_array($locks) && count($locks))?1:0;
    $tpl->assign('locking',Utils::locking_enabled())
     ->assign('have_locks',$have_locks)
     ->assign('pagelimit',$pagelimit)
     ->assign('pagelist',$pagelist)
     ->assign('curpage',$builder->get_page())
     ->assign('npages',$npages)
     ->assign('multiselect',$builder->supports_multiselect())
     ->assign('columns',$builder->get_display_columns());
    $url = $this->create_url($id,'ajax_get_content',$returnid);
    $tpl->assign('ajax_get_content_url',str_replace('amp;','',$url))
	  ->assign('settingsicon',cms_join_path(__DIR__,'images','settings'));

    if( Utils::get_pagenav_display() == 'title' ) {
        $tpl->assign('colhdr_page',$this->Lang('colhdr_name'))
         ->assign('coltitle_page',$this->Lang('coltitle_name'));
    }
    else {
        $tpl->assign('colhdr_page',$this->Lang('colhdr_menutext'))
         ->assign('coltitle_page',$this->Lang('coltitle_menutext'));
    }
    if( $editinfo ) $tpl->assign('content_list',$editinfo);
    if( $filter && !$editinfo ) {
        $tpl->assign('error',$this->Lang('err_nomatchingcontent'));
    }

    $opts = [];
    if( $this->CheckPermission('Remove Pages') && $this->CheckPermission('Modify Any Page') ) {
        bulkcontentoperations::register_function($this->Lang('bulk_delete'),'delete');
    }

    if( $this->CheckPermission('Manage All Content')) {
        bulkcontentoperations::register_function($this->Lang('bulk_active'),'active');
        bulkcontentoperations::register_function($this->Lang('bulk_inactive'),'inactive');
        bulkcontentoperations::register_function($this->Lang('bulk_cachable'),'setcachable');
        bulkcontentoperations::register_function($this->Lang('bulk_noncachable'),'setnoncachable');
        bulkcontentoperations::register_function($this->Lang('bulk_showinmenu'),'showinmenu');
        bulkcontentoperations::register_function($this->Lang('bulk_hidefrommenu'),'hidefrommenu');
        bulkcontentoperations::register_function($this->Lang('bulk_setdesign'),'setdesign');
        bulkcontentoperations::register_function($this->Lang('bulk_changeowner'),'changeowner');
    }
    $opts = bulkcontentoperations::get_operation_list();
    if( is_array($opts) && count($opts) ) $tpl->assign('bulk_options',$opts);

    //TODO ensure flexbox css for .hbox, .boxchild

    $tpl->display();
}
catch( Exception $e ) {
    echo '<div class="error">'.$e->GetMessage().'</div>';
    debug_to_log($e);
}
exit;
