<?php
/*
Class definition and methods for Section Header content type
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple. 
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\contenttypes;

use CMSMS\contenttypes\ContentBase;

/**
 * Implements the Section Header content type
 *
 * Section headers are logical ways to organize content.  They usually appear in navigations, but are not navigable.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class SectionHeader extends ContentBase
{
	// NOTE any private or static property will not be serialized

	/**
	 * @param mixed $params
	 */
	public function __construct($params)
	{
		parent::__construct($params);
		foreach ([
			'accesskey' => '',
			'cachable' => true,
			'secure' => false, //deprecated property since 3.0
			'page_url' => '',
			'target' => '',
		] as $key => $value) {
			$this->$key = $value;
		}
	}

	public function GetURL(bool $rewrite = true) : string { return '#'; }
	public function HasSearchableContent() : bool { return false; }
	public function HasUsableLink() : bool { return false; }
}

//backward-compatibility shiv
\class_alias(SectionHeader::class, 'SectionHeader', false);
