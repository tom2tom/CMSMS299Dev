<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: Content (c) 2013 by Robert Campbell
#         (calguy1000@cmsmadesimple.org)
#  A module for managing content in CMSMS.
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2004 by Ted Kulp (wishy@cmsmadesimple.org)
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#-------------------------------------------------------------------------
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
#END_LICENSE
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

$smarty->assign('locktimeout',$this->GetPreference('locktimeout'));
$smarty->assign('lockrefresh',$this->GetPreference('lockrefresh'));

$opts = array('all'=>$this->Lang('opt_alltemplates'),'alldesign'=>$this->Lang('opt_alldesign'),'allpage'=>$this->Lang('opt_allpage'),'designpage'=>$this->Lang('opt_designpage'));
$smarty->assign('template_list_opts',$opts);
$smarty->assign('template_list_mode',$this->GetPreference('template_list_mode','designpage'));
echo $this->ProcessTemplate('admin_general_tab.tpl');

#
# EOF
#
?>