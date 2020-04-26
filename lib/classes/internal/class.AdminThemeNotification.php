<?php
#Class for admin notifications
#Copyright (C) 2014-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#BUT WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS\internal;

use CmsInvalidDataException;

/**
 * A class representing a simple notification.
 *
 * @package CMS
 * @license GPL
 * @since   1.11
 * @deprecated since 2.3
 * @author  Robert Campbell
 * @property string $module Module name
 * @property int $priority Priority between 1 and 3
 * @property string $html HTML contents of the notification
 */
class AdminThemeNotification
{
    /**
     * @ignore
     */
    private $_module;

    /**
     * @ignore
     */
    private $_priority;

    /**
     * @ignore
     */
    private $_html;


    /**
     * @ignore
     */
    public function __get($key)
    {
        switch( $key ) {
        case 'module':
        case 'priority':
        case 'html':
            return $this->$key;
        }

        throw new CmsInvalidDataException('Attempt to retrieve invalid property from CmsAdminThemeNotification');
    }


    /**
     * @ignore
     */
    public function __set($key,$value)
    {
        switch( $key ) {
        case 'module':
        case 'priority':
        case 'html':
            $this->$key = $value;
            return;
        }

        throw new CmsInvalidDataException('Attempt to set invalid property from CmsAdminThemeNotification');
    }
} // class

\class_alias(AdminThemeNotification::class, 'CMSMS\internal\AdminNotification', false);
