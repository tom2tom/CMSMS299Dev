<?php
/*
TemplateTypeAssistant abstract class
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple. 
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

/**
 * An abstract class to define an assistant to the template-type objects in the database.
 *
 * @package CMS
 * @license GPL
 * @since 2.99
 * @since 2.2 in namespace CMSMS\Layout
 */
abstract class TemplateTypeAssistant
{
    /**
     * Get the type object for the current assistant.
     *
     * @return TemplateType
     */
    abstract public function get_type();

    /**
     * Get a usage string for the current assistant.
     *
     * @param string $name The template name.
     * @return string
     */
    abstract public function get_usage_string($name);
}
