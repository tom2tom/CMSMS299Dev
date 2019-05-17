<?php
# CMSContentManager module action: get design-related page content via ajax
# Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\TemplateOperations;

if( !isset($gCms) ) exit;
if( !$this->CanEditContent() ) exit;

$out = null;
try {
/*
    $design_id = (int) get_parameter_value($params,'design_id',-1);
    if( $design_id > 0 ) {
        $mode = $this->GetPreference('template_list_mode','allpage');
        switch( $mode ) {
        case 'alldesign':
            // all templates for this design
            $design = CmsLayoutCollection::load($design_id); DISABLED
            $template_list = $design->get_templates();

            $templates = TemplateOperations::get_bulk_templates($template_list);
            $out = [];
            foreach( $templates as $one ) {
                if( !$one->get_listable() ) continue;
                $out[$one->get_id()] = $one->get_name(); //TODO CHECKME switch order?
            }
            break;

        case 'designpage':
            $type = CmsLayoutTemplateType::load(CmsLayoutTemplateType::CORE.'::page');
            $type_id = $type->get_id();
            $design = CmsLayoutCollection::load($design_id); DISABLED
            $template_list = $design->get_templates();

            $templates = TemplateOperations::get_bulk_templates($template_list);
            if( $templates ) {
                $out = [];
                foreach( $templates as $one ) {
                    if( $one->get_type_id() != $type_id ) continue;
                    if( !$one->get_listable() ) continue;
                    $out[$one->get_id()] = $one->get_name();
                }
            }
            break;

        case 'allpage':
*/
            $type = CmsLayoutTemplateType::load(CmsLayoutTemplateType::CORE.'::page');
            $template_list = TemplateOperations::get_all_templates_by_type($type);
            $out = [];
            foreach( $template_list as $one ) {
                if( !$one->get_listable() ) continue;
                $out[$one->get_id()] = $one->get_name();
            }
//            break;
//        }
    }
//}
catch( Throwable $t ) {
    $out = null;
}

if( !$out ) $out = null;
echo json_encode($out);
exit;
