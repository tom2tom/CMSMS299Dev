<?php
#-------------------------------------------------------------------------
# Module: AdminSearch - A CMSMS addon module to provide admin side search capbilities.
# Copyright (C) 2012-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
#
#-------------------------------------------------------------------------
if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) return;

$template = get_parameter_value( $params, 'template', 'defaultadmin.tpl' );
$url = $this->create_url($id,'admin_search');
$url = str_replace('&amp;','&',$url).'&showtemplate=false';
$smarty->assign('ajax_url',$url);
$smarty->assign('js_url',$this->GetModuleURLPath().'/lib/admin_search_tab.js');

$userid = get_userid();
$tmp = get_preference($userid,$this->GetName().'saved_search');
if( $tmp ) $smarty->assign('saved_search',unserialize($tmp));

$slaves = AdminSearch_tools::get_slave_classes();
$smarty->assign('slaves',$slaves);

echo $this->ProcessTemplate($template);
#
# EOF
#
