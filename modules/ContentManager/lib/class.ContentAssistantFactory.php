<?php
/*
Class: ContentAssistantFactory
Copyright (C) 2013-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
namespace ContentManager;

use ContentManager\ContentBase;
use Exception;

class ContentAssistantFactory
{
	private $_content_obj;

	public function __construct(ContentBase $content_obj)
	{
		$this->_content_obj = $content_obj;
	}

	/**
	 *
	 * @return object
	 * @throws Exception
	 */
	public function getEditContentAssistant()
	{
		$classname = get_class($this->_content_obj);
		$n = 0;
		while ($n < 10) {
			++$n;
			$test = $classname.'EditContentAssistant';
			if (class_exists($test)) {
				$obj = new $test($this->_content_obj);
				return $obj;
			}
			$classname = get_parent_class($classname);
			if (!$classname) {
				$obj = null;
				return $obj;
			}
		}
		throw new Exception('Too many levels of hierarchy without finding an assistant');
	}
} // class
