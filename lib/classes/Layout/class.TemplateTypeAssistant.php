<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: CmsLayoutTemplateType (c) 2013 by Robert Campbell
#         (calguy1000@cmsmadesimple.org)
#  A class to manage template types.
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

/**
 * This file defines the TemplateTypeAssistant abstract class.
 *
 * @package CMS
 * @license GLP
 */
namespace CMSMS\Layout;

/**
 * An abstract class to define an assistant to the template type objects in the database.
 *
 * @package CMS
 * @license GPL
 * @since 2.2
 * @author Robert Campbell <calguy1000@gmail.com>
 */
abstract class TemplateTypeAssistant
{
    /**
     * Get the type object for the current assistant.
     *
     * @return CmsLayoutTemplateType
     */
    abstract public function &get_type();

    /**
     * Get a usage string for the current assistant.
     *
     * @param string $name The template name.
     * @return string
     */
    abstract public function get_usage_string($name);
}