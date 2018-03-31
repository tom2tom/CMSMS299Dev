<?php
#function to get page-content representing an admin icon
#Copyright (C) 2004-2018 Ted Kulp <ted@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

function smarty_function_admin_icon($params,&$template)
{
    if( !cmsms()->test_state(CmsApp::STATE_ADMIN_PAGE) ) return;

    $icon = null;
    $tagparms = ['class'=>'systemicon'];
    foreach( $params as $key => $value ) {
        switch( $key ) {
        case 'icon':
            $icon = trim($value);
            break;
        case 'width':
        case 'height':
        case 'alt':
        case 'rel':
        case 'class':
        case 'id':
        case 'name':
        case 'title':
        case 'accesskey':
            $tagparms[$key] = trim($value);
            break;
        case 'assign':
            break;
        }
    }

    if( !$icon ) return;

    if( !isset($tagparms['alt']) ) $tagparms['alt'] = basename($icon);
    $out = cms_admin_utils::get_icon($icon,$tagparms);
    if( !$out ) return;

    if( isset($params['assign']) ) {
        //TODO why global smarty instead of template ?
        $template->smarty->assign(trim($params['assign']),$out);
        return;
    }
    return $out;
}
