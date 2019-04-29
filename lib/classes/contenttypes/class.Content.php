<?php
#The main Content class
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

namespace CMSMS\contenttypes;

use cms_utils;
use CmsApp;
use CmsContentException;
use CmsCoreCapabilities;
use CMSMS\contenttypes\ContentBase;
use CMSMS\internal\page_template_parser;
use SmartyException;

/**
 * Implements the Content (page) content type.
 *
 * This is the primary content type. It represents an HTML page.
 *
 * @package CMS
 * @subpackage content_types
 * @license GPL
 */
class Content extends ContentBase
{
	// NOTE any private or static property will not be serialized

	/**
	 * @param mixed $params
	 */
	public function __construct($params)
	{
		parent::__construct($params);
		if (isset($this->_fields['content_id'])) {
			// not constructing: load content-block(s) and anything else relevant to the display
			$this->_load_properties();
		}
	}

	public function IsDefaultPossible() : bool { return true; }

	/**
	 * @since 2.3
	 * #return string
	 */
	public function TemplateResource() : string
	{
/*		$tmp = $this->GetPropertyValue('template_rsrc');
		if( !$tmp ) $tmp = $this->templateid;
		if( $tmp ) {
			$num = (int) $tmp;
			if( $num > 0 && trim($num) == $tmp ) {
				// numeric: assume normal (database|file) template
				return "cms_template:$tmp";
			} else {
				return $tmp;
			}
		}
		return '';
*/
		return 'cms_template:'.$this->templateid;
	}
} // class

//backward-compatibility shiv
\class_alias(Content::class, 'Content', false);
