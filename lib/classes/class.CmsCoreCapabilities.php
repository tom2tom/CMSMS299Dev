<?php
# Enum-class that identifies various standard core capabilities
# Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# BUT withOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

//namespace CMSMS;

use CMSMS\BasicEnum;

/**
 * An enum that identifies various standard core capabilities
 *
 * @package CMS
 * @license GPL
 * @see CMSModule::HasCapability
 */
final class CmsCoreCapabilities extends BasicEnum
{
	/**
	 * A constant indicating the module supports admin. searches
	 */
	const ADMINSEARCH = 'AdminSearch';

	/**
	 * A constant for a capability indicating the module provides content block types
	 */
	const CONTENT_BLOCKS = 'contentblocks';

	/**
	 * A constant for a capability indicating that the module provides custom content types
	 */
	const CONTENT_TYPES = 'content_types';

	/**
	 * A constant indicating that the module is core/system
	 * @since 2.9
	 */
	const CORE_MODULE = 'coremodule';

	/**
	 * A constant indicating that the module handles events
	 */
	const EVENTS = 'handles_events';

	/**
	 * A constant indicating that the module manages async jobs
	 * @since 2.3
	 */
    const JOBS_MODULE = 'jobmanager';

	/**
	 * A constant indicating that the module is a plugin module
	 */
	const PLUGIN_MODULE = 'plugin';

	/**
	 * A constant indicating that the module sets [non-]static route(s) during construction and/or initialization
	 * @since 2.3
	 */
	const ROUTE_MODULE = 'routable';

	/**
	 * A constant indicating that the module provides frontend search functionality.
	 */
	const SEARCH_MODULE = 'search';

	/**
	 * A constant indicating that the module is a syntax editor module.
	 * @since 2.0
	 */
	const SYNTAX_MODULE = 'syntaxhighlighting';

	/**
	 * A constant indicating that the module provides pseudocron tasks
	 * See also JOBS_MODULE
	 */
	const TASKS = 'tasks';  // string used pre-2.0

	/**
	 * A capability indicating that the module is a WYSIWYG module
	 * @since 2.0
	 */
	const WYSIWYG_MODULE = 'wysiwyg';

} // class
