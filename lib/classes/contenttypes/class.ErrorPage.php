<?php
/*
Class for CMS Made Simple ErrorPage content type
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AppState;
use CMSMS\contenttypes\Content;
use function lang;

/**
 * Implements the ErrorPage content type
 *
 * @package CMS
 * @version $Revision$
 * @license GPL
 */
class ErrorPage extends Content
{
	// NOTE any private or static property will not be serialized

	public $doAliasCheck = false;
	public $error_types = [];

	/**
	 * @param mixed $params
	 */
	public function __construct($params)
	{
		parent::__construct($params);
		foreach ([
			'accesskey' => '',
			'active' => true,
			'alias' => '', //this one is a replacement
			'cachable' => false,
			'menutext' => '',
			'page_url' => '',
			'parent' => -1,
//			'searchable' => false,
			'secure' => false, //deprecated property since 2.99
			'showinmenu' => false,
			'titleattribute' => '',
		] as $key => $value) {
			$this->$key = $value;
		}

		if (AppState::test_state(AppState::STATE_ADMIN_PAGE)) {
			$this->error_types = [
			 	'403' => lang('403description'),
				'404' => lang('404description'),
			 	'503' => lang('503description'),
			];
		}
		//TODO c.f. AppParams::get('sitedownmessage') for 503 content
		$this->doAutoAliasIfEnabled = false; //CHECKME
	}

	public function HasUsableLink() : bool { return false; }
	public function IsDefaultPossible() : bool { return false; }
	public function IsSystemPage() : bool { return true; }
	public function IsViewable() : bool { return true; }
	public function WantsChildren() : bool { return false; }
}

//backward-compatibility shiv
\class_alias(ErrorPage::class, 'ErrorPage', false);
