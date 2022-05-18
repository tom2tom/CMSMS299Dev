<?php
/*
Interface for content-assistant classes
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
namespace CMSMS;

use CMSMS\contenttypes\ContentBase;

/**
 * An interface for content assistant classes.
 *
 * IContentAssistant instances provide various extensions and utilities
 * for content objects.
 *
 * @since	3.0
 * @since	2.0 as ContentAssistant
 * @abstract
 * @package	CMS
 */
interface IContentAssistant
{
	/**
	 * Construct a IContentAssistant-compatible object
	 *
	 * @abstract
	 * @param ContentBase the object for which the assistant will be used.
	 */
	#[\ReturnTypeWillChange]
	public function __construct(ContentBase $content);
}
