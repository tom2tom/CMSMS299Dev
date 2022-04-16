<?php
/*
Class for admin notifications
Copyright (C) 2014-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
BUT WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\internal;

use LogicException;

/**
 * A class representing a simple notification.
 *
 * @package CMS
 * @license GPL
 * @since 3.0
 * @since  1.11 as CmsAdminThemeNotification
 * @deprecated since 3.0
 * @author  Robert Campbell
 * @property string $module Module name
 * @property int $priority Priority between 1 and 3
 * @property string $html HTML contents of the notification
 */
class AdminNotification
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
    public function __get(string $key)
    {
        switch( $key ) {
        case 'module':
        case 'priority':
        case 'html':
            return $this->$key;
        }
        throw new LogicException("'$key' is not a valid property of ".__CLASS__);
    }


    /**
     * @ignore
     */
    public function __set(string $key,$value)
    {
        switch( $key ) {
        case 'module':
        case 'priority':
        case 'html':
            $this->$key = $value;
            return;
        }
        throw new LogicException("'$key' is not a valid property of ".__CLASS__);
    }
} // class
