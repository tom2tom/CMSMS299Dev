<?php
#Class of bookmark-related functions
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
#BUT withOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;

use CmsApp;
use CMSMS\Bookmark;
use const CMS_DB_PREFIX;
use const CMS_ROOT_URL;
use function get_secure_param;
use function startswith;

/**
 * Class for doing bookmark related functions.  Many of the Bookmark-object functions
 * are just wrappers around these.
 *
 * @final
 * @package CMS
 * @license GPL
 */
final class BookmarkOperations
{
	/**
	 * Not worth caching centrally with singletons which include 'protected' properties
	 * @ignore
	 */
	private static $_instance = null;

	/* *
	 * @ignore
	 */
//	private function __construct() {}

	/* *
	 * @ignore
	 */
//	private function __clone() {}

	/**
	 * @ignore
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public static function __callStatic($name, $args)
	{
		if (!self::$_instance) { self::$_instance = new self(); }
		if ($name == 'get_instance') {
			return self::$_instance;
		}
		return self::$_instance->$name(...$args); //TODO may bomb with same method-names
	}

	/**
	 * Prepares an URL for saving by replacing security tags with a holder
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
	 * Prepares a url for displaying by replacing the holder for the security
	 * tag with the actual value.
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
	 * Gets a list of all bookmarks for a given user
	 *
	 * @param int $user_id The desired user id.
	 * @return array An array of Bookmark objects
	 */
	public function LoadBookmarks(int $user_id) : array
	{
		$gCms = CmsApp::get_instance();
		$db = $gCms->GetDb();

		$result = [];
		$query = 'SELECT bookmark_id, user_id, title, url FROM '.CMS_DB_PREFIX.'admin_bookmarks WHERE user_id = ? ORDER BY title';
		$rs = $db->Execute($query, [$user_id]);

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
	 * Loads a bookmark by bookmark_id.
	 *
	 * @param int $id bookmark_id to load
	 * @return mixed Bookmark | null
	 * @since 0.6.1
	 */
	public function LoadBookmarkByID(int $id)
	{
		$result = null;
		$db = CmsApp::get_instance()->GetDb();

		$query = 'SELECT bookmark_id, user_id, title, url FROM '.CMS_DB_PREFIX.'admin_bookmarks WHERE bookmark_id = ?';
		$rs = $db->Execute($query, [$id]);

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
	 * Saves a new bookmark to the database.
	 *
	 * @param Bookmark $bookmark Bookmark object to save
	 * @return int The new bookmark_id.  If it fails, it returns -1.
	 */
	public function InsertBookmark(Bookmark $bookmark) : int
	{
		$db = CmsApp::get_instance()->GetDb();

		$bookmark->url = $this->_prep_for_saving($bookmark->url);
		$query = 'INSERT INTO '.CMS_DB_PREFIX.'admin_bookmarks (user_id, url, title) VALUES (?,?,?)';
		$dbr = $db->Execute($query, [$bookmark->user_id, $bookmark->url, $bookmark->title]);
		return ($dbr) ? $db->Insert_ID() : -1;
	}

	/**
	 * Updates an existing bookmark in the database.
	 *
	 * @param Bookmark $bookmark object to save
	 * @return bool (unreliable)
	 */
	public function UpdateBookmark(Bookmark $bookmark) : bool
	{
		$db = CmsApp::get_instance()->GetDb();

		$bookmark->url = $this->_prep_for_saving($bookmark->url);
		$query = 'UPDATE '.CMS_DB_PREFIX.'admin_bookmarks SET user_id = ?, title = ?, url = ? WHERE bookmark_id = ?';
		$dbr = $db->Execute($query, [$bookmark->user_id, $bookmark->title, $bookmark->url, $bookmark->bookmark_id]);
		return ($dbr != false);
	}

	/**
	 * Deletes an existing bookmark from the database.
	 *
	 * @param int $id Id of the bookmark to delete
	 * @return bool
	 */
	public function DeleteBookmarkByID(int $id) : bool
	{
		$db = CmsApp::get_instance()->GetDb();

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'admin_bookmarks where bookmark_id = ?';
		$dbr = $db->Execute($query, [$id]);
		return ($dbr != false);
	}
} //class

//backward-compatibility shiv
\class_alias(BookmarkOperations::class, 'BookmarkOperations', false);
