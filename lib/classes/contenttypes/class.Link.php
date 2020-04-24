<?php
#CMS Made Simple link content type
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS\contenttypes;

use CMSMS\contenttypes\ContentBase;

/**
 * Implements the Link content type
 *
 * Links are content objects that appear in navigations and implement a
 * link to an external page or site.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class Link extends ContentBase
{
	// NOTE any private or static property will not be serialized

	/**
	 * @param mixed $params
	 */
	public function __construct($params)
	{
		parent::__construct($params);
		foreach ([
			'cachable' => true,
			'secure' => false, //deprecated property since 2.3
//redundant	'type' => 'link',
		] as $key => $value) {
			$this->$key = $value;
		}
	}

	public function HasSearchableContent() : bool { return false; }

	public function GetURL(bool $rewrite = true) : string
	{
		return (string)$this->GetPropertyValue('url');
		//return cms_htmlentities($this->GetPropertyValue('url'));
	}
}

//backward-compatibility shiv
\class_alias(Link::class, 'Link', false);
