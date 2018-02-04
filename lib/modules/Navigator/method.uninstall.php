<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: Navigator (c) 2013 by Robert Campbell
#         (calguy1000@cmsmadesimple.org)
#  An module for CMS Made Simple to allow building hierarchical navigations.
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2005 by Ted Kulp (wishy@cmsmadesimple.org)
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
#$Id$

$this->RemovePreference();
$this->DeleteTemplate();
$this->RemoveSmartyPlugin();

try {
  $types = CmsLayoutTemplateType::load_all_by_originator('Navigator');
  foreach( $types as $type ) {
      try {
          $templates = $type->get_template_list();
          if( is_array($templates) && count($templates) ) {
              foreach( $templates as $tpl ) {
                  $tpl->delete();
              }
          }
      }
      catch( Exception $e ) {
          audit('',$this->GetName(),'Uninstall Error: '.$e->GetMessage());
      }
      $type->delete();
  }
}
catch( CmsException $e ) {
    // log it
    audit('',$this->GetName(),'Uninstall Error: '.$e->GetMessage());
    return FALSE;
}

#
# EOF
#
?>