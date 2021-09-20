<?php
/*
Class of bookmark-related functions
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
namespace CMSMS;

use CMSMS\Bookmark;
use CMSMS\SingleItem;
use const CMS_DB_PREFIX;
use const CMS_ROOT_URL;
use function get_secure_param;
use function startswith;

/**
 * Class for doing bookmark-related functions. Many of the Bookmark-class
 * functions are just wrappers around these.
 *
 * @final
 * @package CMS
 * @license GPL
 */
final class BookmarkOperations
{
	/**
	 * @ignore
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public static function __callStatic(string $name, array $args)
	{
		return (new CMSMS\BookmarkOperations())->$name(...$args); //TODO may bomb with same method-names
	}

	/**
	 * Prepare an URL for saving by replacing security tags with a holder
	 * string so it can be replaced when retrieved and not break security.
	 *
	 * @param string $url The url to save
	 * @return string The fixed url
	 * @internal
	 */
	private function _prep_for_saving(string $url) : string
	{
		$urlext = get_secure_param();
		if( startswith($url,CMS_ROOT_URL) ) $url = str_replace(CMS_ROOT_URL,'[ROOT_URL]',$url);
		$url = str_replace($urlext,'[SECURITYTAG]',$url);
		return $url;
	}

	/**
	 * Prepare an URL for displaying by replacing the holder for the
	 * security tag with the actual value.
	 *
	 * @param string $url The url to display
	 * @return string The fixed url
	 * @internal
	 */
	private function _prep_for_display(string $url) : string
	{
		$urlext = get_secure_param();
		$map = ['[SECURITYTAG]'=>$urlext,'[ROOT_URL]'=>CMS_ROOT_URL];
		foreach( $map as $from => $to ) {
			$url = str_replace($from,$to,$url);
		}

		$url = str_replace($from,$to,$url);
		return $url;
	}

	/**
	 * Get a list of all bookmarks for a given user
	 *
	 * @param int $user_id The desired user id.
	 * @return array An array of Bookmark objects
	 */
	public function LoadBookmarks(int $user_id) : array
	{
		$db = SingleItem::Db();
		$query = 'SELECT bookmark_id, user_id, title, url FROM '.CMS_DB_PREFIX.'admin_bookmarks WHERE user_id = ? ORDER BY title';
		$rs = $db->execute($query, [$user_id]);

		$result = [];
		while ($rs && ($row = $rs->FetchRow())) {
			$onemark = new Bookmark();
			$onemark->bookmark_id = $row['bookmark_id'];
			$onemark->user_id = $row['user_id'];
			$onemark->url = $this->_prep_for_display($row['url']);
			$onemark->title = $row['title'];
			$result[] = $onemark;
		}
		if ($rs) $rs->close();

		return $result;
	}

	/**
	 * Load a bookmark by bookmark_id.
	 *
	 * @param int $id bookmark_id to load
	 * @return mixed Bookmark | null
	 * @since 0.6.1
	 */
	public function LoadBookmarkByID(int $id)
	{
		$result = null;
		$db = SingleItem::Db();

		$query = 'SELECT bookmark_id, user_id, title, url FROM '.CMS_DB_PREFIX.'admin_bookmarks WHERE bookmark_id = ?';
		$rs = $db->execute($query, [$id]);

		while ($rs && ($row = $rs->FetchRow())) {
			$onemark = new Bookmark();
			$onemark->bookmark_id = $row['bookmark_id'];
			$onemark->user_id = $row['user_id'];
			$onemark->url = $this->_prep_for_display($row['url']);
			$onemark->title = $row['title'];
			$result = $onemark;
		}
		if ($rs) $rs->close();

		return $result;
	}

	/**
	 * Save a new bookmark in the database.
	 *
	 * @param Bookmark $bookmark Bookmark object to save
	 * @return int The new bookmark_id.  If it fails, it returns -1.
	 */
	public function InsertBookmark(Bookmark $bookmark) : int
	{
		$db = SingleItem::Db();
		$bookmark->url = $this->_prep_for_saving($bookmark->url);
		$query = 'INSERT INTO '.CMS_DB_PREFIX.'admin_bookmarks (user_id, url, title) VALUES (?,?,?)';
		$dbr = $db->execute($query, [$bookmark->user_id, $bookmark->url, $bookmark->title]);
		return ($dbr) ? $db->Insert_ID() : -1;
	}

	/**
	 * Update an existing bookmark in the database.
	 *
	 * @param Bookmark $bookmark object to save
	 * @return bool (unreliable)
	 */
	public function UpdateBookmark(Bookmark $bookmark) : bool
	{
		$db = SingleItem::Db();
		$bookmark->url = $this->_prep_for_saving($bookmark->url);
		$query = 'UPDATE '.CMS_DB_PREFIX.'admin_bookmarks SET user_id = ?, title = ?, url = ? WHERE bookmark_id = ?';
//		$dbr = useless for update
		$db->execute($query, [$bookmark->user_id, $bookmark->title, $bookmark->url, $bookmark->bookmark_id]);
//		return ($dbr != false);
		return ($db->errorNo() === 0);
	}

	/**
	 * Delete an existing bookmark from the database.
	 *
	 * @param int $id Id of the bookmark to delete
	 * @return bool
	 */
	public function DeleteBookmarkByID(int $id) : bool
	{
		$db = SingleItem::Db();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'admin_bookmarks where bookmark_id = ?';
		$dbr = $db->execute($query, [$id]);
		return ($dbr != false);
	}
} //class

//backward-compatibility shiv
\class_alias(BookmarkOperations::class, 'BookmarkOperations', false);
