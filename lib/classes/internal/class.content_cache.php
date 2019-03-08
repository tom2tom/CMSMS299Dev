<?php
# Class for managing cached content objects
# Copyright (C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS\internal;

use cms_cache_handler;
use CmsApp;
use CMSMS\ContentBase;
use CMSMS\ContentOperations;

/**
 * A static class to manage caching content objects.
 *
 * @package CMS
 * @since 1.9
 * @internal
 * @final
 * @ignore
 */
final class content_cache
{
	/**
	 * @ignore
	 */
	private static $_instance = null;

	/**
	 * @ignore
	 */
	private static $_alias_map;

	/**
	 * @ignore
	 */
	private static $_id_map;

	/**
	 * @ignore
	 */
	private static $_content_cache;

	/**
	 * @ignore
	 */
	private $_preload_cache;

	/**
	 * @ignore
	 */
	private $_key;

	/**
	 * @ignore
	 */
	private function __construct()
	{
		if( !CmsApp::get_instance()->is_frontend_request() ) return;
		$content_ids = null;
		$deep = FALSE;
		$this->_key = 'pc'.md5($_SERVER['REQUEST_URI'].serialize($_GET));
		$data = cms_cache_handler::get_instance()->get($this->_key,__CLASS__);
		if( $data) {
			list($lastmtime,$deep,$content_ids) = $data;
			$contentops = ContentOperations::get_instance();
			if( $lastmtime < $contentops->GetLastContentModification() ) {
				$deep = null;
				$content_ids = null;
			}
		}
		if( $content_ids ) {
			$this->_preload_cache = $content_ids;
			if( !$data ) $contentops = ContentOperations::get_instance();
			$tmp = $contentops->LoadChildren(null,$deep,FALSE,$content_ids);
		}
	}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * @ignore
	 */
	public static function get_instance() : self
	{
		if( !(self::$_instance) ) self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * @ignore
	 */
	public function __destruct()
	{
		if( !CmsApp::get_instance()->is_frontend_request() ) return;
		if( !$this->_key ) return;
		if( $this->_preload_cache ) return;

		$list = $this->get_loaded_page_ids();
		if( $list ) {
			if( !$this->_preload_cache ) {
				$dirty = TRUE;
			}
			else {
				$t2 = array_diff($list,$this->_preload_cache);
				$dirty = ( $t2 );
			}

			if( $dirty ) {
				$ndeep = [];
				$deep = FALSE;
				foreach( $list as $one ) {
					$obj = self::get_content($one);
					if( !is_object($obj) ) continue;
					$tmp = $obj->Properties();
					if( $tmp ) {
						$deep = TRUE;
						$ndeep[] = $one;
						break;
					}
				}
				$deep = ($deep && count($ndeep) > (count($list) / 4));
				$tmp = [time(),$deep,$list];
				cms_cache_handler::get_instance()->set($this->_key,$tmp,__CLASS__);
			}
		}
	}

	/**
	 * @ignore
	 */
	private static function &get_content_obj($hash)
	{
		$res = null;
		if( self::$_content_cache ) {
			if( isset(self::$_content_cache[$hash]) ) $res = self::$_content_cache[$hash];
		}
		return $res;
	}

	/**
	 * Return from the cache a content object corresponding to $identifier.
	 *
	 * If $identifier is an integer or numeric string, an id search is performed.
	 * If $identifier is another string, an alias search is performed.
	 *
	 * @param mixed $identifier Unique identifier
	 * @return mixed The ContentBase object, or null.
	 */
	public static function &get_content($identifier)
	{
		$hash = self::content_exists($identifier);
		if( $hash === FALSE ) {
			// content doesn't exist...
			$res = null;
			return $res;
		}

		return self::get_content_obj($hash);
	}

	/**
	 * Test if content corresponding to $identifier is present in the cache
	 *
	 * If $identifier is an integer or numeric string, an id search is performed.
	 * If $identifier is a string, an alias search is performed.
	 *
	 * @param mixed $identifier Unique identifier
	 * @return bool
	 */
	public static function content_exists($identifier)
	{
		if( !self::$_content_cache ) return FALSE;

		if( is_numeric($identifier) ) {
			if( !self::$_id_map ) return FALSE;
			if( !isset(self::$_id_map[$identifier]) ) return FALSE;
			return self::$_id_map[$identifier];
		}
		else if( is_string($identifier) ) {
			if( !self::$_alias_map ) return FALSE;
			if( !isset(self::$_alias_map[$identifier]) ) return FALSE;
			return self::$_alias_map[$identifier];
		}
		return FALSE;
	}

	/**
	 * Add data to the cache
	 *
	 * @access private
	 * @internal
	 * @since 1.10.1
	 * @param int The content Id
	 * @param string The content alias
	 * @param ContentBase The content object
	 * @return bool
	 */
	private static function _add_content($id,$alias,ContentBase &$obj)
	{
	if( !$id) return FALSE;
	if( !self::$_alias_map ) self::$_alias_map = [];
	if( !self::$_id_map ) self::$_id_map = [];
	if( !self::$_content_cache ) self::$_content_cache = [];

	$hash = md5($id.$alias);
	self::$_content_cache[$hash] = $obj;
	if( $alias ) self::$_alias_map[$alias] = $hash;
	self::$_id_map[$id] = $hash;
	return TRUE;
	}

	/**
	 * Add the content object to the cache
	 *
	 * @param int The content Id
	 * @param string The content alias
	 * @param ContentBase The content object
	 * @return bool
	 */
	public static function add_content($id,$alias,ContentBase &$obj)
	{
		self::_add_content($id,$alias,$obj);
	}

	/**
	 * Clear the contents of the entire cache
	 */
	public static function clear()
	{
		self::$_content_cache = null;
		self::$_alias_map = null;
		self::$_id_map = null;
	}

	/**
	 * Return a list of the page ids that are in the cache
	 *
	 * @return Array
	 */
	public static function get_loaded_page_ids()
	{
		if( self::$_id_map ) return array_keys(self::$_id_map);
	}

	/**
	 * Retrieve a pageid given an alias.
	 *
	 * @param string Page alias
	 * @return int id, or FALSE if alias cannot be found in cache.
	 */
	public static function get_id_from_alias($alias)
	{
		if( !isset(self::$_alias_map) ) return FALSE;
		if( !isset(self::$_alias_map[$alias]) ) return FALSE;
		$hash = self::$_alias_map[$alias];
		return array_search($hash,self::$_id_map);
	}

	/**
	 * Retrieve a page alias given an id.
	 *
	 * @param int page id.
	 * @return string alias, or FALSE if id cannot be found in cache.
	 */
	public static function get_alias_from_id($id)
	{
		if( !isset(self::$_id_map) ) return FALSE;
		if( !isset(self::$_id_map[$id]) ) return FALSE;
		$hash = self::$_id_map[$id];
		return array_search($hash,self::$_alias_map);
	}

	/**
	 * Indicates whether we have preloaded cached data
	 *
	 * @return bool
	 */
	public static function have_preloaded()
	{
		return is_array(self::get_instance()->_preload_cache);
	}

	/**
	 * Unload the specified content id (numeric id or alias) if loaded.
	 * Note, this should be used with caution, as the next time this page is requested it will be loaded from the database again.n
	 *
	 * If $identifier is an integer or numeric string, an id search is performed.
	 * If $identifier is a string, an alias search is performed.
	 *
	 * @author Robert Campbell
	 * @since 2.0
	 * @param mixed Unique identifier
	 * @return void
	 */
	public static function unload($identifier)
	{
		$hash = self::content_exists($identifier);
		if( $hash ) {
			$id = array_search($identifier,self::$_id_map);
			$alias = array_search($identifier,self::$_alias_map);
			if( $alias !== FALSE && $id != FALSE ) {
				unset(self::$_id_map[$id]);
				unset(self::$_alias_map[$alias]);
				unset(self::$_content_cache[$hash]);
			}
		}
	}
}
