<?php
#Class for handling and dispatching events
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

namespace CMSMS;

use cms_utils;
use CmsApp;
use CMSModule;
use CMSMS\internal\global_cachable;
use CMSMS\internal\global_cache;
use const CMS_DB_PREFIX;
use function debug_buffer;
use function lang;

/**
 * Class for handling and dispatching system and other defined events.
 *
 * @package CMS
 * @license GPL
 */
final class Events
{
	/**
	 * @ignore
	 */
	private static $_handlercache;

	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * Record an event that can be generated.
	 *
	 * @param string $originator The event 'owner' - a module name or 'Core'
	 * @param string $eventname The name of the event
	 * @return bool
	 */
	public static function CreateEvent( $originator, $eventname )
	{
		$db = CmsApp::get_instance()->GetDb();
		$id = $db->GenID( CMS_DB_PREFIX.'events_seq' );
		$originator = trim($originator);
		$eventname = trim($eventname);
		$pref = CMS_DB_PREFIX;
		$q = <<<EOS
INSERT INTO {$pref}events (event_id,originator,event_name) SELECT ?,?,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$pref}events T WHERE T.originator=? AND T.event_name=?)
EOS;
		$dbresult = $db->Execute( $q, [$id, $originator, $eventname, $originator, $eventname] );
		if ($dbresult != false) {
			global_cache::clear(__CLASS__);
			return true;
		}
		return false;
	}

	/**
	 * Remove an event from the system.
	 * This removes the event itself, and all handlers of the event
	 *
	 * @param string $originator The event 'owner' - a module name or 'Core'
	 * @param string $eventname The name of the event
     * @return bool
	 */
	public static function RemoveEvent( $originator, $eventname )
	{
		$db = CmsApp::get_instance()->GetDb();

		// get the id
		$q = 'SELECT event_id FROM '.CMS_DB_PREFIX.'events WHERE originator = ? AND event_name = ?';
		$dbresult = $db->Execute( $q, [ $originator, $eventname ] );
		if( $dbresult == false || $dbresult->RecordCount() == 0 ) {
			// query failed, event not found
			return false;
		}
		$row = $dbresult->FetchRow();
		$id = $row['event_id'];

		// delete all the handlers
		$q = 'DELETE FROM '.CMS_DB_PREFIX.'event_handlers WHERE event_id = ?';
		$db->Execute( $q, [ $id ] );

		// then delete the event
		$q = 'DELETE FROM '.CMS_DB_PREFIX.'events WHERE event_id = ?';
		$db->Execute( $q, [ $id ] );

		global_cache::clear(__CLASS__);
		return true;
	}

	/**
	 * Call all registered handlers of the given event.
	 *
	 * @param string $originator The event sender/owner - a module name or 'Core'
	 * @param string $eventname The name of the event
	 * @param array $params Optional parameters associated with the event. Default []
	 */
	public static function SendEvent( $originator, $eventname, $params = [] )
	{
		global $CMS_INSTALL_PAGE;
		if( isset($CMS_INSTALL_PAGE) ) return;

		$results = self::ListEventHandlers($originator, $eventname);
		if ($results != false) {
			$params['_modulename'] = $originator; //might be 'Core'
			$params['_eventname'] = $eventname;
			$mgr = null;
			foreach( $results as $row ) {
				if( !empty( $row['tag_name']) ) {
					if( $mgr == null ) {
						$mgr = SimplePluginOperations::get_instance();
					}
					debug_buffer($eventname.' event notice to simple plugin ' . $row['tag_name']);
					$mgr->DoEvent( $row['tag_name'], $originator, $eventname, $params );
				}
				elseif( !empty( $row['module_name']) ) {
					// don't send event to the originating module
					if( $row['module_name'] == $originator ) continue;

					// call the module event-handler
					$obj = CMSModule::GetModuleInstance($row['module_name']);
					if( $obj ) {
						debug_buffer('calling module ' . $row['module_name'] . ' from event ' . $eventname);
						$obj->DoEvent( $originator, $eventname, $params );
					}
/* TODO support regular plugin handler i.e. find/load plugin, call it
				}
				else {
		            include_once $plugfile;
		            if (!(function_exists('smarty_'.$rec['type'].'_'.$rec['name']) ||
				          function_exists('smarty_cms_'.$rec['type'].'_'.$rec['name']) ||
						  function_exists('smarty_nocache_'.$rec['type'].'_'.$rec['name']))) continue;
TODO support non-module event handlers (callables) other than plugins
*/
				}
			}
		}

		// also notify non-stored handlers, if any
        HookManager::do_hook_simple($eventname, $originator, $eventname, $params);
	}

	/**
	 * @ignore
	 */
	public static function setup()
	{
		$obj = new global_cachable(__CLASS__,function()
			{
				$db = CmsApp::get_instance()->GetDb();
				$pref = CMS_DB_PREFIX;
				$q = <<<EOS
SELECT e.event_id, eh.tag_name, eh.module_name, e.originator, e.event_name, eh.handler_order, eh.handler_id, eh.removable
FROM {$pref}event_handlers eh
INNER JOIN {$pref}events e ON e.event_id = eh.event_id
ORDER BY eh.handler_order
EOS;
				return $db->GetArray($q);
			});
		global_cache::add_cachable($obj);
	}

	/**
	 * Return the list of event handlers for a particular event.
	 *
	 * @param string $originator The event 'owner' - a module name or 'Core'
	 * @param string $eventname The name of the event
	 * @return mixed If successful, an array of arrays, each element
	 *               in the array contains two elements 'handler_name', and 'module_handler',
	 *               any one of these could be null. If it fails, false is returned.
	 */
	public static function ListEventHandlers( $originator, $eventname )
	{
		self::$_handlercache = global_cache::get(__CLASS__);
		$handlers = [];

		if( self::$_handlercache ) {
			foreach (self::$_handlercache as $row) {
				if ($row['originator'] == $originator && $row['event_name'] == $eventname) $handlers[] = $row;
			}
		}

		if (count($handlers) > 0) return $handlers;
		return false;
	}

	/**
	 * @ignore
	 */
	public static function GetEventHandler( $handler_id )
	{
		self::$_handlercache = global_cache::get(__CLASS__);

		$out = [];
		if( self::$_handlercache ) {
			foreach( self::$_handlercache as $row ) {
				if( $row['handler_id'] == $handler_id ) return $row;
			}
		}
	}

	/**
	 * Get a list of all recorded events
	 *
	 * @return mixed array or false
	 */
	public static function ListEvents()
	{
		$db = CmsApp::get_instance()->GetDb();

		$pref = CMS_DB_PREFIX;
		$q = <<<EOS
SELECT e.*, COUNT(eh.event_id) AS usage_count FROM {$pref}events e
LEFT OUTER JOIN {$pref}event_handlers eh ON e.event_id=eh.event_id
GROUP BY e.event_id
ORDER BY originator,event_name
EOS;
		$dbresult = $db->Execute( $q );
		if( $dbresult == false ) return false;

		$result = [];
		while( $row = $dbresult->FetchRow() ) {
			if( $row['originator'] == 'Core' || cms_utils::module_available($row['originator']) ) {
				$result[] = $row;
			}
		}
		return $result;
	}

	/**
	 * Record a handler of the specified event.
	 *
	 * @param string $originator The event 'owner' - a module name or 'Core'
	 * @param string $eventname The name of the event
	 * @param string $tag_name The name of a User Defined Tag. If not passed, no User Defined Tag is set.
	 * @param string $module_handler The name of the module. If not passed, no module is set.
	 * @param bool $removable Can this event be removed from the list? Defaults to true.
	 * @return bool If successful, true.  If it fails, false.
	 */
	public static function AddEventHandler( $originator, $eventname, $tag_name = false, $module_handler = false, $removable = true)
	{
		if( $tag_name == false && $module_handler == false ) return false;
		if( $tag_name != false && $module_handler != false ) return false;

		$db = CmsApp::get_instance()->GetDb();

		// find the id
		$q = 'SELECT event_id FROM '.CMS_DB_PREFIX.'events WHERE originator = ? AND event_name = ?';
		$dbresult = $db->Execute( $q, [ $originator, $eventname ] );
		if( $dbresult == false || $dbresult->RecordCount() == 0 ) return false; // query failed, event not found
		$row = $dbresult->FetchRow();
		$id = $row['event_id'];

		// now see if there's nothing already existing for this
		// tag or module and this id
		$q = 'SELECT * FROM '.CMS_DB_PREFIX.'event_handlers WHERE event_id = ? AND ';
		$params = [ $id ];
		if( $tag_name != '' ) {
			$q .= 'tag_name = ?';
			$params[] = $tag_name;
		}
		else {
			$q .= 'module_name = ?';
			$params[] = $module_handler;
		}
		$dbresult = $db->Execute( $q, $params );
		if( $dbresult != false && $dbresult->RecordCount() > 0 ) return false;	// hmmm, something matches already

		// now see if we can get a new id
		$order = 1;
		$q = 'SELECT max(handler_order) AS newid FROM '.CMS_DB_PREFIX.'event_handlers WHERE event_id = ?';
		$dbresult = $db->Execute( $q, [ $id ] );
		if( $dbresult != false && $dbresult->RecordCount() != 0) {
			$row = $dbresult->FetchRow();
			$order = $row['newid'] + 1;
		}

		$handler_id = $db->GenId( CMS_DB_PREFIX.'event_handler_seq' );

		// okay, we can insert
		$params = [ $id ];
		$q = 'INSERT INTO '.CMS_DB_PREFIX.'event_handlers ';
		if( $module_handler != false ) {
			$q .= '(event_id,module_name,removable,handler_order,handler_id)';
			$params[] = $module_handler;
		}
		else {
			//TODO also support non-module event handlers other than plugins
			$q .= '(event_id,tag_name,removable,handler_order,handler_id)';
			$params[] = $tag_name;
		}
		$q .= 'VALUES (?,?,?,?,?)';
		$params[] = ($removable?1:0);
		$params[] = $order;
		$params[] = $handler_id;
		$dbresult = $db->Execute( $q, $params );
		global_cache::clear(__CLASS__);
		return ( $dbresult != false );
	}

	/**
	 * @ignore
	 */
	protected static function InternalRemoveHandler( $handler )
	{
		$db = CmsApp::get_instance()->GetDb();
		$id = $handler['event_id'];

		// update any subsequent handlers
		$sql = 'UPDATE '.CMS_DB_PREFIX.'event_handlers SET handler_order = handler_order - 1 WHERE event_id = ? AND handler_order > ?';
		$db->Execute( $sql, [ $id, $handler['handler_order']] );

		// now delete this record
		$sql = 'DELETE FROM '.CMS_DB_PREFIX.'event_handlers WHERE event_id = ? AND handler_id = ?';
		$db->Execute( $sql, [ $id, $handler['handler_id']  ] );

		global_cache::clear(__CLASS__);
	}

	/**
	 * Remove an event handler given its id
	 *
	 * @param int $handler_id
	 */
	public static function RemoveEventHandlerById( $handler_id )
	{
		$handler = self::GetEventHandler( $handler_id );
		if( $handler ) self::InternalRemoveHandler( $handler );
	}

	/**
	 * Remove a handler of the given event.
	 *
	 * @param string $originator The event 'owner' - a module name or 'Core'
	 * @param string $eventname The name of the event
	 * @param string $tag_name Optional name of a User Defined Tag. If not passed, no User Defined Tag is set.
	 * @param string $module_handler Optional name of the module. If not passed, no module is set.
	 * @return bool indicating success or otherwise.
	 */
	public static function RemoveEventHandler( $originator, $eventname, $tag_name = false, $module_handler = false )
	{
		if( $tag_name != false && $module_handler != false ) return false;
		$field = 'handler_name';
		if( $module_handler != false ) $field = 'module_handler';

		$db = CmsApp::get_instance()->GetDb();

		// find the event id
		$sql = 'SELECT event_id FROM '.CMS_DB_PREFIX.'events WHERE originator = ? AND event_name = ?';
		$id = (int) $db->GetOne( $sql, [ $originator, $eventname ] );
		if( $id < 1 ) {
			// query failed, event not found
			return false;
		}

		// find the handler
		$sql = 'SELECT * FROM '.CMS_DB_PREFIX.'event_handlers WHERE event_id = ? AND ';
		$params = [ $id ];
		if( $module_handler != false ) {
			$sql .= 'module_name = ?';
			$params[] = $module_handler;
		}
		else {
			$sql .= 'tag_name = ?';
			$params[] = $tag_name;
		}
		$row = $db->GetRow( $sql, $params );
		if( !is_array($row) || !count($row) ) return false;

		self::InternalRemoveHandler( $row );
		return true;
	}

	/**
	 * Remove all handlers of the given event.
	 *
	 * @param string $originator The event 'owner' - a module name or 'Core'
	 * @param string $eventname The name of the event
	 * @return bool indicating success or otherwise
	 */
	public static function RemoveAllEventHandlers( $originator, $eventname )
	{
		$db = CmsApp::get_instance()->GetDb();

		// find the id
		$q = 'SELECT event_id FROM '.CMS_DB_PREFIX.'events WHERE
		originator = ? AND event_name = ?';
		$dbresult = $db->Execute( $q, [ $originator, $eventname ] );
		if( $dbresult == false || $dbresult->RecordCount() == 0 ) {
			// query failed, event not found
			return false;
		}
		$row = $dbresult->FetchRow();
		$id = $row['event_id'];

		// and delete the handlers
		$q = 'DELETE FROM '.CMS_DB_PREFIX.'event_handlers WHERE event_id = ?';
		$dbresult = $db->Execute( $q, [ $id ] );
		global_cache::clear(__CLASS__);
		return ( $dbresult != false );
	}

	/**
	 * Increase an event handler's priority
	 *
	 * @param int $handler_id
	 */
	public static function OrderHandlerUp( $handler_id )
	{
		$handler = self::GetEventHandler( $handler_id );
		if( !$handler ) return;

		if( $handler['handler_order'] < 2 ) return;

		$db = CmsApp::get_instance()->GetDb();
		$sql = 'UPDATE '.CMS_DB_PREFIX.'event_handlers SET handler_order = handler_order + 1 WHERE event_id = ? AND handler_order = ?';
		$db->Execute( $sql, [ $handler['event_id'], $handler['handler_order'] - 1 ] );
		$sql = 'UPDATE '.CMS_DB_PREFIX.'event_handlers SET handler_order = handler_order - 1 WHERE event_id = ? AND handler_id = ?';
		$db->Execute( $sql, [ $handler['event_id'], $handler['handler_id'] ] );
		global_cache::clear(__CLASS__);
	}

	/**
	 * Decrease an event handler's priority
	 *
	 * @param int $handler_id
	 */
	public static function OrderHandlerDown( $handler_id )
	{
		$handler = self::GetEventHandler( $handler_id );
		if( !$handler ) return;

		if( $handler['handler_order'] < 2 ) return;

		$db = CmsApp::get_instance()->GetDb();
		$sql = 'UPDATE '.CMS_DB_PREFIX.'event_handlers SET handler_order = handler_order - 1 WHERE event_id = ? AND handler_order = ?';
		$db->Execute( $sql, [ $handler['event_id'], $handler['handler_order'] + 1 ] );
		$sql = 'UPDATE '.CMS_DB_PREFIX.'event_handlers SET handler_order = handler_order + 1 WHERE event_id = ? AND handler_id = ?';
		$db->Execute( $sql, [ $handler['event_id'], $handler['handler_id'] ] );
		global_cache::clear(__CLASS__);
	}

	/**
	 * Get event help message (for a core event).
	 *
	 * @param string $eventname The name of the event
	 * @return string Help for the event, or empty.
	 */
	public static function GetEventHelp( $eventname )
	{
		return lang('event_help_'.strtolower($eventname));
	}

	/**
	 * Get event description (for a core event).
	 *
	 * @param string $eventname The name of the event
	 * @return string Description of the event, or empty
	 */
	public static function GetEventDescription( $eventname )
	{
		return lang('event_desc_'.strtolower($eventname));
	}
} // class

//backward-compatibility shiv
\class_alias(Events::class, 'Events', false);
