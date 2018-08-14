<?php
#Class to resolve an issue with the Smarty_Resource_Custom class
#Copyright (C) 2004-2012 Ted Kulp <ted@cmsmadesimple.org>
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

use Smarty_Internal_Template;
use Smarty_Resource_Custom;
use Smarty_Template_Source;

/**
 * A simple class to resolve an issue with the Smarty_Resource_Custom class
 *
 * @since 1.11
 * @internal
 * @ignore
 * @package CMS
 */
abstract class fixed_smarty_custom_resource extends Smarty_Resource_Custom
{
    protected $smarty;

    public function populate(Smarty_Template_Source $source, Smarty_Internal_Template $_template = null)
    {
        $this->smarty = $source->smarty;  // hackish.
        $source->filepath = $source->type . ':' . $source->name;
        $source->uid = sha1($source->type . ':' . $source->name);

        $mtime = $this->fetchTimestamp($source->name);
        if ($mtime !== null) {
            $source->timestamp = $mtime;
        } else {
            $this->fetch($source->name, $content, $timestamp);
            $source->timestamp = $timestamp ?? false;
            if( isset($content) ) $source->content = $content;
        }
        $source->exists = !!$source->timestamp;
    }
}
