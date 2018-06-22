<?php
#compatibility class to manage User Defined Tags.
#Copyright (C) 2017-2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
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

/**
 * A compatibility class to manage User Defined Tags.
 * Before CMSMS 2.3, User Defined Tag data were stored in the database.
 * Since 2.3 this functionality is provided by corresponding filesystem files.
 * This class provides backwards compatibility.
 *
 * @package CMS
 * @license GPL
 * @deprecated
 */
namespace CMSMS;

use CmsApp;

final class UserTagOperations
{
	/**
	 * @ignore
	 */
	private static $_instance = null;

	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Get a reference to the only allowed instance of this class
	 * @return UserTagOperations
	 */
	final public static function &get_instance() : self
	{
		if( !self::$_instance ) self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * @ignore
	 */
	public function __call($name,$arguments)
	{
		return $this->CallUserTag($name,$arguments);
	}

	/**
	 * Load all the information about User Defined Tags.
	 * Since 2.3, this function is an empty stub.
	 *
	 * @deprecated
	 */
	public function LoadUserTags()
	{
	}

	/**
	 * Retrieve the body of a User Defined Tag
	 * Since 2.3, this function is an empty stub.
	 *
	 * @param string $name User defined tag name
	 * @deprecated
	 * @return string|false
	 */
	public function GetUserTag($name)
	{
		return false;
	}

	/**
	 * Test if a User Defined Tag with a specific name exists
	 *
	 * @param string $name User defined tag name
	 * @return string|false
	 * @since 1.10
	 */
	function UserTagExists($name)
	{
		$gCms = CmsApp::get_instance();
		$mgr = $gCms->GetSimplePluginOperations();
		return $mgr->plugin_exists($name);
	}

	/**
	 * Add or update a named User Defined Tag into the database
	 * Since 2.3, this function is an empty stub.
	 *
	 * @param string $name User defined tag name
	 * @param string $text Body of User Defined Tag
	 * @param string $description Description for the User Defined Tag.
	 * @param int    $id ID of existing user tag (for updates).
	 * @return bool
	 */
	function SetUserTag($name, $text, $description, $id = null)
	{
		return false;
	}

	/**
	 * Remove a named User Defined Tag from the database
	 * Since 2.3, this function is an empty stub.
	 *
	 * @param string $name User defined tag name
	 * @return bool
	 */
	public function RemoveUserTag($name)
	{
		return false;
	}

 	/**
	 * Return a list (suitable for use in a pulldown) of user tags.
	 *
	 * @return array|false
	 */
	public function ListUserTags()
	{
		$gCms = CmsApp::get_instance();
		$mgr = $gCms->GetSimplePluginOperations();
		$tmp = $mgr->get_list();
		if( !$tmp ) return;

		$out = null;
		foreach( $tmp as $name ) {
			$out[$name] = $name;
		}
		asort($out);
		return $out;
	}

	/**
	 * Execute a User Defined Tag
	 *
	 * @param string $name The name of the User Defined Tag
	 * @param array  $params Optional parameters.
	 * @return string|false
	 * @deprecated
	 */
	public function CallUserTag($name, &$params)
	{
		$gCms = CmsApp::get_instance();
		$mgr = $gCms->GetSimplePluginOperations();
		return $mgr->call_plugin($name,$params,$gCms->GetSmarty());
	}

	/**
	 * Given a UDT name create an executable function from it
	 * Since 2.3 this function is an empty stub.
	 *
	 * @internal
	 * @param string $name The name of the User Defined Tag to operate with.
	 */
	public function CreateTagFunction($name)
	{
	}

} // class

//backward-compatibility shiv
\class_alias(UserTagOperations::class, 'UserTagOperations', false);
