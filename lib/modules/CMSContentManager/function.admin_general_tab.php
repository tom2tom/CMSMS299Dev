<?php
# CMSContentManager settings action tab
# Copyright (C) 2013-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

$opts = [
 'all'=>$this->Lang('opt_alltemplates'),
 'alldesign'=>$this->Lang('opt_alldesign'),
 'allpage'=>$this->Lang('opt_allpage'),
 'designpage'=>$this->Lang('opt_designpage')
];

$tpl->assign('locktimeout',$this->GetPreference('locktimeout'))
 ->assign('lockrefresh',$this->GetPreference('lockrefresh'))
 ->assign('template_list_opts',$opts)
 ->assign('template_list_mode',$this->GetPreference('template_list_mode','designpage'));
