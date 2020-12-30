<?php
/*
Class definition and methods for Page Link content type
Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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
namespace CMSMS\contenttypes;

use CMSMS\contenttypes\ContentBase;
use CMSMS\PageLoader;

/**
 * Implements the PageLink content type.
 *
 * This content type simply provides a way to manage additional links to internal content pages
 * that may be in another place in the page hierarchy.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class PageLink extends ContentBase
{
	// NOTE any private or static property will not be serialized

	/**
	 * @param mixed $params
	 */
	public function __construct($params)
	{
		parent::__construct($params);
		foreach ([
			'cachable' => false,
			'secure' => false, //deprecated property since 2.99
		] as $key => $value) {
			$this->$key = $value;
		}
		if (isset($this->_fields['content_id'])) {
			// not constructing: load props for GetURL()
			$this->_load_properties();
		}
	}

	public function HasSearchableContent() : bool { return false; }

	/**
	 * Construct an URL for the destination page
	 * @param bool $rewrite Default true.
	 * @return string
	 */
	public function GetURL(bool $rewrite = true) : string
	{
		$page = $this->GetPropertyValue('page');
		$content = PageLoader::LoadContent($page);
		if( is_object( $content ) ) {
			$url = $content->GetURL($rewrite);
			$params = $this->GetPropertyValue('params');
			return $url . $params;
		}
		return '';
	}
}

//backward-compatibility shiv
\class_alias(PageLink::class, 'PageLink', false);
