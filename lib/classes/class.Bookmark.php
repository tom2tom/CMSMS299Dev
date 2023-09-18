<?php
/*
Bookmark class for the CMSMS admin console
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
namespace CMSMS;

use CMSMS\BookmarkOperations;

/**
 * Bookmark class for the CMSMS admin console.
 *
 * @package CMS
 * @license GPL
 */
class Bookmark
{
	/**
	 * @var int $bookmark_id The bookmark id
	 */
	public $bookmark_id;

	/**
	 * @var int $user_id Admin user (owner) ID
	 */
	public $user_id;

	/**
	 * @var string $title The bookmark title
	 */
	public $title;

	/**
	 * @var string $url The bookmark URL
	 */
	public $url;

	/**
	 * Generic constructor.  Runs the SetInitialValues fuction.
	 */
	public function __construct()
	{
		$this->SetInitialValues();
	}

	/**
	 * Sets object to some sane initial values
	 */
	public function SetInitialValues()
	{
		$this->bookmark_id = 0;
		$this->title = '';
		$this->url = '';
		$this->user_id = 0;
	}


	/**
	 * Saves the bookmark to the database.
	 *
	 * If no id is set, then a new record is created.
	 * Otherwise, the record is updated to all values in the Bookmark object.
	 *
	 * @return bool
	 */
	public function Save()
	{
		$result = false;
		$bookops = new BookmarkOperations();

		if ($this->bookmark_id > 0) {
			$result = $bookops->UpdateBookmark($this);
		}
		else {
			$newid = $bookops->InsertBookmark($this);
			if ($newid > 0) {
				$this->bookmark_id = $newid;
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Delete the record for this Bookmark from the database.
	 * All values in the object are reset to their initial values.
	 *
	 * @return bool
	 */
	public function Delete()
	{
		$result = false;

		if ($this->bookmark_id > 0) {
			$result = (new BookmarkOperations())->DeleteBookmarkByID($this->bookmark_id);
			if ($result) $this->SetInitialValues();
		}

		return $result;
	}
}

//backward-compatibility shiv
\class_alias(Bookmark::class, 'Bookmark');
