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

namespace CMSMS;

use cms_cache_handler;
use CmsApp;
use CMSMS\ContentBase;
use CMSMS\ContentOperations;
use LogicException;

/**
 * A singleton class to manage caching of content objects.
 *
 * @package CMS
 * @since 2.3
 * @since 1.9 as internal\content_cache
 * @final
 */
final class ContentCache
{
	/**
	 * @ignore
	 */
	private static $_instance = null;

	/**
	 * Effectively an alias-index of the cached content
	 * @ignore
	 */
	private $_alias_map;

	/**
	 * Effectively an id-index of the cached content
	 * @ignore
	 */
	private $_id_map;

	/**
	 * @ignore
	 */
	private $_content_cache;

	/**
	 * @ignore
	 */
	public $_preload_cache = null; //set during init

	/**
	 * @ignore
	 */
	public $_key; //accessed during init

	/**
	 * @ignore
	 */
	private function __construct() {
		$this->_key = 'pc'.md5($_SERVER['REQUEST_URI']); // wuz $_GET too, but is empty, and if not, probable leak!
	}

	/**
	 * @ignore
	 */
	private function __clone() {}

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
					$obj = $this->get_content($one);
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
	 * Get the singleton instance of this class
	 * This method is called at least once-per-page during a request,
	 * which for most sites means LOTS. And it needs some post-construction
	 * initializing. Hence a singleton.
	 * @throws LogicException
	 */
	public static function get_instance() : self
	{
		if( empty(self::$_instance) ) {
			if( !CmsApp::get_instance()->is_frontend_request() ) {
				throw new LogicException(__CLASS__.' is for frontend requests only');
			}
			self::$_instance = new self();
			// one-time initialization
			$key = self::$_instance->_key; //clunky!
			$data = cms_cache_handler::get_instance()->get($key,__CLASS__);
			if( $data ) {
				list($lastmtime,$deep,$content_ids) = $data;
				$contentops = ContentOperations::get_instance();
				if( $lastmtime < $contentops->GetLastContentModification() ) {
					$content_ids = null;
				}
				if( $content_ids ) {
					$contentops->LoadChildren(null,$deep,FALSE,$content_ids); //recurses into the instance
					self::$_instance->_preload_cache = $content_ids; //clunky!
				}
			}
		}
		return self::$_instance;
	}

	/**
	 * Return from the cache a content object corresponding to $identifier.
	 *
	 * If $identifier is an integer or numeric string, an id search is performed.
	 * If $identifier is another string, an alias search is performed.
	 *
	 * @param mixed $identifier Unique identifier
	 * @return mixed the matched ContentBase object, or null.
	 */
	public function &get_content($identifier)
	{
		$res = null;
		if( $this->_content_cache ) {
			$hash = $this->content_exists($identifier);
			if( $hash !== FALSE ) {
				if( isset($this->_content_cache[$hash]) ) $res = $this->_content_cache[$hash];
			}
		}
		return $res;
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
	public function content_exists($identifier)
	{
		if( !$this->_content_cache ) return FALSE;

		if( is_numeric($identifier) ) {
			if( !$this->_id_map ) return FALSE;
			if( isset($this->_id_map[$identifier]) ) return $this->_id_map[$identifier];
		}
		elseif( is_string($identifier) ) {
			if( !$this->_alias_map ) return FALSE;
			if( isset($this->_alias_map[$identifier]) ) return $this->_alias_map[$identifier];
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
	private function _add_content($id,$alias,ContentBase &$obj)
	{
		if( !$id) return FALSE;
		if( !$this->_alias_map ) $this->_alias_map = [];
		if( !$this->_id_map ) $this->_id_map = [];
		if( !$this->_content_cache ) $this->_content_cache = [];

		$hash = md5($id.$alias);
		$this->_content_cache[$hash] = $obj;
		if( $alias ) $this->_alias_map[$alias] = $hash;
		$this->_id_map[$id] = $hash;
		return TRUE;
	}

	/**
	 * Add a content object to the cache
	 *
	 * @param int The content Id
	 * @param string The content alias
	 * @param ContentBase The content object
	 * @return bool
	 */
	public function add_content($id,$alias,ContentBase &$obj)
	{
		return $this->_add_content($id,$alias,$obj);
	}

	/**
	 * Clear the entire contents of the cache
	 */
	public function clear()
	{
		$this->_content_cache = null;
		$this->_alias_map = null;
		$this->_id_map = null;
	}

	/**
	 * Return a list of the page ids that are in the cache
	 *
	 * @return mixed array | null
	 */
	public function get_loaded_page_ids()
	{
		if( $this->_id_map ) return array_keys($this->_id_map);
	}

	/**
	 * Retrieve a pageid given an alias.
	 *
	 * @param string Page alias
	 * @return mixed int id, or FALSE if alias is not in the cache.
	 */
	public function get_id_from_alias($alias)
	{
		if( !isset($this->_alias_map) ) return FALSE;
		if( !isset($this->_alias_map[$alias]) ) return FALSE;
		$hash = $this->_alias_map[$alias];
		return array_search($hash,$this->_id_map);
	}

	/**
	 * Retrieve a page alias given an id.
	 *
	 * @param int page id.
	 * @return mixed string alias, or FALSE if id is not in the cache.
	 */
	public function get_alias_from_id($id)
	{
		if( !isset($this->_id_map) ) return FALSE;
		if( !isset($this->_id_map[$id]) ) return FALSE;
		$hash = $this->_id_map[$id];
		return array_search($hash,$this->_alias_map);
	}

	/**
	 * Indicates whether we have preloaded cached data
	 *
	 * @return bool
	 */
	public function have_preloaded()
	{
		return !empty($this->_preload_cache);
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
	public function unload($identifier)
	{
		$hash = $this->content_exists($identifier);
		if( $hash ) {
			$id = array_search($identifier,$this->_id_map);
			$alias = array_search($identifier,$this->_alias_map);
			if( $alias !== FALSE && $id != FALSE ) {
				unset($this->_id_map[$id]);
				unset($this->_alias_map[$alias]);
				unset($this->_content_cache[$hash]);
			}
		}
	}
}
