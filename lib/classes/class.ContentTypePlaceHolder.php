<?php
#Convenience class to hold CMS Content Type structure.
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

namespace CMSMS;

/**
 * Convenience class to hold CMS Content Type data
 *
 * @package CMS
 */
class ContentTypePlaceHolder
{
	/**
	 * @var string The type name
	 */
	public $type;

	/**
	 * @var string The filename containing the type class
	 */
	public $filename;

	/**
	 * @var string A friendly name for the type
	 */
	public $friendlyname;

	/**
	 * @var Whether the type has been loaded
	 */
	public $loaded;
} //class
