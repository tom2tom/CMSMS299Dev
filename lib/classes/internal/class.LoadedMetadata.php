<?php
/*
Singleton class of methods for managing module metadata
Copyright (C) 2011-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
namespace CMSMS\internal;

use CMSMS\DataException;
use CMSMS\DeprecationNotice;
use CMSMS\internal\LoadedMetadataType;
use CMSMS\LoadedData;
use CMSMS\Lone;
use ReflectionMethod;
use Throwable;
use UnexpectedValueException;
use const CMS_DEPREC;
use function debug_buffer;

/**
 * A singleton class for managing metadata about modules.
 * This class polls and caches information from modules as needed.
 * Its API reflects that of its parent CMSMS\LoadedData class, but its data
 * populators are LoadedMetadataType's (which use varargs in their fetch-method)
 * @final
 * @internal
 *
 * @package CMS
 * @since 3.0
 * @since 1.10 as global namespace module_meta
 */
final class LoadedMetadata extends LoadedData
{
	/**
	 * @ignore
	 */
	const ANY_RESULT = '.*';

	/**
	 * @ignore
	 * System-cache keys-space for this class's data
	 */
	private const META_SPACE = '9BjqyEjR7P'; // i.e. CacheDriver::get_cachespace(static::class)

	/**
	 * @var array
	 * Map from custom-callable types to corresponding data-fetch callable
	 * Each member like 'generictype' => 'callable'. Specifically:
	 *  'capable_modules' => self::CapableModules
	 *  'methodic_modules' => self::MethodicModules
	 * @ignore
	 */
	private $customs = [];

	/* *
	 * @ignore
	 * @private to prevent direct creation (even by Lone class)
	 */
//	private function __construct() {} TODO public iff wanted by Lone ?

	/**
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	private function __clone() {}// : void {}

	/**
	 * Get the singleton instance of this class
	 * @deprecated since 3.0 Instead use Lone::get('LoadedMetadata')
	 *
	 * @return LoadedMetadata object
	 */
	public static function get_instance()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'LoadedMetadata\')'));
		return Lone::get('LoadedMetadata');
	}

	public static function load_setup()
	{
		Lone::get('LoadedMetadata')->set_custom_callables();
	}

	/**
	 * Record callables for custom-fetchers which might be populated on demand
	 * @since 3.0
	 */
	public function set_custom_callables()
	{
		$this->customs = [
			'capable_modules' => static::class.'::CapableModules',
			'methodic_modules' => static::class.'::MethodicModules',
		];
	}

	/**
	 * 'capable_modules' metadata fetcher.
	 * Return names of installed modules which have, or don't have, the
	 * specified capability. Modules' availability setting is ignored.
	 * This retrieves data from the metadata cache if possible, so it does
	 * not necessarily check actual capabilities. Absent cached data, this
	 * method temporarily loads modules which are not currently loaded.
	 * @since 3.0
	 * @since 1.10 as module_meta::module_list_by_capability()
	 *
	 * @param bool $force Flag signalling source-data wanted
	 * @param string $capability The capability name
	 * @param mixed  $params array | scalar | null Optional CMSModule::HasCapability()
	 *  arguments other than $capability
	 * @param bool   $match  Optional capability-status to match. Default true.
	 * @return array of matching module names, possibly empty
	 */
	public static function CapableModules(bool $force, string $capability, $params = [], bool $match = true) : array
	{
		if( !$capability ) return [];

		if( !is_array($params) ) {
			if( $params ) {
				$params = [$params];
			}
			else {
				$params = [];
			}
		}

		debug_buffer("Start adding '$capability' capability to modules metadata");
		$modops = Lone::get('ModuleOperations');
		$availmodules = $modops->GetInstalledModules();
		$out = [];
		foreach( $availmodules as $modname ) {
			$modops->PollModule($modname, $force, function($mod) use($capability, $params, $match, &$out, $modname) {
				if( $mod ) {
					$res = $mod->HasCapability($capability, $params);
					if( $res == $match ) {
						$out[] = $modname;
					}
				}
			});
		}

		debug_buffer("Finished adding '$capability' capability to modules metadata");
		return $out;
	}

	/**
	 * 'methodic_modules' metadata fetcher.
	 * Return names of installed modules which have the specified method,
	 * and that method returns the specified result. Modules' availability
	 * setting is ignored. This retrieves data from metadata cache if
	 * possible, so it does not necessarily load modules and call their
	 * method. Absent cached data, this method temporarily loads modules
	 * which are not currently loaded.
	 * @since 3.0
	 * @since 1.10 as module_meta::module_list_by_method()
	 *
	 * @param bool $force Flag signalling source-data wanted
	 * @param string $method Method name
	 * @param mixed  $returnvalue Optional value to (non-strictly) compare
	 *  with method return-value, and only report matches. May be
	 *  LoadedMetadata::ANY_RESULT for any value. Default true.
	 * @return array of matching module names i.e. possibly empty
	 */
	public static function MethodicModules(bool $force, string $method, $returnvalue = true) : array
	{
		if( !$method ) return [];

		debug_buffer("Start adding '$method()' to modules metadata");
		// TODO some ReflectionClass process instead of the following, if method-calling is supported
		$modops = Lone::get('ModuleOperations');
		$availmodules = $modops->GetInstalledModules();
		$out = [];
		foreach( $availmodules as $modname ) {
			$modops->PollModule($modname, $force, function($mod) use($method, $returnvalue, &$out, $modname) {
				if( $mod && method_exists($mod, $method) ) {
					// check if this is just an inherited method
					$reflector = new ReflectionMethod($mod, $method);
					if( $reflector->getDeclaringClass()->getName() == $modname ) { //OR == get_class($mod) if modules are namespaced
						// do the test
						$res = $mod->$method();
						if( $returnvalue === self::ANY_RESULT || $returnvalue == $res ) {
							$out[] = $modname;
						}
					}
				}
			});
		}

		debug_buffer("Finished adding '$method()' to modules metadata");
		return $out;
	}

	/**
	 * Report whether a data-type is present in the cache
	 *
	 * @param string $type  Data-identifier e.g. 'capable_modules'
	 * @param varargs $details Optional extra parameters
	 * @return bool
	 */
	public function has(string $type, ...$details) : bool
	{
		if( isset($this->customs[$type]) ) {
			if( !$details || $details[0] === '*' ) {
				$m = $type.parent::SUB_SEP;
				foreach( $this->types as $ctype => $obj ) {
					if( startswith($ctype, $m) ) {
						return true;
					}
				}
// TODO			if( $this->get_main_cache()->has(any member like $m, self::META_SPACE) ) {
//					return true; // TODO subsequent existence-check will fail
//				}
			}
			else {
				$ctype = $type.$this->get_subtype([$details[0]]);
				if( isset($this->types[$ctype]) ) {
					return true;
				}
				if( $this->get_main_cache()->has($ctype, self::META_SPACE) ) {
					return true; // TODO subsequent existence-check will fail
				}
			}
		}
		return false;
	}

	/**
	 * Get all loaded or loadable data for the specified type
	 *
	 * @param string $type a recognized metadata name e.g. 'capable_modules'
	 * @param bool $force Optional flag signalling source-data wanted
	 *  (i.e. no system-cache). Default false.
	 * @param varargs $details since 3.0 Optional extra parameters
	 * @return mixed
	 * @throws UnexpectedValueException if $type is not a recorded/cachable type
	 */
	public function get(string $type, bool $force = false, ...$details)
	{
		if( isset($this->customs[$type]) ) {
			$ctype = $type.$this->get_subtype([$details[0]]);
			if( !isset($this->types[$ctype]) ) { // this type might not have been setup already
				// populate on demand
				$obj = new LoadedMetadataType($type, $details[0], $this->customs[$type]);
				$this->add_type($obj); // keyed as $ctype
				if( $this->get_main_cache()->has($ctype, self::META_SPACE) ) {
					//TODO
				}
			}
		} else {
			throw new UnexpectedValueException("Invalid loaded-data type '$type'");
		}

		if( !$force ) {
			if( !parent::$cache_loaded ) {
				// Migrate all (formerly loaded) data from system cache to in-memory cache
				$saved = $this->get_main_cache()->getall(self::META_SPACE);
				if( $saved ) {
					foreach( $saved as $name => $val ) {
						if( !isset($this->data[$name]) ) {
							$this->data[$name] = $val; // this might precede corresponding type-addition
						}
						else {
							$this->dirty[$name] = 1;
						}
					}
				}
				parent::$cache_loaded = true;
			}
			if( !isset($this->data[$ctype]) ) {
				$this->data[$ctype] = $this->types[$ctype]->fetch($force, ...$details);
				$this->dirty[$ctype] = 1;
			}
		}
		else {
			$this->data[$ctype] = $this->types[$ctype]->fetch($force, ...$details);
			$this->dirty[$ctype] = 1;
		}
		return $this->data[$ctype];
	}

	/**
	 * Convenience combination of delete() then get() to re-populate
	 * from the original data source. Nothing is returned.
	 *
	 * @param string $type a recognized metadata name e.g. 'capable_modules'
	 *  If $type is '*' or falsy or not supplied, all types will be refreshed.
	 * @param varargs $details There may be other parameter(s) following
	 *  $type. Only the first of them is used here (it may be '*').
	 *  Or if no detail is specified, it is treated as if there were a '*'.
	 */
	public function refresh(string $type, ...$details)
	{
		if( $type && $type !== '*' && $details && $details[0] !== '*' ) {
			$this->delete($type, ...$details);
			try {
				$this->get($type, true, ...$details);
			}
			catch (Throwable $t) {
				// nothing here
			}
		}
		else {
			// re-populate from source for the types we have now
			if( 0 ) { // TODO some matcher-func($type, $details), $type might be '' or '*' or specific, $details might be empty, $details[0] might be '*'
//	 			$this->get_main_cache()->clear(WHATEVER MATCHES);
				$used = [];
				foreach( $this->types as $obj ) {
					if( 0 ) { // TODO some specific matcher
						$used = array_merge_recursive($used, $obj->get_uses());
					}
				}
				if( $used ) {
					// re-populate by force-fetch
					throw new DataException("Metadata type '*' underspecified for refresh");
				}
			}
			else {
				$this->get_main_cache()->clear(self::META_SPACE);
				$used = [];
				foreach( $this->types as $ctype => $obj ) {
					$used = array_merge_recursive($used, $obj->get_uses());
				}
				if( $used ) {
/* $used is array like:
  'methodic_modules' =>
	array
	  'RegisterRoute' =>
		array
		  0 =>
			array
			  0 => boolean false
	  'IsPluginModule' =>
		array
		  0 =>
			array
			  empty
  'capable_modules' =>
	array
	  'plugin' =>
		array
		  0 =>
			array
			  empty
*/
					// re-populate all by force-fetch
					foreach( $used as $dtype => $data ) {
						foreach( $data as $prop => $pdata) {
							$args = reset($pdata);
							$this->get($dtype, true, $prop, ...$args);
						}
					}
				}
			}
		}
	}

	/**
	 * Remove from the in-memory cache the data of the specified type.
	 * Hence reload then-current data when the data-type is next wanted.
	 *
	 * @param mixed $type string | null Optional type-name.
	 *  If $type is '*' or falsy or not supplied, all types will be released
	 * @param varargs $details since 3.0 Optional extra parameters
	 */
	public function release($type = null, ...$details)
	{
		throw new Exception("Metadata release is not supported");
/*		if( $details ) { $type .= $this->get_subtype($details); }
		if( $type && $type !== '*' ) {
			unset($this->data[$type], $this->dirty[$type]);
		}
		else {
			$this->data = [];
			$this->dirty = [];
		}
		self::$cache_loaded = false;
*/
	}

	/**
	 * Remove the specified type from the in-memory and system metadata caches
	 * @since 3.0
	 *
	 * @param string $type a recognized metadata name e.g. 'capable_modules'
	 * @param varargs $details There may be other parameter(s) following
	 *  $type, they being extra arguments that were specified for use in
	 *  fetch()'ing. Only the first of them is used here (it may be '*').
	 * @throws DataException or UnexpectedValueException
	 */
	public function delete(string $type, ...$details)
	{
		// TODO handle $type === '*' ?
		if( $type && isset($this->customs[$type]) ) {
			if( !$details ) {
				throw new DataException("Metadata type '$type' underspecified for deletion");
			}
			if( $details[0] === '*' ) {
				// delete all that match $type.':::*'
				$m = $type.parent::SUB_SEP;
				$used = [];
				foreach( $this->types as $ctype => $obj ) {
					if( startswith($ctype, $m) ) {
						$used = array_merge_recursive($used, $obj->get_uses());
					}
				}
				// TODO delete all unique($used[])
				return;
			}
			$ctype = $type.parent::SUB_SEP.$details[0];
			if( isset($this->types[$ctype]) ) {
				$this->get_main_cache()->delete($ctype, self::META_SPACE);
				unset($this->data[$ctype], $this->dirty[$ctype]);
			}
			else {
				// TODO feedback re nothing to delete
			}
			return;
		}
		if( $type ) {
			throw new UnexpectedValueException("Invalid loaded-data type '$type'");
		}
		throw new DataException("Metadata type not provided for deletion");
	}

	/**
	 * Remove everything, or a named type, from the in-memory and system metadata caches
	 * @since 3.0
	 *
	 * @param mixed $type string | null Optional type-name. If $type is
	 *  '*' or falsy or not supplied, all types will be cleared.
	 * @param varargs $details since 3.0 Optional extra parameters
	 */
	public function clear($type = null, ...$details)
	{
		if( $type && $type !== '*' ) {
			if( $details ) {
				$type .= $this->get_subtype($details);
			}
			$this->delete($type, '*');
		}
		else {
			$this->get_main_cache()->clear(self::META_SPACE);
			$this->data = [];
			$this->dirty = [];
		}
	}

	/**
	 * Migrate 'dirty' data from in-memory metadata cache to system cache
	 * This is a shutdown/destruction method, not intended for external use.
	 * Unlike the corresponding parent-class method, this also works when
	 * the installer is running.
	 * @since 3.0
	 * @internal
	 * @ignore
	 */
	public function save()
	{
		$cache = $this->get_main_cache();
		foreach( $this->data as $type => $val ) {
			if( !empty($this->dirty[$type]) ) {
				$cache->set_timed($type, $val, parent::TIMEOUT, self::META_SPACE);
				unset($this->dirty[$type]);
			}
		}
	}

	/**
	 * Return names of installed modules which have, or don't have, the
	 * specified capability. Modules' availability setting is ignored.
	 * @deprecated since 3.0 Instead use
	 *  Lone::get('LoadedMetadata')->get('capable_modules',$force,$capability[,...)
	 *
	 * @param string $capability The capability name
	 * @param array  $params Optional capability parameters
	 * @param bool   $match  Optional capability-status to match. Default true.
	 * @return array of matching module names, possibly empty
	 */
	public function module_list_by_capability(string $capability, $params = [], bool $match = true) : array
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'LoadedMetadata\')->get(\'capable_modules\', $forced, parms ...)'));
		return Lone::get('LoadedMetadata')->get('capable_modules', false, $capability, $params, $match);
	}

	/**
	 * Return names of installed modules which have the specified method,
	 * and that method returns the specified result.
	 * Modules' availability setting is ignored.
	 * @deprecated since 3.0 Instead use
	 *  Lone::get('LoadedMetadata')->get('methodic_modules',$force,$method[,$returnvalue])
	 *
	 * @param string $method Method name
	 * @param mixed  $returnvalue Optional value to (non-strictly) compare
	 *  with method return-value, and only report matches. May be
	 *  LoadedMetadata::ANY_RESULT for any value. Default true.
	 * @return array of matching module names i.e. possibly empty
	 */
	public function module_list_by_method(string $method, $returnvalue = true) : array
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'LoadedMetadata\')->get(\'methodic_modules\', parms ...)'));
		return Lone::get('LoadedMetadata')->get('methodic_modules', false, $method, $returnvalue);
	}

	/**
	 * Return sub-type identifier suffix
	 * @since 3.0
	 *
	 * @param array $details
	 * @return string
	 */
	protected function get_subtype(array $details) : string
	{
		switch( count($details) ) {
			case 1:
				return parent::SUB_SEP.$details[0];
			case 0: // should never happen
				throw new DataException("Underspecified metadata type");
			default:
				$str = parent::SUB_SEP.$details[0];
				unset($details[0]);
				return $str.parent::SUB_SEP.$this->hash_subtype($details);
		}
	}
} // class
