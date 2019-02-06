<?php
# DesignManager module action: settings
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Site Preferences') ) return;

if( isset($params['submit']) ) {
  $n = (int)$params['lock_timeout'];
  if( $n != 0 ) $n = max(5,min(480,$n));
  $this->SetPreference('lock_timeout',$n);

  $n = (int)$params['lock_refresh'];
  if( $n != 0 ) $n = max(30,min(3540,$n));
  $this->SetPreference('lock_refresh',$n);

  $this->ShowMessage($this->Lang('msg_options_saved'));
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('admin_settings.tpl'),null,null,$smarty);
$tpl->assign('lock_timeout',$this->GetPreference('lock_timeout'))
 ->assign('lock_refresh',$this->GetPreference('lock_refresh'));

$tpl->display();
