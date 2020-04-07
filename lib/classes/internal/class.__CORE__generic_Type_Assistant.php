<?php
#Class for getting template help
#Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS\internal;

use CMSMS\Layout\TemplateTypeAssistant;

/**
 * @since 2.2
 */
class __CORE__generic_Type_Assistant extends TemplateTypeAssistant
{
    public function &get_type()
    {
    }

    public function get_usage_string($name)
    {
        $name = trim($name);
        if( !$name ) return;
        $pattern = '{include file=\'cms_template:%s\'}';
        return sprintf($pattern,$name);
    }
}
