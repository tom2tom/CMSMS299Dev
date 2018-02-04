<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: ModuleManager (c) 2008 by Robert Campbell
#         (calguy1000@cmsmadesimple.org)
#  An addon module for CMS Made Simple to allow browsing remotely stored
#  modules, viewing information about them, and downloading or upgrading
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2005 by Ted Kulp (wishy@cmsmadesimple.org)
# Visit our homepage at: http://www.cmsmadesimple.org
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
if (!isset($gCms)) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) exit;

if( isset($config['developer_mode']) ) {
  $smarty->assign('developer_mode',1);
  $smarty->assign('module_repository',$this->GetPreference('module_repository'));
  $smarty->assign('disable_caching',$this->GetPreference('disable_caching',0));
}
$smarty->assign('dl_chunksize',$this->GetPreference('dl_chunksize',256));
$smarty->assign('latestdepends',$this->GetPreference('latestdepends',1));
$smarty->assign('allowuninstall',$this->GetPreference('allowuninstall',0));

echo $this->ProcessTemplate('adminprefs.tpl');

#
# EOF
#
?>