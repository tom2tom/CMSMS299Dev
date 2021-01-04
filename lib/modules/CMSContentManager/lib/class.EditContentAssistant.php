<?php
/*
EditContentAssistant: base class for building edit-content assistant objects
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

namespace CMSContentManager;

use CMSContentManager\ContentBase;
use CMSMS\internal\ContentAssistant;

/**
 * An abstract class for building edit content assistant objects.
 */
abstract class EditContentAssistant implements ContentAssistant
{
	private $_content_obj;

	/**
	 * construct an EditContentAssistant object.
	 *
	 * @param ContentBase $content the content-object that we are building an assistant for.
	 */
	public function __construct(ContentBase $content)
	{
		$this->_content_obj = $content;
	}

	/**
	 * Get HTML (including javascript) that should go in the page content when editing this content object.
	 * This could be used for outputting some javascript to enhance the functionality of some content fields.
	 *
	 * @return string
	 */
	abstract public function getExtraCode();
} // class
